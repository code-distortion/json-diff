<?php

namespace CodeDistortion\JsonDiff\Tests\Unit;

use CodeDistortion\JsonDiff\JsonDelta;
use CodeDistortion\JsonDiff\JsonDiff;
use CodeDistortion\JsonDiff\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

/**
 * Test that JsonDiff can produce and apply deltas.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class JsonDiffTest extends PHPUnitTestCase
{
    /**
     * Test that JsonDiff can produce comparisons.
     *
     * @return void
     */
    #[Test]
    public static function test_that_json_diff_can_produce_comparisons(): void
    {
        $data1 = ['a' => 'b'];
        $data2 = ['a' => 'B'];
        $expectedJournal = [
            [
                JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                JsonDelta::KEY_PATH => ['a'],
                JsonDelta::KEY_POSITION => 0,
                JsonDelta::KEY_ORIG_VALUE => 'b',
                JsonDelta::KEY_NEW_VALUE => 'B',
            ],
        ];
        $delta = JsonDiff::compare($data1, $data2);
        self::assertSame($expectedJournal, $delta->getJournal());
    }



    /**
     * Test that JsonDiff can apply deltas.
     *
     * @return void
     */
    public static function test_that_json_diff_can_apply_deltas(): void
    {
        $data1 = ['a' => 'b'];
        $data2 = ['a' => 'B'];
        $delta = JsonDiff::compare($data1, $data2);
        $appliedData = JsonDiff::applyDelta($data1, $delta);
        self::assertSame($data2, $appliedData);
    }



    /**
     * Test that JsonDiff can undo deltas.
     *
     * @return void
     */
    #[Test]
    public static function test_that_json_diff_can_undo_deltas(): void
    {
        $data1 = ['a' => 'b'];
        $data2 = ['a' => 'B'];
        $delta = JsonDiff::compare($data1, $data2);
        $undoneData = JsonDiff::undoDelta($data2, $delta);
        self::assertSame($data1, $undoneData);
    }
}
