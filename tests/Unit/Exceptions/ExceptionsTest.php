<?php

namespace CodeDistortion\JsonDiff\Tests\Unit\Exceptions;

use CodeDistortion\JsonDiff\Exceptions\JsonDiffException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test the Exceptions.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class ExceptionsTest extends TestCase
{
    /**
     * Test the JsonDiffException messages.
     *
     * @return void
     */
    #[Test]
    public function test_json_diff_exception_messages(): void
    {
        // test dataTypeInvalid()
        $exception = JsonDiffException::dataTypeInvalid();
        self::assertSame(
            'The data specified is of a format that cannot be handled by JsonDiff',
            $exception->getMessage()
        );

        // test invalidDeltaJournal()
        $exception = JsonDiffException::invalidDeltaJournal();
        self::assertSame(
            'The delta-journal seems to be invalid',
            $exception->getMessage()
        );

        // test positionSpecifiedForNonArray()
        $position = 5;
        $exception = JsonDiffException::positionSpecifiedForNonArray($position);
        self::assertSame(
            "Cannot use non-zero position ($position) when updating a non-array",
            $exception->getMessage()
        );
    }
}
