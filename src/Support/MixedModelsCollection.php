<?php

namespace Ritechoice23\Followable\Support;

use Illuminate\Support\Collection;

class MixedModelsCollection extends Collection
{
    /**
     * Get all models (acts like Builder's get() method).
     * This allows the collection to be used with ->get() like a query builder.
     */
    public function get($key = null, $default = null)
    {
        // If no key provided, return the entire collection (Builder-like behavior)
        if ($key === null && $default === null && func_num_args() === 0) {
            return $this;
        }

        // Otherwise, use parent Collection get() behavior
        return parent::get($key, $default);
    }

    /**
     * Paginate the collection manually.
     */
    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $page = $page ?: \Illuminate\Pagination\Paginator::resolveCurrentPage($pageName);

        $items = $this->forPage($page, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $this->count(),
            $perPage,
            $page,
            [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'pageName' => $pageName
            ]
        );
    }
}
