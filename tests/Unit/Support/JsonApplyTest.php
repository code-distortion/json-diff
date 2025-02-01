<?php

namespace CodeDistortion\JsonDiff\Tests\Unit;

use CodeDistortion\JsonDiff\JsonDelta;
use CodeDistortion\JsonDiff\Support\JsonApply;
use CodeDistortion\JsonDiff\Support\JsonCompare;
use CodeDistortion\JsonDiff\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test the JsonApply class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class JsonApplyTest extends PHPUnitTestCase
{
    /**
     * Test that applyDelta() works.
     *
     * @param mixed $data1 The original data.
     * @param mixed $data2 The data to change to.
     * @return void
     */
    #[Test]
    #[DataProvider('deltaDataProvider')]
    public static function test_that_apply_delta_works(mixed $data1, mixed $data2): void
    {
        $delta = JsonCompare::compare($data1, $data2);
        $result = JsonApply::applyDelta($data1, $delta);
        self::assertSame($data2, $result);
    }

    /**
     * Test that undoDelta() works.
     *
     * @param mixed $data1 The original data.
     * @param mixed $data2 The final data to come back from.
     * @return void
     */
    #[Test]
    #[DataProvider('deltaDataProvider')]
    public static function test_that_undo_delta_works(mixed $data1, mixed $data2): void
    {
        $delta = JsonCompare::compare($data1, $data2);
        $result = JsonApply::undoDelta($data2, $delta);
        self::assertSame($data1, $result);
    }

    /**
     * Provide data for the test_that_apply_delta_works() method.
     *
     * @return array<string,array<string,array<string,array<string,string>|integer|string>|string>>
     */
    public static function deltaDataProvider(): array
    {
        return [

            // adding values

            'deeper array - added value' => [
                'data1' => [
                    'name' => 'John',
                    'age' => 30,
                    'address' => [
                        'street' => '123 Main St',
                        'city' => 'Anytown',
                        'state' => 'CA',
                        'zip' => '12345',
                    ],
                ],
                'data2' => [
                    'name' => 'John',
                    'age' => 30,
                    'address' => [
                        'street' => '123 Main St',
                        'city' => 'Anytown',
                        'state' => 'CA',
                        'zip' => '12345',
                        'country' => 'USA',
                    ],
                ],
            ],

            // updating values

            'scalar - updated value' => [
                'data1' => 'John',
                'data2' => 'Jane',
            ],

            'scalar to array - updated value' => [
                'data1' => 'John',
                'data2' => [
                    'name' => 'Jane',
                    'age' => 30,
                ],
            ],

            'array to scalar - updated value' => [
                'data1' => [
                    'name' => 'John',
                    'age' => 30,
                ],
                'data2' => 'Jane',
            ],

            'array - updated value' => [
                'data1' => [
                    'name' => 'John',
                    'age' => 30,
                ],
                'data2' => [
                    'name' => 'Jane',
                    'age' => 30,
                ],
            ],

            'deeper array - updated value' => [
                'data1' => [
                    'name' => 'John',
                    'age' => 30,
                    'address' => [
                        'street' => '123 Main St',
                        'city' => 'Anytown',
                        'state' => 'CA',
                        'zip' => '12345',
                    ],
                ],
                'data2' => [
                    'name' => 'John',
                    'age' => 30,
                    'address' => [
                        'street' => '987 Quiet Rd',
                        'city' => 'Anytown',
                        'state' => 'CA',
                        'zip' => '12345',
                    ],
                ],
            ],

            // removing values

            'deeper array - removed value' => [
                'data1' => [
                    'name' => 'John',
                    'age' => 30,
                    'address' => [
                        'street' => '123 Main St',
                        'city' => 'Anytown',
                        'state' => 'CA',
                        'zip' => '12345',
                    ],
                ],
                'data2' => [
                    'name' => 'John',
                    'age' => 30,
                    'address' => [
                        'street' => '123 Main St',
                        'city' => 'Anytown',
                        'state' => 'CA',
                    ],
                ],
            ],

        ];
    }



    /**
     * Test that applyDelta() works.
     *
     * @param mixed     $data1 The original data.
     * @param mixed     $data2 The data to change to.
     * @param JsonDelta $delta The delta to apply.
     * @return void
     */
    #[Test]
    #[DataProvider('specialCaseDeltaApplyDataProvider')]
    public static function test_special_case_apply_delta(mixed $data1, mixed $data2, JsonDelta $delta): void
    {
        // $delta = JsonCompare::compare($data1, $data2);
        // dump($delta);
        $result = JsonApply::applyDelta($data1, $delta);
        self::assertSame($data2, $result);
    }

    /**
     * Provide data for the test_that_apply_delta_works() method.
     *
     * @return array<string,array<string,array<string,array<integer|string,string>|string>|JsonDelta|null>>
     */
    public static function specialCaseDeltaApplyDataProvider(): array
    {
        return [

            // special cases that don't occur in normal situations

            // this wouldn't be generated by JsonCompare, as it would have been an "updated value"
            "New value, but the original data was null" => [
                'data1' => null,
                'data2' => ['name' => 'John'],
                'delta' => (function () {
                    $delta = new JsonDelta();
                    $delta->recordNewValue(['name'], 'John', 0);
                    return $delta;
                })(),
            ],

            // this wouldn't be generated by JsonCompare, as the key is null
            "New value, with a null key" => [
                'data1' => [],
                'data2' => ['' => 'John'],
                'delta' => (function () {
                    $delta = new JsonDelta();
                    $delta->recordNewValue([null], 'John', 0);
                    return $delta;
                })(),
            ],

            // this wouldn't be generated by JsonCompare, as the higher level key (one) doesn't exist.
            // JsonCompare would generate a "new value" at the higher level instead.
            "New value, with a key that doesn't exist" => [
                'data1' => [],
                'data2' => ['one' => ['two' => 'John']],
                'delta' => (function () {
                    $delta = new JsonDelta();
                    $delta->recordNewValue(['one', 'two'], 'John', 0);
                    return $delta;
                })(),
            ],

            // this wouldn't be generated by JsonCompare, as it would be split into 3 steps:
            // - "three" would be removed,
            // - "two" would be added,
            // - "three" would be added after "two".
            "New value, in the midst of an array" => [
                'data1' => ['numbers' => [1 => 'one', 3 => 'three']],
                'data2' => ['numbers' => [1 => 'one', 2 => 'two', 3 => 'three']],
                'delta' => (function () {
                    $delta = new JsonDelta();
                    $delta->recordNewValue(['numbers', 2], 'two', 1);
                    return $delta;
                })(),
            ],



            // this wouldn't be generated by JsonCompare, as the position being updated doesn't exist.
            "Update value, but the original value is scalar" => [
                'data1' => null,
                'data2' => ['one' => ['two' => 'three']],
                'delta' => (function () {
                    $delta = new JsonDelta();
                    $delta->recordChangedValue(['one', 'two'], "didn't exist", 'three', 0);
                    return $delta;
                })(),
            ],



            // this wouldn't be generated by JsonCompare, as the position being updated doesn't exist.
            "Remove value, but the original value is scalar" => [
                'data1' => null,
                'data2' => null, // no change
                'delta' => (function () {
                    $delta = new JsonDelta();
                    $delta->recordRemovedValue(['one'], "didn't exist", 0);
                    return $delta;
                })(),
            ],

            // this wouldn't be generated by JsonCompare, as the position being updated doesn't exist.
            "Remove value, with a null key" => [
                'data1' => [],
                'data2' => [],
                'delta' => (function () {
                    $delta = new JsonDelta();
                    $delta->recordRemovedValue([null], "didn't exist", 0);
                    return $delta;
                })(),
            ],

            // this wouldn't be generated by JsonCompare, as the position being updated doesn't exist.
            "Remove value, " => [
                'data1' => [],
                'data2' => [],
                'delta' => (function () {
                    $delta = new JsonDelta();
                    $delta->recordRemovedValue(['', ''], "didn't exist", 0);
                    return $delta;
                })(),
            ],

        ];
    }
}
