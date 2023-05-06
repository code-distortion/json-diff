<?php

namespace CodeDistortion\JsonDiff\Support;

use CodeDistortion\JsonDiff\Exceptions\JsonDiffException;
use CodeDistortion\JsonDiff\JsonDelta;

/**
 * Apply a JsonDelta's changes to a data structure.
 *
 * @codingStandardsIgnoreStart
 *
 * @internal
 *
 * @codingStandardsIgnoreEnd
 */
class JsonApply
{
    /**
     * Apply Delta changes to a value.
     *
     * @param mixed     $data  The original value.
     * @param JsonDelta $delta The delta to apply.
     * @return mixed
     */
    public static function applyDelta(mixed $data, JsonDelta $delta): mixed
    {
        return self::performDeltaApplication($data, $delta, false);
    }

    /**
     * "undo" the changes - apply Delta changes to a value in reverse.
     *
     * @param mixed     $data  The new value.
     * @param JsonDelta $delta The delta to apply.
     * @return mixed
     */
    public static function undoDelta(mixed $data, JsonDelta $delta): mixed
    {
        return self::performDeltaApplication($data, $delta, true);
    }

    /**
     * Apply this delta change to a value.
     *
     * @param mixed     $data    The value to alter.
     * @param JsonDelta $delta   The delta to apply.
     * @param boolean   $reverse Apply the reverse actions?.
     * @return mixed
     */
    private static function performDeltaApplication(mixed $data, JsonDelta $delta, bool $reverse): mixed
    {
        $journal = $delta->getJournal();

        if ($reverse) {
            $journal = array_reverse($journal);
        }

        foreach ($journal as $entry) {

            /** @var string $type */
            $type = $entry[JsonDelta::KEY_TYPE];
            /** @var array<integer, string|integer> $path */
            $path = $entry[JsonDelta::KEY_PATH];
            /** @var integer $position */
            $position = $entry[JsonDelta::KEY_POSITION];
            /** @var mixed $newValue */
            $newValue = $entry[JsonDelta::KEY_NEW_VALUE];

            if ($reverse) {
                if ($type == JsonDelta::TYPE_NEW) {
                    $type = JsonDelta::TYPE_REMOVED;
                } elseif ($type == JsonDelta::TYPE_REMOVED) {
                    $type = JsonDelta::TYPE_NEW;
                }
                $newValue = $entry[JsonDelta::KEY_ORIG_VALUE];
            }

            switch ($type) {

                case JsonDelta::TYPE_NEW:
                    $data = self::addValue($data, $path, $position, $newValue);
                    break;

                case JsonDelta::TYPE_CHANGED:
                    $data = self::setValue($data, $path, $position, $newValue);
                    break;

                case JsonDelta::TYPE_REMOVED:
                    $data = self::removeValue($data, $path, $position);
                    break;
            }
        }
        return $data; // the original with the changes applied
    }

//    /**
//     * Add a value to an array, given a particular key-path.
//     *
//     * @param array $array The array to add to.
//     * @param array $path  The key-path to take.
//     * @param mixed $value The value to add to the array.
//     * @return array
//     */
//    private function setArrayPath(array $array, array $path, mixed $value): array
//    {
//        $key = array_shift($path);
//
//        $array[$key] = count($path) // still more depth to go?
//            ? self::setArrayPath($array[$key] ?? [], $path, $value)
//            : $value;
//
//        return $array;
//    }

    /**
     * Add a value to an array, given a particular key-path.
     *
     * @param mixed                          $array    The array to add to.
     * @param array<integer, string|integer> $path     The key-path to take.
     * @param integer                        $position The position in the bottom most array.
     * @param mixed                          $value    The value to add to the array.
     * @return mixed[]
     */
    private static function addValue(mixed $array, array $path, int $position, mixed $value): array
    {
        if (!is_array($array)) {
            $array = [];
        }

        $key = array_shift($path);
        $key = is_null($key)
            ? ''
            : $key;

        // still more depth to go?
        if (count($path)) {
            $array[$key] = self::addValue($array[$key] ?? [], $path, $position, $value);
            return $array;
        }

        return self::insertIntoArray($array, $key, $position, $value);
    }

    /**
     * Add a value to an array, given a particular key-path.
     *
     * @param mixed                          $array    The array to add to.
     * @param array<integer, string|integer> $path     The key-path to take.
     * @param integer                        $position The position in the bottom most array.
     * @param mixed                          $value    The value to add to the array.
     * @return mixed
     * @throws JsonDiffException When a non-zero position is specified for a non-array.
     */
    private static function setValue(mixed $array, array $path, int $position, mixed $value): mixed
    {
        if (!count($path)) {
//            // just an internal check to remove a mutation
//            if ($position != 0) {
//                throw JsonDiffException::positionSpecifiedForNonArray($position);
//            }
            return $value;
        }

        if (!is_array($array)) {
            $array = [];
        }

        $key = array_shift($path);

        // still more depth to go?
        if (count($path)) {
            $array[$key] = self::setValue($array[$key] ?? [], $path, $position, $value);
        } else {
            $key = self::getKeyAtPos($array, $position);
            $array[$key] = $value;
        }

        return $array;
    }

    /**
     * Remove a value from an array, given a particular key-path.
     *
     * @param mixed                          $array    The array to add to.
     * @param array<integer, string|integer> $path     The key-path to take.
     * @param integer                        $position The position in the bottom most array.
     * @return mixed
     */
    private static function removeValue(mixed $array, array $path, int $position): mixed
    {
        if (!is_array($array)) {
            return $array;
        }

        $key = array_shift($path);
        $key = is_null($key)
            ? ''
            : $key;

        // still more depth to go?
        if (count($path)) {
            $array[$key] = self::removeValue($array[$key] ?? [], $path, $position);
        } else {
            $key = self::getKeyAtPos($array, $position);
            unset($array[$key]);
        }

        return $array;
    }

    /**
     * Pick the array key that's in a certain position.
     *
     * @param mixed[]        $array    The array to inspect.
     * @param integer|string $key      They key to insert.
     * @param integer        $position The position in the bottom most array.
     * @param mixed          $value    The value to add to the array.
     * @return mixed[]
     */
    private static function insertIntoArray(array $array, int|string $key, int $position, mixed $value): array
    {
        // array_splice doesn't maintain integer keys, so do it manually instead
        $before = [];
        $after = [];
        $count = 0;
        foreach ($array as $index => $existingValue) {
            $count < $position
                ? $before[$index] = $existingValue
                : $after[$index] = $existingValue;
            $count++;
        }

        // array_merge doesn't maintain integer keys, so do it manually instead
        $return = [];
        foreach ([$before, [$key => $value], $after] as $partArray) {
            foreach ($partArray as $index => $value) {
                $return[$index] = $value;
            }
        }
        return $return;
    }

    /**
     * Pick the array key that's in a certain position.
     *
     * @param mixed[] $array    The array to inspect.
     * @param integer $position The position to pick from.
     * @return string|integer|null
     */
    private static function getKeyAtPos(array $array, int $position): string|int|null
    {
        $count = 0;
        foreach (array_keys($array) as $key) {
            if ($count == $position) {
                return $key;
            }
            $count++;
        }
        return null;
    }
}
