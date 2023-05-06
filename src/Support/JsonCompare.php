<?php

namespace CodeDistortion\JsonDiff\Support;

use CodeDistortion\JsonDiff\Exceptions\JsonDiffException;
use CodeDistortion\JsonDiff\JsonDelta;

/**
 * Perform a comparison between two data structures, generates a JsonDelta containing the differences.
 *
 * @codingStandardsIgnoreStart
 *
 * @internal
 *
 * @codingStandardsIgnoreEnd
 */
class JsonCompare
{
    /**
     * Perform a comparison between two values.
     *
     * @param mixed $original The original value.
     * @param mixed $new      The new value.
     * @return JsonDelta
     */
    public static function compare(mixed $original, mixed $new): JsonDelta
    {
        $delta = new JsonDelta();
        self::compareMixed($delta, [], $original, $new, 0);
        return $delta;
    }

    /**
     * Actually perform the comparison between two values.
     *
     * @param JsonDelta                      $delta    The delta object to add changes to.
     * @param array<integer, string|integer> $path     The key-path these values came from.
     * @param mixed                          $original The original value.
     * @param mixed                          $new      The new value.
     * @param integer                        $position The position (when already inside an array).
     * @return void
     * @throws JsonDiffException When some data is passed that cannot be handled.
     */
    private static function compareMixed(
        JsonDelta $delta,
        array $path,
        mixed $original,
        mixed $new,
        int $position
    ): void {

        Support::ensureDataIsValid($original);
        Support::ensureDataIsValid($new);

        if (Support::isScalar($original)) {
            self::compareScalar($delta, $path, $original, $new, $position);
            return;
        }

        if (is_array($original)) {
            self::compareArray($delta, $path, $original, $new, $position);
        }
    }

    /**
     * Check to see if two scalar values are the same.
     *
     * @param JsonDelta                      $delta    The delta object to add changes to.
     * @param array<integer, string|integer> $path     The key-path these values came from.
     * @param mixed                          $original The original value.
     * @param mixed                          $new      The new value.
     * @param integer                        $position The position (when already inside an array).
     * @return void
     */
    private static function compareScalar(
        JsonDelta $delta,
        array $path,
        mixed $original,
        mixed $new,
        int $position
    ): void {

        if ((!Support::isScalar($new)) || ($original !== $new)) {
            $delta->recordChangedValue($path, $original, $new, $position);
        }
    }

    /**
     * Check to see if two arrays are the same.
     *
     * @param JsonDelta                      $delta    The delta object to add changes to.
     * @param array<integer, string|integer> $path     The key-path these values came from.
     * @param mixed[]                        $original The original value.
     * @param mixed                          $new      The new value.
     * @param integer                        $position The position (when already inside an array).
     * @return void
     */
    private static function compareArray(
        JsonDelta $delta,
        array $path,
        array $original,
        mixed $new,
        int $position
    ): void {

        if (!is_array($new)) {
            $delta->recordChangedValue($path, $original, $new, $position);
            return;
        }

        $newKeys = array_keys($new);
        $originalKeys = array_keys($original);

        // ignore the new values that are in the correct position
        foreach ($originalKeys as $originalKey) {
            if (reset($newKeys) == $originalKey) {
                array_shift($newKeys);
            }
        }

        // loop through the original items, and record what happened to their values
        $position = 0;
        foreach ($originalKeys as $originalKey) {

            $curPath = array_merge($path, [$originalKey]);

            // exists in a different position in the new array
            if (in_array($originalKey, $newKeys)) {
                $delta->recordRemovedValue($curPath, $original[$originalKey], $position);
            // exists in the same position, check if its value has changed
            } elseif (array_key_exists($originalKey, $new)) {
                self::compareMixed($delta, $curPath, $original[$originalKey], $new[$originalKey], $position);
                $position++;
            // the value is missing from the new array
            } else {
                $delta->recordRemovedValue($curPath, $original[$originalKey], $position);
            }
        }

        // add any new values to the end
        foreach ($newKeys as $newKey) {
            $curPath = array_merge($path, [$newKey]);
            $delta->recordNewValue($curPath, $new[$newKey], $position);
            $position++;
        }
    }
}
