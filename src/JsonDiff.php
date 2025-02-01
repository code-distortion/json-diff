<?php

namespace CodeDistortion\JsonDiff;

use CodeDistortion\JsonDiff\Support\JsonApply;
use CodeDistortion\JsonDiff\Support\JsonCompare;

/**
 * Perform data comparison and transformations.
 *
 * Handles scalar values, null, and arrays (also containing: scalar values, null, and arrays).
 */
class JsonDiff
{
    /**
     * Perform a comparison between two values.
     *
     * @param mixed $original The original value.
     * @param mixed $new      The new value.
     * @return JsonDelta A JsonDelta object representing the differences
     */
    public static function compare(mixed $original, mixed $new): JsonDelta
    {
        return JsonCompare::compare($original, $new);
    }

    /**
     * Apply Delta changes to a value.
     *
     * @param mixed     $original The original value.
     * @param JsonDelta $delta    The delta to apply.
     * @return mixed
     */
    public static function applyDelta(mixed $original, JsonDelta $delta): mixed
    {
        return JsonApply::applyDelta($original, $delta);
    }

    /**
     * Apply Delta changes to a value in reverse.
     *
     * @param mixed     $new   The new value.
     * @param JsonDelta $delta The delta to apply.
     * @return mixed
     */
    public static function undoDelta(mixed $new, JsonDelta $delta): mixed
    {
        return JsonApply::undoDelta($new, $delta);
    }
}
