<?php

namespace CodeDistortion\JsonDiff\Tests\Unit;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use CodeDistortion\JsonDiff\Exceptions\JsonDiffException;
use CodeDistortion\JsonDiff\JsonHistory;
use CodeDistortion\JsonDiff\Tests\PHPUnitTestCase;
use stdClass;

/**
 * Test the JsonHistory functionality.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class JsonHistoryTest extends PHPUnitTestCase
{
    /**
     * Check that JsonHistory accepts different types of values.
     *
     * @test
     *
     * @return void
     */
    public static function test_that_json_history_accepts_different_values(): void
    {
        // empty JsonHistory's

        // use the constructor, pass no data
        $history = new JsonHistory();
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());

        // use the constructor, pass an empty array
        $history = new JsonHistory([]);
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());



        // use the new() method, pass no data
        $history = JsonHistory::new();
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());

        // use the new() method, pass an empty array
        $history = JsonHistory::new([]);
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());

        // use the new() method, pass null
        $history = JsonHistory::new(null);
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());



        // use the fromEncodedSnapshots() method, pass no data
        $history = JsonHistory::fromEncodedSnapshots();
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());

        // use the fromEncodedSnapshots() method, pass an empty array
        $history = JsonHistory::fromEncodedSnapshots([]);
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());

        // use the fromEncodedSnapshots() method, pass null
        $history = JsonHistory::fromEncodedSnapshots(null);
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());




        // JsonHistory's with various types of data
        $data = [1, 1.01, 'a', ['b'], true];

        // populate using different sorts of values
        $history = new JsonHistory();
        $history->addSnapshot(1);
        $history->addSnapshot(1.01);
        $history->addSnapshot('a');
        $history->addSnapshot(['b']);
        $history->addSnapshot(true);
        self::assertSame($data, $history->getSnapshots());

        $encodedSnapshots = $history->getEncodedSnapshots();

        // populate using encoded snapshots
        $history2 = JsonHistory::fromEncodedSnapshots($encodedSnapshots);
        self::assertSame($data, $history2->getSnapshots());

        // populate by passing the data when instantiating
        $history2 = new JsonHistory($data);
        self::assertSame($data, $history2->getSnapshots());

        // populate by passing data to the new() method
        $history2 = JsonHistory::new($data);
        self::assertSame($data, $history2->getSnapshots());



        // instantiate with invalid data
//        $exceptionThrown = false;
//        try {
//            $history = new JsonHistory([new stdClass()]);
//        } catch (JsonDiffException $e) {
//            $exceptionThrown = true;
//        }
//        self::assertTrue($exceptionThrown);

        // update with invalid data
        $exceptionThrown = false;
        try {
            $history = new JsonHistory();
            $history->addSnapshot(new stdClass());
        } catch (JsonDiffException $e) {
            $exceptionThrown = true;
        }
        self::assertTrue($exceptionThrown);
    }

    /**
     * Check that JsonHistory accepts different types of keys.
     *
     * @test
     *
     * @return void
     */
    public static function test_that_json_history_accepts_different_keys(): void
    {
        $intKey = time();
        $nowUTCMutable = Carbon::now('UTC')->microseconds(0);
        $nowUTCImmutable = CarbonImmutable::now('UTC')->microseconds(0);

        // populate using integer keys
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $intKey);
        $history->addSnapshot(['a' => '2'], $intKey + 1);
        $history->addSnapshot(['a' => '3'], $intKey + 2);
        self::assertSame(
            [$intKey, $intKey + 1, $intKey + 2],
            array_keys($history->getSnapshots())
        );

        // populate using string keys
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], 'a');
        $history->addSnapshot(['a' => '2'], 'b');
        $history->addSnapshot(['a' => '3'], 'c');
        self::assertSame(
            ['a', 'b', 'c'],
            array_keys($history->getSnapshots())
        );

        // populate by passing a Carbon for the keys
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $nowUTCMutable);
        $history->addSnapshot(['a' => '2'], $nowUTCMutable->copy()->addSecond());
        $history->addSnapshot(['a' => '3'], $nowUTCMutable->copy()->addSeconds(2));
        self::assertSame(
            [
                "$nowUTCMutable->timestamp.000000",
                ((int) $nowUTCMutable->timestamp + 1) . ".000000",
                ((int) $nowUTCMutable->timestamp + 2) . ".000000",
            ],
            array_keys($history->getSnapshots())
        );

        // populate by passing a CarbonImmutable for the keys
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $nowUTCImmutable);
        $history->addSnapshot(['a' => '2'], $nowUTCImmutable->addSecond());
        $history->addSnapshot(['a' => '3'], $nowUTCImmutable->addSeconds(2));
        self::assertSame(
            [
                "$nowUTCImmutable->timestamp.000000",
                ((int) $nowUTCImmutable->timestamp + 1) . ".000000",
                ((int) $nowUTCImmutable->timestamp + 2) . ".000000",
            ],
            array_keys($history->getSnapshots())
        );

        // don't pass keys
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1']);
        $history->addSnapshot(['a' => '2']);
        $history->addSnapshot(['a' => '3']);
        $encodedSnapshots = $history->getEncodedSnapshots();
        self::assertSame([0, 1, 2], array_keys($encodedSnapshots));

        // pass only the first key - as an integer
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], 10);
        $history->addSnapshot(['a' => '2']);
        $history->addSnapshot(['a' => '3']);
        $encodedSnapshots = $history->getEncodedSnapshots();
        self::assertSame([10, 11, 12], array_keys($encodedSnapshots));

        // pass only the first key - as a string
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], 'j');
        $history->addSnapshot(['a' => '2']);
        $history->addSnapshot(['a' => '3']);
        $encodedSnapshots = $history->getEncodedSnapshots();
        self::assertSame([0, 1, 'j'], array_keys($encodedSnapshots)); // is reordered automatically

        // pass only the first key - as a Carbon
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $nowUTCMutable);
        $history->addSnapshot(['a' => '2']);
        $history->addSnapshot(['a' => '3']);
        $encodedSnapshots = $history->getEncodedSnapshots();
        self::assertSame(
            [0, 1, "$nowUTCImmutable->timestamp.000000"], // is reordered automatically
            array_keys($encodedSnapshots)
        );
    }

    /**
     * Check that JsonHistory builds data history properly.
     *
     * @test
     *
     * @return void
     */
    public static function test_that_json_history_handles_changes_to_data(): void
    {
        $intKey = time();

        // populate with different combinations of values
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $intKey);
        $history->addSnapshot(['a' => '2'], $intKey + 1);
        $history->addSnapshot(['a' => '1'], $intKey + 2);
        self::assertSame(3, count($history->getSnapshots()));

        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $intKey);
        $history->addSnapshot(['a' => '2'], $intKey + 1);
        $history->addSnapshot(['a' => '2'], $intKey + 2);
        self::assertSame(2, count($history->getSnapshots()));



        // use the encoded snapshot values to populate a new JsonHistory object
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $intKey);
        $history->addSnapshot(['a' => '2'], $intKey + 1);
        $history->addSnapshot(['a' => '3'], $intKey + 2);
        $encodedSnapshots = $history->getEncodedSnapshots();

        $history2 = JsonHistory::fromEncodedSnapshots($encodedSnapshots);
        $history2->addSnapshot(['a' => '2'], $intKey + 1); // existing key (in the middle) but no change
        self::assertSame(['a' => '3'], $history2->getLatestSnapshot());
        self::assertSame($encodedSnapshots, $history2->getEncodedSnapshots());

        $history2 = JsonHistory::fromEncodedSnapshots($encodedSnapshots);
        $history2->addSnapshot(['a' => '4'], $intKey + 1); // existing key (in the middle) sand new value
        self::assertSame(['a' => '3'], $history2->getLatestSnapshot());
        self::assertNotSame($encodedSnapshots, $history2->getEncodedSnapshots());

        $history2 = JsonHistory::fromEncodedSnapshots($encodedSnapshots);
        $history2->addSnapshot(['a' => '3'], $intKey + 3); // new key but no change
        self::assertSame(['a' => '3'], $history2->getLatestSnapshot());
        self::assertSame($encodedSnapshots, $history2->getEncodedSnapshots());

        $history2 = JsonHistory::fromEncodedSnapshots($encodedSnapshots);
        $history2->addSnapshot(['a' => '4'], $intKey + 3); // new key and new value
        self::assertSame(['a' => '4'], $history2->getLatestSnapshot());
        self::assertNotSame($encodedSnapshots, $history2->getEncodedSnapshots());
    }

    /**
     * Check that JsonHistory, built in a different order gives the same result.
     *
     * @test
     *
     * @return void
     */
    public static function test_that_history_inserted_in_different_order_gives_the_same_result(): void
    {
        $nowUTC = CarbonImmutable::now('UTC');

        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $nowUTC);
        $history->addSnapshot(['a' => '2'], $nowUTC->addSecond());
        $history->addSnapshot(['a' => '3'], $nowUTC->addSeconds(2));

        $history2 = new JsonHistory();
        $history2->addSnapshot(['a' => '1'], $nowUTC); // different order
        $history2->addSnapshot(['a' => '3'], $nowUTC->addSeconds(2));
        $history2->addSnapshot(['a' => '2'], $nowUTC->addSecond());

        self::assertSame($history->getSnapshots(), $history2->getSnapshots());

        $history2 = new JsonHistory();
        $history2->addSnapshot(['a' => '3'], $nowUTC->addSeconds(2)); // different order
        $history2->addSnapshot(['a' => '2'], $nowUTC->addSecond());
        $history2->addSnapshot(['a' => '1'], $nowUTC);

        self::assertSame($history->getSnapshots(), $history2->getSnapshots());
    }
}
