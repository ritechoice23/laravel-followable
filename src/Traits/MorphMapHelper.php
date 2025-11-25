<?php

namespace Ritechoice23\Followable\Traits;

use Illuminate\Database\Eloquent\Relations\Relation;

trait MorphMapHelper
{
    /**
     * Resolve morph type to handle both morph map keys and full class names.
     */
    protected function resolveMorphType(string $type): string
    {
        if (class_exists($type)) {
            $morphMap = Relation::morphMap();
            if (! empty($morphMap)) {
                $alias = array_search($type, $morphMap, true);

                return $alias !== false ? $alias : $type;
            }

            return $type;
        }

        $morphMap = Relation::morphMap();
        if (isset($morphMap[$type])) {
            return $type;
        }

        return $type;
    }

    /**
     * Get the actual class name from a morph type (handles both aliases and class names).
     */
    protected function getMorphClassFor(string $type): string
    {
        $morphMap = Relation::morphMap();

        if (! empty($morphMap) && isset($morphMap[$type])) {
            return $morphMap[$type];
        }

        if (class_exists($type)) {
            return $type;
        }

        return $type;
    }
}
