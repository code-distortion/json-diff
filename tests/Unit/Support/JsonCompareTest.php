<?php

namespace CodeDistortion\JsonDiff\Tests\Unit;

use CodeDistortion\JsonDiff\Exceptions\JsonDiffException;
use CodeDistortion\JsonDiff\JsonDelta;
use CodeDistortion\JsonDiff\Support\JsonApply;
use CodeDistortion\JsonDiff\Support\JsonCompare;
use CodeDistortion\JsonDiff\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Throwable;

/**
 * Test the JsonCompare class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class JsonCompareTest extends PHPUnitTestCase
{
    /**
     * Check that JsonDiff throws an exception when data is passed to it that it can't handle.
     *
     * @param stdClass|null $data1           The initial data to use.
     * @param stdClass|null $data2           The next data to use.
     * @param boolean       $expectException Whether an exception is expected or not.
     * @return void
     */
    #[Test]
    #[DataProvider('ExceptionGeneratingDataProvider')]
    public static function test_that_json_compare_validates_inputs(
        ?stdClass $data1,
        ?stdClass $data2,
        bool $expectException,
    ): void {

        $caughtException = false;
        try {
            JsonCompare::compare($data1, $data2);
        } catch (JsonDiffException) {
            $caughtException = true;
        }
        self::assertSame($expectException, $caughtException);
    }

    /**
     * DataProvider for test_that_json_compare_validates_inputs().
     *
     * @return array<integer,array<string,boolean|stdClass|null>>
     */
    public static function ExceptionGeneratingDataProvider(): array
    {
        return [
            [
                'data1' => null,
                'data2' => null,
                'expectException' => false,
            ],
            [
                'data1' => new stdClass(),
                'data2' => null,
                'expectException' => true,
            ],
            [
                'data1' => null,
                'data2' => new stdClass(),
                'expectException' => true,
            ],
            [
                'data1' => new stdClass(),
                'data2' => new stdClass(),
                'expectException' => true,
            ],
        ];
    }



    /**
     * Check that the deltas that are generated are correct.
     *
     * @param mixed          $data1           The initial data to use.
     * @param mixed          $data2           The next data to use.
     * @param array<mixed[]> $expectedJournal The expected journal data.
     * @return void
     */
    #[Test]
    #[DataProvider('deltasDataProvider')]
    public static function test_the_deltas_that_are_generated(mixed $data1, mixed $data2, array $expectedJournal): void
    {
        $delta = JsonCompare::compare($data1, $data2);
        self::assertSame($expectedJournal, $delta->getJournal());
    }

    /**
     * DataProvider for test_the_deltas_that_are_generated().
     *
     * @return array<mixed[]>
     */
    public static function deltasDataProvider(): array
    {
        return [

            // no changes
            [
                'data1' => null,
                'data2' => null, // no changes
                'expectedJournal' => [],
            ],

            // whole value changed
            [
                'data1' => null,
                'data2' => 1, // original value changed
                'expectedJournal' => [
                    [
                        JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                        JsonDelta::KEY_PATH => [],
                        JsonDelta::KEY_POSITION => 0,
                        JsonDelta::KEY_ORIG_VALUE => null,
                        JsonDelta::KEY_NEW_VALUE => 1,
                    ],
                ],
            ],
            [
                'data1' => 1,
                'data2' => 1.23, // original value changed
                'expectedJournal' => [
                    [
                        JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                        JsonDelta::KEY_PATH => [],
                        JsonDelta::KEY_POSITION => 0,
                        JsonDelta::KEY_ORIG_VALUE => 1,
                        JsonDelta::KEY_NEW_VALUE => 1.23,
                    ],
                ],
            ],
            [
                'data1' => 1.23,
                'data2' => 'a', // original value changed
                'expectedJournal' => [
                    [
                        JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                        JsonDelta::KEY_PATH => [],
                        JsonDelta::KEY_POSITION => 0,
                        JsonDelta::KEY_ORIG_VALUE => 1.23,
                        JsonDelta::KEY_NEW_VALUE => 'a',
                    ],
                ],
            ],
            [
                'data1' => 'a',
                'data2' => true, // original value changed
                'expectedJournal' => [
                    [
                        JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                        JsonDelta::KEY_PATH => [],
                        JsonDelta::KEY_POSITION => 0,
                        JsonDelta::KEY_ORIG_VALUE => 'a',
                        JsonDelta::KEY_NEW_VALUE => true,
                    ],
                ],
            ],

            // nested values no changes
            [
                'data1' => ['a' => 'b'],
                'data2' => ['a' => 'b'], // no changes
                'expectedJournal' => [],
            ],

            // nested values changed / new
            [
                'data1' => null,
                'data2' => ['a' => ['d' => 'e']],
                'expectedJournal' => [
                    [
                        JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                        JsonDelta::KEY_PATH => [],
                        JsonDelta::KEY_POSITION => 0,
                        JsonDelta::KEY_ORIG_VALUE => null,
                        JsonDelta::KEY_NEW_VALUE => ['a' => ['d' => 'e']],
                    ],
                ],
            ],
            [
                'data1' => ['a' => 'b'],
                'data2' => ['a' => 'c'], // original value changed
                'expectedJournal' => [
                    [
                        JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                        JsonDelta::KEY_PATH => ['a'],
                        JsonDelta::KEY_POSITION => 0,
                        JsonDelta::KEY_ORIG_VALUE => 'b',
                        JsonDelta::KEY_NEW_VALUE => 'c',
                    ],
                ],
            ],
            [
                'data1' => ['a' => ['b' => 'c']],
                'data2' => ['a' => ['b' => 'd']], // original value changed
                'expectedJournal' => [
                    [
                        JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                        JsonDelta::KEY_PATH => ['a', 'b'],
                        JsonDelta::KEY_POSITION => 0,
                        JsonDelta::KEY_ORIG_VALUE => 'c',
                        JsonDelta::KEY_NEW_VALUE => 'd',
                    ],
                ],
            ],
            [
                'data1' => ['a' => ['b' => 'c']],
                'data2' => ['a' => ['d' => 'e']], // new key, old value removed
                'expectedJournal' => [
                    [
                        JsonDelta::KEY_TYPE => JsonDelta::TYPE_REMOVED,
                        JsonDelta::KEY_PATH => ['a', 'b'],
                        JsonDelta::KEY_POSITION => 0,
                        JsonDelta::KEY_ORIG_VALUE => 'c',
                        JsonDelta::KEY_NEW_VALUE => null,
                    ],
                    [
                        JsonDelta::KEY_TYPE => JsonDelta::TYPE_NEW,
                        JsonDelta::KEY_PATH => ['a', 'd'],
                        JsonDelta::KEY_POSITION => 0,
                        JsonDelta::KEY_ORIG_VALUE => null,
                        JsonDelta::KEY_NEW_VALUE => 'e',
                    ],
                ],
            ],
            [
                'data1' => ['a' => ['d' => 'e']],
                'data2' => null,
                'expectedJournal' => [
                    [
                        JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                        JsonDelta::KEY_PATH => [],
                        JsonDelta::KEY_POSITION => 0,
                        JsonDelta::KEY_ORIG_VALUE => ['a' => ['d' => 'e']],
                        JsonDelta::KEY_NEW_VALUE => null,
                    ],
                ],
            ],

            // nested values - new positions
            [
                'data1' => ['a' => 'b', 'c' => 'd'],
                'data2' => ['c' => 'd', 'a' => 'b'], // new positions
                'expectedJournal' => [
                    [
                        JsonDelta::KEY_TYPE => JsonDelta::TYPE_REMOVED,
                        JsonDelta::KEY_PATH => ['a'],
                        JsonDelta::KEY_POSITION => 0,
                        JsonDelta::KEY_ORIG_VALUE => 'b',
                        JsonDelta::KEY_NEW_VALUE => null,
                    ],
                    [
                        JsonDelta::KEY_TYPE => JsonDelta::TYPE_NEW,
                        JsonDelta::KEY_PATH => ['a'],
                        JsonDelta::KEY_POSITION => 1,
                        JsonDelta::KEY_ORIG_VALUE => null,
                        JsonDelta::KEY_NEW_VALUE => 'b',
                    ],
                ],
            ],

            // nested values - new positions
            [
                'data1' => ['a' => 'b', 'c' => 'd'],
                'data2' => ['c' => 'D', 'a' => 'B'], // new positions and values
                'expectedJournal' => [
                    [
                        JsonDelta::KEY_TYPE => JsonDelta::TYPE_REMOVED,
                        JsonDelta::KEY_PATH => ['a'],
                        JsonDelta::KEY_POSITION => 0,
                        JsonDelta::KEY_ORIG_VALUE => 'b',
                        JsonDelta::KEY_NEW_VALUE => null,
                    ],
                    [
                        JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                        JsonDelta::KEY_PATH => ['c'],
                        JsonDelta::KEY_POSITION => 0,
                        JsonDelta::KEY_ORIG_VALUE => 'd',
                        JsonDelta::KEY_NEW_VALUE => 'D',
                    ],
                    [
                        JsonDelta::KEY_TYPE => JsonDelta::TYPE_NEW,
                        JsonDelta::KEY_PATH => ['a'],
                        JsonDelta::KEY_POSITION => 1,
                        JsonDelta::KEY_ORIG_VALUE => null,
                        JsonDelta::KEY_NEW_VALUE => 'B',
                    ],
                ],
            ],
        ];
    }



    /**
     * Test that JsonCompare generates JsonDeltas properly.
     *
     * @param mixed $data1 The initial data to use.
     * @param mixed $data2 The next data to use.
     * @return void
     * @throws Throwable When a test fails.
     */
    #[Test]
    #[DataProvider('JsonDiffDataProvider')]
    public static function test_that_json_diff_produces_comparisons_and_transformations(
        mixed $data1,
        mixed $data2
    ): void {

        $delta = JsonCompare::compare($data1, $data2);

        self::assertInstanceOf(JsonDelta::class, $delta);

        try {
            // check the transformations work properly
            self::assertSame($data2, JsonApply::applyDelta($data1, $delta));
            self::assertSame($data1, JsonApply::undoDelta($data2, $delta));

            // make sure the JsonDelta understands if it has changes or not
            if ($data1 === $data2) {
                self::assertFalse($delta->hasAlterations());
                self::assertTrue($delta->doesntHaveAlterations());
            } else {
                self::assertTrue($delta->hasAlterations());
                self::assertFalse($delta->doesntHaveAlterations());
            }

            // make sure the Delta is serialised, and instantiated from the serialised version
            $deltaJournal = $delta->getJournal();
            $newDelta = new JsonDelta($deltaJournal);
            self::assertSame($deltaJournal, $newDelta->getJournal());

        } catch (Throwable $e) {
//            dump("These values didn't work", serialize($data1), serialize($data2));
            throw $e;
        }
    }

    /**
     * DataProvider for test_that_json_diff_produces_comparisons_and_transformations().
     *
     * @return array<integer, mixed[]>
     */
    public static function JsonDiffDataProvider(): array
    {
        $valuesToCombine = [
            null,
            '',
            1,
            2,
            'a',
            'b',
            [null],
            [''],
            [1],
            [2],
            ['a'],
            ['b'],
            [null, 'a'],
            ['a', null],
            ['a', 'b'],
            ['a' => 'a'],
            ['b' => 'b'],
            ['a' => 'b'],
            [5 => 'a'],
            [6 => 'a'],
            ['a' => ['A', 'B']],
            ['b' => ['A', 'B']],
            ['a' => ['B', 'B']],
        ];

        // generate every combination of these values
        $return = [];
        foreach ($valuesToCombine as $data1) {
            foreach ($valuesToCombine as $data2) {
                $return[] = ['data1' => $data1, 'data2' => $data2];
            }
        }

        // generate random values to compare
        for ($count = 0; $count < 1000; $count++) {
            $return[] = [self::createComparisonData(), self::createComparisonData()];
        }

        // hard-code a particular pair, to test this combination in particular
//        $return = [];
//        $return[] = [
//            'data1' => unserialize('N;'),
//            'data2' => unserialize('a:1:{i:0;N;}'),
//        ];
//        $return[] = [
//            'data1' => ['a' => ['b']],
//            'data2' => ['a' => 'z'],
//        ];

        return $return;
    }

    /**
     * Generate a random input value for comparison.
     *
     * @param integer $maxDepth The maximum depth of arrays to create.
     * @return string|integer|array<integer|string, mixed>|null
     */
    private static function createComparisonData(int $maxDepth = 3): string|int|array|null
    {
        return match (mt_rand(1, 7)) {
            1 => '',
            2 => mt_rand(0, 9),
            3 => self::randomChar('abcdef'),
            4 => $maxDepth > 0 && (mt_rand(1, 5) !== 1)
                ? self::randomArrayWithIntegerKeys($maxDepth - 1)
                : self::createComparisonData($maxDepth),
            5 => $maxDepth > 0 && (mt_rand(1, 5) !== 1)
                ? self::randomArrayWithStringKeys($maxDepth - 1)
                : self::createComparisonData($maxDepth),
            6 => $maxDepth > 0 && (mt_rand(1, 5) !== 1)
                ? self::randomArrayWithMixedKeys($maxDepth - 1)
                : self::createComparisonData($maxDepth),
            default => null,
        };
    }

    /**
     * Generate a random character.
     *
     * @param string $availableChars The characters to choose from.
     * @return string
     */
    private static function randomChar(string $availableChars = 'abcdefghijklmnopqrstuvwxyz'): string
    {
        return $availableChars[mt_rand(0, strlen($availableChars) - 1)];
    }

    /**
     * Generate an array of random values, with integer keys.
     *
     * @param integer $maxDepth The maximum depth of arrays to create.
     * @return array<integer, mixed>
     */
    private static function randomArrayWithIntegerKeys(int $maxDepth): array
    {
        $maxLength = mt_rand(0, 3);
        $array = [];
        for ($count = 0; $count < $maxLength; $count++) {
            $array[] = self::createComparisonData($maxDepth);
        }
        return $array;
    }

    /**
     * Generate an array of random values, with string keys.
     *
     * @param integer $maxDepth The maximum depth of arrays to create.
     * @return array<string, mixed>
     */
    private static function randomArrayWithStringKeys(int $maxDepth): array
    {
        $maxLength = mt_rand(0, 3);
        $array = [];
        for ($count = 0; $count < $maxLength; $count++) {
            $key = self::randomChar('abcd');
            $array[$key] = self::createComparisonData($maxDepth);
        }
        return $array;
    }

    /**
     * Generate an array of random values, with mixed (integer and string) keys.
     *
     * @param integer $maxDepth The maximum depth of arrays to create.
     * @return array<integer|string, mixed>
     */
    private static function randomArrayWithMixedKeys(int $maxDepth): array
    {
        $maxLength = mt_rand(0, 3);
        $array = [];
        for ($count = 0; $count < $maxLength; $count++) {
            $key = (mt_rand(0, 1) !== 0)
                ? mt_rand(0, 5)
                : self::randomChar('abcd');
            $array[$key] = self::createComparisonData($maxDepth);
        }
        return $array;
    }
}
