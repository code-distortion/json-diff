<?php

namespace CodeDistortion\JsonDiff\Tests\Unit\Support;

use Carbon\Carbon;
use CodeDistortion\JsonDiff\Exceptions\JsonDiffException;
use CodeDistortion\JsonDiff\Support\Support;
use CodeDistortion\JsonDiff\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

/**
 * Test the Support class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class SupportTest extends PHPUnitTestCase
{
    /**
     * Check that data is validated properly.
     *
     * @param mixed   $value           The value to check.
     * @param boolean $expectException Whether an exception is expected.
     * @return void
     */
    #[Test]
    #[DataProvider('dataValidationDataProvider')]
    public static function test_data_validation(mixed $value, bool $expectException): void
    {
        $caughtException = false;
        try {
            Support::ensureDataIsValid($value);
        } catch (JsonDiffException $e) {
            $caughtException = true;
        }
        self::assertSame($expectException, $caughtException);
    }

    /**
     * DataProvider for test_data_validation().
     *
     * @return array<array-key, array{value: mixed, expectException: boolean}>
     */
    public static function dataValidationDataProvider(): array
    {
        return [
            ['value' => 1, 'expectException' => false],
            ['value' => 1.01, 'expectException' => false],
            ['value' => 'a', 'expectException' => false],
            ['value' => true, 'expectException' => false],
            ['value' => null, 'expectException' => false],
            ['value' => [], 'expectException' => false],
            ['value' => ['a' => 'b'], 'expectException' => false],
            ['value' => new stdClass(), 'expectException' => true],
            ['value' => Carbon::now(), 'expectException' => true],
            ['value' => fn(): string => 'a', 'expectException' => true],
        ];
    }



    /**
     * Check that scalar values are detected properly.
     *
     * @return void
     */
    #[Test]
    public static function test_for_scalar_values(): void
    {
        self::assertTrue(Support::isScalar(1));
        self::assertTrue(Support::isScalar(1.01));
        self::assertTrue(Support::isScalar('a'));
        self::assertTrue(Support::isScalar(true));
        self::assertTrue(Support::isScalar(null));
        self::assertFalse(Support::isScalar([]));
        self::assertFalse(Support::isScalar(['a' => 'b']));
        self::assertFalse(Support::isScalar(new stdClass()));
        self::assertFalse(Support::isScalar(fn(): string => 'a'));
    }
}
