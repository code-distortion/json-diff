<?php

namespace CodeDistortion\JsonDiff\Tests\Unit;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use CodeDistortion\JsonDiff\Exceptions\JsonDiffException;
use CodeDistortion\JsonDiff\JsonHistory;
use CodeDistortion\JsonDiff\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use stdClass;

/**
 * Test the JsonHistory class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class JsonHistoryTest extends PHPUnitTestCase
{
    /**
     * Check that JsonHistory accepts different types of values.
     *
     * @return void
     */
    #[Test]
    public static function test_that_json_history_accepts_different_values(): void
    {
        $data = [1, 1.01, 'a', ['b'], true];



        // empty JsonHistory's

        // use the constructor, pass no data
        $history = new JsonHistory();
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());

        // use the constructor, pass an empty array
        $history = new JsonHistory([]);
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());

        // use the constructor, pass an array of data
        $history = new JsonHistory($data);
        self::assertSame($data, $history->getSnapshots());



        // use the new() method, pass no data
        $history = JsonHistory::new();
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());

        // use the new() method, pass null
        $history = JsonHistory::new(null);
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());

        // use the new() method, pass an empty array
        $history = JsonHistory::new([]);
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());

        // use the new() method, pass an array of data
        $history = JsonHistory::new($data);
        self::assertSame($data, $history->getSnapshots());



        // use the fromEncodedSnapshots() method, pass no data
        $history = JsonHistory::fromEncodedSnapshots();
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());

        // use the fromEncodedSnapshots() method, pass null
        $history = JsonHistory::fromEncodedSnapshots(null);
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());

        // use the fromEncodedSnapshots() method, pass an empty array
        $history = JsonHistory::fromEncodedSnapshots([]);
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());



        // build a JsonHistory object, so we can instantiate a new JsonHistory object based on it
        $history = new JsonHistory();
        $history->addSnapshot(1);
        $history->addSnapshot(1.01);
        $history->addSnapshot('a');
        $history->addSnapshot(['b']);
        $history->addSnapshot(true);
        self::assertSame($data, $history->getSnapshots());

        // use the new() method, pass a JsonHistory instance
        $history2 = JsonHistory::new($history);
        // JsonHistory should have COPIED the state from $history, instead of creating a fresh instance
        // check to see if the encodedSnapshotsAreCached property is true, indicating that the state was
        // copied from the $history instance
        $reflectionClass = new ReflectionClass($history2);
        $encodedSnapshotsAreCachedProp = $reflectionClass->getProperty('encodedSnapshotsAreCached');
        $encodedSnapshotsAreCachedProp->setAccessible(true);
        self::assertTrue($encodedSnapshotsAreCachedProp->getValue($history2));
        self::assertSame($data, $history2->getSnapshots());

        // populate using encoded snapshots
        $encodedSnapshots = $history->getEncodedSnapshots();
        $history2 = JsonHistory::fromEncodedSnapshots($encodedSnapshots);
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
            $history->addSnapshot(new stdClass()); // @phpstan-ignore-line
        } catch (JsonDiffException $e) {
            $exceptionThrown = true;
        }
        self::assertTrue($exceptionThrown);
    }

    /**
     * Check that JsonHistory accepts different types of keys.
     *
     * @return void
     */
    #[Test]
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
     * This includes:
     * - adding a new value,
     * - updating with the same value
     * - updating with a different value
     *
     * @return void
     */
    #[Test]
    public static function test_that_json_history_builds_data_history_properly(): void
    {
        $intKey = time();

        // no snapshots
        $history = new JsonHistory();
        self::assertNull($history->getLatestSnapshot());
        self::assertSame([], $history->getSnapshots());



        // create and update with a new value, then update with a new value (back to the original value)
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $intKey);
        $history->addSnapshot(['a' => '2'], $intKey + 1);
        $history->addSnapshot(['a' => '1'], $intKey + 2);
        self::assertSame(3, count($history->getSnapshots()));

        // create and update with a new value, then update with the same value
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $intKey);
        $history->addSnapshot(['a' => '2'], $intKey + 1);
        $history->addSnapshot(['a' => '2'], $intKey + 2);
        self::assertSame(2, count($history->getSnapshots()));



        // prepare a JsonHistory object with some data so we can use its encoded snapshot values below
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $intKey);
        $history->addSnapshot(['a' => '2'], $intKey + 1);
        $history->addSnapshot(['a' => '3'], $intKey + 2);
        $encodedSnapshots = $history->getEncodedSnapshots();

        // use the encoded snapshot values to populate a new JsonHistory object
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
     * Check that JsonHistory handles data that's overwritten.
     *
     * @return void
     */
    #[Test]
    public static function test_that_json_history_handles_data_being_overwritten(): void
    {
        $intKey = time();

        // set value once
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $intKey); // should set the value as it doesn't exist yet
        self::assertSame(1, count($history->getSnapshots()));
        self::assertSame(['a' => '1'], $history[$intKey]);

        // set value once - with allowOverride on
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $intKey, true); // should set the value as it doesn't exist yet
        self::assertSame(1, count($history->getSnapshots()));
        self::assertSame(['a' => '1'], $history[$intKey]);

        // set value once - with allowOverride off
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $intKey, false); // should set the value as it doesn't exist yet
        self::assertSame(1, count($history->getSnapshots()));
        self::assertSame(['a' => '1'], $history[$intKey]);

        // set value twice
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $intKey);
        $history->addSnapshot(['a' => '2'], $intKey); // should override the previous value
        self::assertSame(1, count($history->getSnapshots()));
        self::assertSame(['a' => '2'], $history[$intKey]);

        // set value twice - with allowOverride on
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $intKey);
        $history->addSnapshot(['a' => '2'], $intKey, true); // should override the previous value
        self::assertSame(1, count($history->getSnapshots()));
        self::assertSame(['a' => '2'], $history[$intKey]);

        // set value twice - with allowOverride off
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $intKey);
        $history->addSnapshot(['a' => '2'], $intKey, false); // should not override the previous value
        self::assertSame(1, count($history->getSnapshots()));
        self::assertSame(['a' => '1'], $history[$intKey]);
    }

    /**
     * Check that JsonHistory, built in a different order gives the same result.
     *
     * @return void
     */
    #[Test]
    public static function test_that_history_inserted_in_different_order_gives_the_same_result(): void
    {
        $nowUTC = CarbonImmutable::now('UTC');

        // in order
        $history = new JsonHistory();
        $history->addSnapshot(['a' => '1'], $nowUTC);
        $history->addSnapshot(['a' => '2'], $nowUTC->addSecond());
        $history->addSnapshot(['a' => '3'], $nowUTC->addSeconds(2));

        self::assertSame(['a' => '3'], $history->getLatestSnapshot());

        // out of order
        $history2 = new JsonHistory();
        $history2->addSnapshot(['a' => '1'], $nowUTC);
        $history2->addSnapshot(['a' => '3'], $nowUTC->addSeconds(2));
        $history2->addSnapshot(['a' => '2'], $nowUTC->addSecond());

        self::assertSame($history->getSnapshots(), $history2->getSnapshots());
        self::assertSame(['a' => '3'], $history2->getLatestSnapshot());

        // out of order - different order
        $history2 = new JsonHistory();
        $history2->addSnapshot(['a' => '3'], $nowUTC->addSeconds(2));
        $history2->addSnapshot(['a' => '2'], $nowUTC->addSecond());
        $history2->addSnapshot(['a' => '1'], $nowUTC);

        self::assertSame($history->getSnapshots(), $history2->getSnapshots());
        self::assertSame(['a' => '3'], $history2->getLatestSnapshot());
    }

    /**
     * Test that JsonHistory can check if a key exists.
     *
     * @return void
     */
    #[Test]
    public static function test_key_exists(): void
    {
        $carbon1 = CarbonImmutable::now('UTC');
        usleep(1); // make sure the Carbon objects are different by at least 1 microsecond
        $carbon2 = CarbonImmutable::now('UTC');

        $history = new JsonHistory();
        $history->addSnapshot(['a' => 'a']); // key 0
        $history->addSnapshot(['a' => 'b'], 2);
        $history->addSnapshot(['a' => 'c'], 'two');
        $history->addSnapshot(['a' => 'd'], $carbon1);

        self::assertTrue($history->offsetExists(0));
        self::assertTrue($history->offsetExists(2));
        self::assertTrue($history->offsetExists('two'));
        self::assertTrue($history->offsetExists($carbon1));
        self::assertFalse($history->offsetExists(3));
        self::assertFalse($history->offsetExists('three'));
        self::assertFalse($history->offsetExists($carbon2));

        // keyExists - synonym for offsetExists
        self::assertTrue($history->keyExists(0));
        self::assertTrue($history->keyExists(2));
        self::assertTrue($history->keyExists('two'));
        self::assertTrue($history->keyExists($carbon1));
        self::assertFalse($history->keyExists(3));
        self::assertFalse($history->keyExists('three'));
        self::assertFalse($history->keyExists($carbon2));
    }

    /**
     * Test the different sorts of keys.
     *
     * @return void
     */
    #[Test]
    public static function test_the_different_sorts_of_keys(): void
    {
        // integer keys
        self::assertSame(0, JsonHistory::resolveKey(0));
        self::assertSame(1, JsonHistory::resolveKey(1));
        self::assertSame(-1, JsonHistory::resolveKey(-1));

        // string keys
        self::assertSame('a', JsonHistory::resolveKey('a'));
        self::assertSame('b', JsonHistory::resolveKey('b'));
        self::assertSame('', JsonHistory::resolveKey(''));

        // carbon keys
        $carbon = Carbon::now('UTC');
        self::assertSame($carbon->format('U.u'), JsonHistory::resolveKey($carbon));
        $carbon = CarbonImmutable::now('UTC');
        self::assertSame($carbon->format('U.u'), JsonHistory::resolveKey($carbon));

        // others are not recognised
        self::assertNull(JsonHistory::resolveKey(null));
        self::assertNull(JsonHistory::resolveKey(1.0));
        self::assertNull(JsonHistory::resolveKey(true));
        self::assertNull(JsonHistory::resolveKey(false));
        self::assertNull(JsonHistory::resolveKey([]));
        self::assertNull(JsonHistory::resolveKey(new stdClass()));
    }
}
