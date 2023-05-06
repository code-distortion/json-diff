<?php

namespace CodeDistortion\JsonDiff\Support;

use CodeDistortion\JsonDiff\Exceptions\JsonDiffException;

/**
 * Common methods, shared by this package.
 */
class Support
{
    /**
     * Check to make sure the given data is valid.
     *
     * @param mixed $data The data to check.
     * @return void
     * @throws JsonDiffException When the data is invalid.
     */
    public static function ensureDataIsValid(mixed $data): void
    {
        if (self::isScalar($data)) {
            return;
        }

        if (is_array($data)) {
            // don't bother checking inside the array. It's up to the developer to not pass objects inside arrays.
            return;
        }

        throw JsonDiffException::dataTypeInvalid();
    }

    /**
     * Check if a value is scalar (including null).
     *
     * @param mixed $value The value to check.
     * @return boolean
     */
    public static function isScalar(mixed $value): bool
    {
        if (is_scalar($value)) {
            return true;
        }
        if (is_null($value)) {
            return true;
        }
        return false;
    }
}
