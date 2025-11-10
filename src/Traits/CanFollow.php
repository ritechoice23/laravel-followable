<?php

namespace Ritechoice23\Followable\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Ritechoice23\Followable\Models\Follow;
use Ritechoice23\Followable\Support\MixedModelsCollection;

trait CanFollow
{
    use MorphMapHelper;

    public function followingRecords(): MorphMany
    {
        return $this->morphMany(Follow::class, 'follower');
    }

    /**
     * Get actual followable models (not Follow records).
     *
     * Best for single-type followings. For mixed types, use followingsGrouped().
     */
    public function followings(?string $type = null): Builder|Collection
    {
        return $this->buildFollowingsQuery($type);
    }

    /**
     * Get paginated followable models.
     */
    public function followingsPaginated(int $perPage = 15, ?string $type = null): LengthAwarePaginatorContract
    {
        return $this->followings($type)->paginate($perPage);
    }

    /**
     * Get followings filtered by specific type(s).
     */
    public function followingsOfType(string|array $types): Builder|Collection
    {
        $types = is_array($types) ? $types : [$types];

        if (count($types) === 1) {
            return $this->followings($types[0]);
        }

        return $this->buildFollowingsQuery(null, $types);
    }

    /**
     * Get followings grouped by model type (perfect for mixed followable types).
     *
     * @return Collection<string, Collection>
     */
    public function followingsGrouped(): Collection
    {
        $followRecords = $this->followingRecords()
            ->select('followable_type', 'followable_id')
            ->get()
            ->groupBy('followable_type');

        $grouped = collect();

        foreach ($followRecords as $type => $records) {
            $modelClass = $this->getMorphClassFor($type);
            $ids = $records->pluck('followable_id')->unique()->values();

            if (class_exists($modelClass)) {
                $models = $modelClass::whereIn('id', $ids)->get();
                $grouped->put($type, $models);
            }
        }

        return $grouped;
    }

    public function follow(Model|int|string $target, array $metadata = []): bool
    {
        [$targetModel, $targetType, $targetId] = $this->normalizeTarget($target);

        if (! config('follow.allow_self_follow', false)) {
            if ($this->getMorphClass() === $targetType && $this->getKey() === $targetId) {
                return false;
            }
        }

        if ($this->isFollowing($target)) {
            return false;
        }

        $follow = $this->followingRecords()->create([
            'followable_type' => $targetType,
            'followable_id' => $targetId,
            'metadata' => $metadata,
        ]);

        return $follow !== null;
    }

    public function unfollow(Model|int|string $target): bool
    {
        [$targetModel, $targetType, $targetId] = $this->normalizeTarget($target);

        $deleted = $this->followingRecords()
            ->where('followable_type', $targetType)
            ->where('followable_id', $targetId)
            ->delete();

        return $deleted > 0;
    }

    public function toggleFollow(Model|int|string $target, ?array $metadata = null): bool
    {
        if ($this->isFollowing($target)) {
            $this->unfollow($target);

            return false;
        }

        $this->follow($target, $metadata ?? []);

        return true;
    }

    public function isFollowing(Model|int|string $target): bool
    {
        [$targetModel, $targetType, $targetId] = $this->normalizeTarget($target);

        return $this->followingRecords()
            ->where('followable_type', $targetType)
            ->where('followable_id', $targetId)
            ->exists();
    }

    public function followingCount(?string $type = null): int
    {
        $query = $this->followingRecords();

        if ($type !== null) {
            $resolvedType = $this->resolveMorphType($type);
            $query->where('followable_type', $resolvedType);
        }

        return $query->count();
    }

    /**
     * Get mutual followings (models that both this model and another model follow).
     */
    public function mutualFollowings(Model $model, ?string $type = null): Collection
    {
        $resolvedType = $type ? $this->resolveMorphType($type) : null;

        // Get following IDs for this model
        $thisFollowingIds = $this->followingRecords()
            ->when($resolvedType, fn ($q) => $q->where('followable_type', $resolvedType))
            ->pluck('followable_id');

        // Get following IDs for the other model
        $otherFollowingIds = $model->followingRecords()
            ->when($resolvedType, fn ($q) => $q->where('followable_type', $resolvedType))
            ->pluck('followable_id');

        // Find intersection
        $mutualIds = $thisFollowingIds->intersect($otherFollowingIds)->values();

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
        $mutualFollows = $this->followingRecords()
            ->whereIn('followable_id', $mutualIds)
            ->select('followable_type', 'followable_id')
            ->get()
            ->groupBy('followable_type');

        $result = collect();
        foreach ($mutualFollows as $followableType => $records) {
            $modelClass = $this->getMorphClassFor($followableType);
            if (class_exists($modelClass)) {
                $ids = $records->pluck('followable_id')->unique()->values();
                $models = $modelClass::whereIn('id', $ids)->get();
                $result = $result->merge($models);
            }
        }

        return $result;
    }

    /**
     * Check if there's a mutual follow relationship (both follow each other).
     */
    public function isMutualFollow(Model $model): bool
    {
        return $this->isFollowing($model) && $model->isFollowing($this);
    }

    /**
     * Get all mutual connections (models that follow each other bidirectionally).
     * Only works when the model can both follow and be followed.
     */
    public function mutualConnections(?string $type = null): Collection
    {
        if (! method_exists($this, 'followRecords')) {
            return collect();
        }

        $resolvedType = $type ? $this->resolveMorphType($type) : null;

        // Get IDs of models this model follows
        $followingIds = $this->followingRecords()
            ->when($resolvedType, fn ($q) => $q->where('followable_type', $resolvedType))
            ->pluck('followable_id');

        if ($followingIds->isEmpty()) {
            return collect();
        }

        // Get IDs of models that follow this model back
        $followerIds = $this->followRecords()
            ->when($resolvedType, fn ($q) => $q->where('follower_type', $resolvedType))
            ->pluck('follower_id');

        // Find mutual (intersection)
        $mutualIds = $followingIds->intersect($followerIds)->values();

        if ($mutualIds->isEmpty()) {
            return collect();
        }

        // If type specified, fetch those models
        if ($resolvedType !== null) {
            $modelClass = $this->getMorphClassFor($resolvedType);
            if (class_exists($modelClass)) {
                return $modelClass::whereIn('id', $mutualIds)->get();
            }
        }

        // For mixed types
        $mutualFollows = $this->followingRecords()
            ->whereIn('followable_id', $mutualIds)
            ->select('followable_type', 'followable_id')
            ->get()
            ->groupBy('followable_type');

        $result = collect();
        foreach ($mutualFollows as $followableType => $records) {
            $modelClass = $this->getMorphClassFor($followableType);
            if (class_exists($modelClass)) {
                $ids = $records->pluck('followable_id')->unique()->values();
                $models = $modelClass::whereIn('id', $ids)->get();
                $result = $result->merge($models);
            }
        }

        return $result;
    }

    public function scopeWhereFollowings(Builder $query, Model|int|string $target): Builder
    {
        [$targetModel, $targetType, $targetId] = $this->normalizeTarget($target);

        return $query->whereHas('followingRecords', function ($q) use ($targetType, $targetId) {
            $q->where('followable_type', $targetType)
                ->where('followable_id', $targetId);
        });
    }

    protected function buildFollowingsQuery(?string $type = null, ?array $types = null): Builder|Collection
    {
        $followsTable = config('follow.table_name', 'follows');

        if ($types !== null && count($types) > 0) {
            $resolvedTypes = array_map(fn ($t) => $this->resolveMorphType($t), $types);

            if (count($resolvedTypes) === 1) {
                return $this->buildSingleTypeFollowingsQuery($resolvedTypes[0], $followsTable);
            }

            return $this->buildMixedTypesFollowingsCollection($resolvedTypes, $followsTable);
        }

        if ($type !== null) {
            $resolvedType = $this->resolveMorphType($type);

            return $this->buildSingleTypeFollowingsQuery($resolvedType, $followsTable);
        }

        $distinctTypes = $this->followingRecords()
            ->select('followable_type')
            ->distinct()
            ->pluck('followable_type')
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
            return $this->buildSingleTypeFollowingsQuery($distinctTypes[0], $followsTable);
        }

        return $this->buildMixedTypesFollowingsCollection($distinctTypes, $followsTable);
    }

    protected function buildSingleTypeFollowingsQuery(string $type, string $followsTable): Builder
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
                $join->on("{$followsTable}.followable_id", '=', "{$table}.{$keyName}")
                    ->where("{$followsTable}.followable_type", '=', $type);
            })
            ->where("{$followsTable}.follower_type", $this->getMorphClass())
            ->where("{$followsTable}.follower_id", $this->getKey())
            ->orderBy("{$followsTable}.created_at", 'desc');
    }

    protected function buildMixedTypesFollowingsCollection(array $types, string $followsTable): Collection
    {
        $allFollowings = collect();

        foreach ($types as $type) {
            $modelClass = $this->getMorphClassFor($type);
            if (! class_exists($modelClass)) {
                continue;
            }

            $results = $this->buildSingleTypeFollowingsQuery($type, $followsTable)->get();
            $allFollowings = $allFollowings->merge($results);
        }

        $sorted = $allFollowings->sortByDesc(function ($following) {
            return $this->followingRecords()
                ->where('followable_type', $following->getMorphClass())
                ->where('followable_id', $following->getKey())
                ->value('created_at');
        })->values();

        return new MixedModelsCollection($sorted);
    }

    protected function normalizeTarget(Model|int|string $target): array
    {
        if ($target instanceof Model) {
            return [
                $target,
                $target->getMorphClass(),
                $target->getKey(),
            ];
        }

        if (is_numeric($target)) {
            return [
                null,
                $this->getMorphClass(),
                $target,
            ];
        }

        return [null, $target, null];
    }
}
