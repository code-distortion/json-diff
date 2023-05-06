<?php

namespace CodeDistortion\JsonDiff\Tests\Unit;

use CodeDistortion\JsonDiff\Exceptions\JsonDiffException;
use CodeDistortion\JsonDiff\JsonDelta;
use CodeDistortion\JsonDiff\JsonDiff;
use CodeDistortion\JsonDiff\Tests\PHPUnitTestCase;
use stdClass;
use Throwable;

/**
 * Test the JsonDiff data comparison and transformation functionality.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class JsonDiffTest extends PHPUnitTestCase
{
    /**
     * Pass combinations of data to JsonDiff.
     *
     * @test
     * @dataProvider JsonDiffDataProvider
     *
     * @param mixed $data1 The initial data to use.
     * @param mixed $data2 The next data to use.
     * @return void
     * @throws Throwable When a test fails.
     */
    public static function test_that_json_diff_produces_comparisons_and_transformations(
        mixed $data1,
        mixed $data2
    ): void {

        $delta = JsonDiff::compare($data1, $data2);

        try {
            // check the transformations work properly
            self::assertSame($data2, JsonDiff::applyDelta($data1, $delta));
            self::assertSame($data1, JsonDiff::undoDelta($data2, $delta));

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
     * Data provider for test_that_json_diff_produces_comparisons_and_transformations
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
            4 => $maxDepth > 0 && (mt_rand(1, 5) != 1)
                ? self::randomArrayWithIntegerKeys($maxDepth - 1)
                : self::createComparisonData($maxDepth),
            5 => $maxDepth > 0 && (mt_rand(1, 5) != 1)
                ? self::randomArrayWithStringKeys($maxDepth - 1)
                : self::createComparisonData($maxDepth),
            6 => $maxDepth > 0 && (mt_rand(1, 5) != 1)
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
            $key = mt_rand(0, 1)
                ? mt_rand(0, 5)
                : self::randomChar('abcd');
            $array[$key] = self::createComparisonData($maxDepth);
        }
        return $array;
    }



    /**
     * Check that JsonDiff throws an exception when data is passed to it that it can't handle.
     *
     * @test
     * @dataProvider ExceptionGeneratingDataProvider
     *
     * @param stdClass|null $data1 The initial data to use.
     * @param stdClass|null $data2 The next data to use.
     * @return void
     */
    public static function test_that_exception_is_thrown_when_data_is_an_invalid_type(
        ?stdClass $data1,
        ?stdClass $data2
    ): void {

        $caughtException = false;
        try {
            JsonDiff::compare($data1, $data2);
        } catch (JsonDiffException) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);
    }

    /**
     * Data provider for test_that_json_diff_produces_comparisons_and_transformations
     *
     * @return array<integer, array<integer, stdClass|null>>
     */
    public static function ExceptionGeneratingDataProvider(): array
    {
        return [
            [new stdClass(), null],
            [null, new stdClass()],
            [new stdClass(), new stdClass()],
        ];
    }
}
