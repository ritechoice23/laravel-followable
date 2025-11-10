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
        // If it's already a class name, get its morph alias
        if (class_exists($type)) {
            $morphMap = Relation::morphMap();
            if (!empty($morphMap)) {
                $alias = array_search($type, $morphMap, true);
                return $alias !== false ? $alias : $type;
            }
            return $type;
        }

        // If it's a morph alias, verify it exists in the map
        $morphMap = Relation::morphMap();
        if (isset($morphMap[$type])) {
            return $type;
        }

        // Return as-is if no morph map is set
        return $type;
    }

    /**
     * Get the actual class name from a morph type (handles both aliases and class names).
     */
    protected function getMorphClassFor(string $type): string
    {
        $morphMap = Relation::morphMap();

        // If morph map exists and type is an alias, return the mapped class
        if (!empty($morphMap) && isset($morphMap[$type])) {
            return $morphMap[$type];
        }

        // If it's already a class name, return it
        if (class_exists($type)) {
            return $type;
        }

        // Return as-is (might not exist)
        return $type;
    }
}
