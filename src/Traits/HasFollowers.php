<?php

namespace Ritechoice23\Followable\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Ritechoice23\Followable\Models\Follow;
use Ritechoice23\Followable\Support\MixedModelsCollection;

trait HasFollowers
{
    use MorphMapHelper;

    /**
     * Get the Follow pivot records (use when you need metadata).
     */
    public function followRecords(): MorphMany
    {
        return $this->morphMany(Follow::class, 'followable');
    }

    /**
     * Get actual follower models (not Follow records).
     *
     * Best for single-type followers. For mixed types, use followersGrouped().
     */
    public function followers(?string $type = null): Builder|Collection
    {
        return $this->buildFollowersQuery($type);
    }

    /**
     * Get paginated follower models.
     */
    public function followersPaginated(int $perPage = 15, ?string $type = null): LengthAwarePaginatorContract
    {
        return $this->followers($type)->paginate($perPage);
    }

    /**
     * Get followers filtered by specific type(s).
     */
    public function followersOfType(string|array $types): Builder|Collection
    {
        $types = is_array($types) ? $types : [$types];

        if (count($types) === 1) {
            return $this->followers($types[0]);
        }

        return $this->buildFollowersQuery(null, $types);
    }

    /**
     * Get followers grouped by model type (perfect for mixed follower types).
     *
     * @return Collection<string, Collection>
     */
    public function followersGrouped(): Collection
    {
        $followRecords = $this->followRecords()
            ->select('follower_type', 'follower_id')
            ->get()
            ->groupBy('follower_type');

        $grouped = collect();

        foreach ($followRecords as $type => $records) {
            $modelClass = $this->getMorphClassFor($type);
            $ids = $records->pluck('follower_id')->unique()->values();

            if (class_exists($modelClass)) {
                $models = $modelClass::whereIn('id', $ids)->get();
                $grouped->put($type, $models);
            }
        }

        return $grouped;
    }

    /**
     * Count followers (optionally filtered by type).
     */
    public function followersCount(?string $type = null): int
    {
        $query = $this->followRecords();

        if ($type !== null) {
            $resolvedType = $this->resolveMorphType($type);
            $query->where('follower_type', $resolvedType);
        }

        return $query->count();
    }

    /**
     * Get mutual followers (followers shared between this model and another model).
     */
    public function mutualFollowers(Model $model, ?string $type = null): Collection
    {
        $followsTable = config('follow.table_name', 'follows');
        $resolvedType = $type ? $this->resolveMorphType($type) : null;

        // Get follower IDs for this model
        $thisFollowerIds = $this->followRecords()
            ->when($resolvedType, fn ($q) => $q->where('follower_type', $resolvedType))
            ->pluck('follower_id');

        // Get follower IDs for the other model
        $otherFollowerIds = $model->followRecords()
            ->when($resolvedType, fn ($q) => $q->where('follower_type', $resolvedType))
            ->pluck('follower_id');

        // Find intersection
        $mutualIds = $thisFollowerIds->intersect($otherFollowerIds)->values();

        if ($mutualIds->isEmpty()) {
            return collect();
        }

        // If type is specified, fetch those models
        if ($resolvedType !== null) {
            $modelClass = $this->getMorphClassFor($resolvedType);
            if (class_exists($modelClass)) {
                return $modelClass::whereIn('id', $mutualIds)->get();
            }
        }

        // For mixed types, group by type
        $mutualFollows = $this->followRecords()
            ->whereIn('follower_id', $mutualIds)
            ->select('follower_type', 'follower_id')
            ->get()
            ->groupBy('follower_type');

        $result = collect();
        foreach ($mutualFollows as $followerType => $records) {
            $modelClass = $this->getMorphClassFor($followerType);
            if (class_exists($modelClass)) {
                $ids = $records->pluck('follower_id')->unique()->values();
                $models = $modelClass::whereIn('id', $ids)->get();
                $result = $result->merge($models);
            }
        }

        return $result;
    }

    /**
     * Check if followed by a specific follower.
     */
    public function isFollowedBy(Model|int|string $follower): bool
    {
        [$followerModel, $followerType, $followerId] = $this->normalizeFollower($follower);

        return $this->followRecords()
            ->where('follower_type', $followerType)
            ->where('follower_id', $followerId)
            ->exists();
    }

    /**
     * Scope to filter models by follower.
     */
    public function scopeWhereFollowers(Builder $query, Model|int|string $follower): Builder
    {
        [$followerModel, $followerType, $followerId] = $this->normalizeFollower($follower);

        return $query->whereHas('followRecords', function ($q) use ($followerType, $followerId) {
            $q->where('follower_type', $followerType)
                ->where('follower_id', $followerId);
        });
    }

    protected function buildFollowersQuery(?string $type = null, ?array $types = null): Builder|Collection
    {
        $followsTable = config('follow.table_name', 'follows');

        if ($types !== null && count($types) > 0) {
            $resolvedTypes = array_map(fn ($t) => $this->resolveMorphType($t), $types);

            if (count($resolvedTypes) === 1) {
                return $this->buildSingleTypeFollowersQuery($resolvedTypes[0], $followsTable);
            }

            return $this->buildMixedTypesFollowersCollection($resolvedTypes, $followsTable);
        }

        if ($type !== null) {
            $resolvedType = $this->resolveMorphType($type);

            return $this->buildSingleTypeFollowersQuery($resolvedType, $followsTable);
        }

        $distinctTypes = $this->followRecords()
            ->select('follower_type')
            ->distinct()
            ->pluck('follower_type')
            ->filter(fn ($t) => class_exists($this->getMorphClassFor($t)))
            ->values()
            ->all();

        if (count($distinctTypes) === 0) {
            $dummyModel = $this->getMorphClass();
            if (class_exists($dummyModel)) {
                return $dummyModel::query()->whereRaw('1 = 0');
            }

            return $this->newQuery()->whereRaw('1 = 0');
        }

        if (count($distinctTypes) === 1) {
            return $this->buildSingleTypeFollowersQuery($distinctTypes[0], $followsTable);
        }

        return $this->buildMixedTypesFollowersCollection($distinctTypes, $followsTable);
    }

    protected function buildSingleTypeFollowersQuery(string $type, string $followsTable): Builder
    {
        $modelClass = $this->getMorphClassFor($type);

        if (! class_exists($modelClass)) {
            return $this->newQuery()->whereRaw('1 = 0');
        }

        $model = new $modelClass;
        $table = $model->getTable();
        $keyName = $model->getKeyName();

        return $model->newQuery()
            ->select("{$table}.*")
            ->join($followsTable, function ($join) use ($table, $keyName, $followsTable, $type) {
                $join->on("{$followsTable}.follower_id", '=', "{$table}.{$keyName}")
                    ->where("{$followsTable}.follower_type", '=', $type);
            })
            ->where("{$followsTable}.followable_type", $this->getMorphClass())
            ->where("{$followsTable}.followable_id", $this->getKey())
            ->orderBy("{$followsTable}.created_at", 'desc');
    }

    protected function buildMixedTypesFollowersCollection(array $types, string $followsTable): MixedModelsCollection
    {
        $allFollowers = collect();

        foreach ($types as $type) {
            $modelClass = $this->getMorphClassFor($type);
            if (! class_exists($modelClass)) {
                continue;
            }

            $results = $this->buildSingleTypeFollowersQuery($type, $followsTable)->get();
            $allFollowers = $allFollowers->merge($results);
        }

        $sorted = $allFollowers->sortByDesc(function ($follower) {
            return $this->followRecords()
                ->where('follower_type', $follower->getMorphClass())
                ->where('follower_id', $follower->getKey())
                ->value('created_at');
        })->values();

        return new MixedModelsCollection($sorted);
    }

    protected function getCommonColumns(array $types): array
    {
        $columnSets = [];

        foreach ($types as $type) {
            if (! class_exists($type)) {
                continue;
            }

            $model = new $type;
            $table = $model->getTable();
            $columns = Schema::getColumnListing($table);
            $columnSets[] = $columns;
        }

        if (empty($columnSets)) {
            return ['id'];
        }

        $commonColumns = array_shift($columnSets);
        foreach ($columnSets as $columns) {
            $commonColumns = array_intersect($commonColumns, $columns);
        }

        return array_values($commonColumns);
    }

    protected function normalizeFollower(Model|int|string $follower): array
    {
        if ($follower instanceof Model) {
            return [
                $follower,
                $follower->getMorphClass(),
                $follower->getKey(),
            ];
        }

        if (is_numeric($follower)) {
            return [
                null,
                $this->getMorphClass(),
                $follower,
            ];
        }

        return [null, $follower, null];
    }
}
