<?php

namespace CodeDistortion\JsonDiff\Exceptions;

use Exception;

/**
 * The exception for JsonDiff errors.
 */
class JsonDiffException extends Exception
{
    /**
     * Generated when some data (e.g. an object) cannot be handled.
     *
     * @return self
     */
    public static function dataTypeInvalid(): self
    {
        return new self('The data specified is of a format that cannot be handled by JsonDiff');
    }

    /**
     * Generated when a delta-journal isn't valid.
     *
     * @return self
     */
    public static function invalidDeltaJournal(): self
    {
        return new self('The delta-journal seems to be invalid');
    }

    /**
     * Generated when a non-zero position was specified for a non-array.
     *
     * @param integer $position The position that was specified.
     * @return self
     */
    public static function positionSpecifiedForNonArray(int $position): self
    {
        return new self("Cannot use non-zero position ($position) when updating a non-array");
    }
}
