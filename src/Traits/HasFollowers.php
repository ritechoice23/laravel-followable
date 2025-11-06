<?php

namespace Ritechoice23\Followable\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Ritechoice23\Followable\Models\Follow;

trait HasFollowers
{
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
    public function followers(?string $type = null): Builder
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
    public function followersOfType(string|array $types): Builder
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
            $modelClass = $type;
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
            $query->where('follower_type', $type);
        }

        return $query->count();
    }

    /**
     * Get mutual followers (followers shared between this model and another model).
     */
    public function mutualFollowers(Model $model, ?string $type = null): Collection
    {
        $followsTable = config('follow.table_name', 'follows');

        // Get follower IDs for this model
        $thisFollowerIds = $this->followRecords()
            ->when($type, fn ($q) => $q->where('follower_type', $type))
            ->pluck('follower_id');

        // Get follower IDs for the other model
        $otherFollowerIds = $model->followRecords()
            ->when($type, fn ($q) => $q->where('follower_type', $type))
            ->pluck('follower_id');

        // Find intersection
        $mutualIds = $thisFollowerIds->intersect($otherFollowerIds)->values();

        if ($mutualIds->isEmpty()) {
            return collect();
        }

        // If type is specified, fetch those models
        if ($type !== null && class_exists($type)) {
            return $type::whereIn('id', $mutualIds)->get();
        }

        // For mixed types, group by type
        $mutualFollows = $this->followRecords()
            ->whereIn('follower_id', $mutualIds)
            ->select('follower_type', 'follower_id')
            ->get()
            ->groupBy('follower_type');

        $result = collect();
        foreach ($mutualFollows as $followerType => $records) {
            if (class_exists($followerType)) {
                $ids = $records->pluck('follower_id')->unique()->values();
                $models = $followerType::whereIn('id', $ids)->get();
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

    protected function buildFollowersQuery(?string $type = null, ?array $types = null): Builder
    {
        $followsTable = config('follow.table_name', 'follows');

        if ($types !== null && count($types) > 0) {
            if (count($types) === 1) {
                return $this->buildSingleTypeFollowersQuery($types[0], $followsTable);
            }

            return $this->buildMixedTypesQuery($types, $followsTable);
        }

        if ($type !== null) {
            return $this->buildSingleTypeFollowersQuery($type, $followsTable);
        }

        $distinctTypes = $this->followRecords()
            ->select('follower_type')
            ->distinct()
            ->pluck('follower_type')
            ->filter(fn ($t) => class_exists($t))
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

        return $this->buildMixedTypesQuery($distinctTypes, $followsTable);
    }

    protected function buildSingleTypeFollowersQuery(string $type, string $followsTable): Builder
    {
        if (! class_exists($type)) {
            return $this->newQuery()->whereRaw('1 = 0');
        }

        $model = new $type;
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

    protected function buildMixedTypesQuery(array $types, string $followsTable): Builder
    {
        $firstModel = new $types[0];
        $builder = $firstModel->newQuery();
        $self = $this;

        $builder->macro('get', function ($columns = ['*']) use ($types, $followsTable, $self) {
            $allFollowers = collect();

            foreach ($types as $type) {
                if (! class_exists($type)) {
                    continue;
                }

                $results = $self->buildSingleTypeFollowersQuery($type, $followsTable)->get($columns);
                $allFollowers = $allFollowers->merge($results);
            }

            return $allFollowers->sortByDesc(function ($follower) use ($self) {
                return $self->followRecords()
                    ->where('follower_type', get_class($follower))
                    ->where('follower_id', $follower->getKey())
                    ->value('created_at');
            })->values();
        });

        $builder->macro('paginate', function ($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null) use ($types, $followsTable, $self) {
            $allFollowers = collect();

            foreach ($types as $type) {
                if (! class_exists($type)) {
                    continue;
                }

                $query = $self->buildSingleTypeFollowersQuery($type, $followsTable);
                $results = $query->get($columns);
                $allFollowers = $allFollowers->merge($results);
            }

            $sorted = $allFollowers->sortByDesc(function ($follower) use ($self) {
                return $self->followRecords()
                    ->where('follower_type', get_class($follower))
                    ->where('follower_id', $follower->getKey())
                    ->value('created_at');
            })->values();

            $page = $page ?: (Paginator::resolveCurrentPage($pageName) ?: 1);
            $items = $sorted->forPage($page, $perPage);

            return new LengthAwarePaginator(
                $items,
                $sorted->count(),
                $perPage,
                $page,
                ['path' => Paginator::resolveCurrentPath(), 'pageName' => $pageName]
            );
        });

        return $builder->whereRaw('1 = 1');
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
