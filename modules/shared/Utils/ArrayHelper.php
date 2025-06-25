<?php

namespace Modules\Shared\Utils;

class ArrayHelper
{
    public static function flatten(array $array): array
    {
        $result = [];
        array_walk_recursive($array, function ($a) use (&$result) {
            $result[] = $a;
        });
        return $result;
    }
} 