<?php

namespace CodeDistortion\JsonDiff;

use ArrayAccess;
use ArrayObject;
use Carbon\CarbonInterface;
use CodeDistortion\ArrayObjectExtended\ArrayObjectExtended;
use CodeDistortion\JsonDiff\Exceptions\JsonDiffException;
use CodeDistortion\JsonDiff\Support\Support;
use Countable;
use IteratorAggregate;
use Serializable;

/**
 * Keep track of some data, and its changes over time.
 *
 * @codingStandardsIgnoreStart
 *
 * @template TKey of integer|string
 * @template TValue of scalar|mixed[]
 * @template-implements IteratorAggregate<TKey, TValue>
 * @template-implements ArrayAccess<TKey, TValue>
 *
 * @codingStandardsIgnoreEnd
 */
class JsonHistory extends ArrayObjectExtended implements IteratorAggregate, ArrayAccess, Serializable, Countable
{
    /** @var array<TKey, mixed[]> The encoded snapshots, encoded with deltas. */
    private array $encodedSnapshots = [];

    /** @var boolean Whether $encodedSnapshots needs to be re-calculated or not. */
    private bool $encodedSnapshotsAreCached = false;



    /**
     * Build a new JsonHistory object based on a set of snapshots.
     *
     * @param array<TKey, TValue>|null $snapshots The encoded snapshots to build a new object from.
     * @return self<TKey, TValue>
     */
    public static function new(?array $snapshots = []): self
    {
        /** @var self<TKey, TValue> $return */
        $return = new self($snapshots ?? []);
        return $return;
    }

    /**
     * Build a new JsonHistory object based on a set of encoded-snapshots..
     *
     * @param array<TKey, mixed[]>|null $encodedSnapshots The encoded-snapshots to build a new object from.
     * @return self<TKey, TValue>
     * @throws JsonDiffException When the encoded snapshot data is invalid.
     */
    public static function fromEncodedSnapshots(?array $encodedSnapshots = []): self
    {
        $encodedSnapshots ??= [];

        $snapshots = self::buildSnapshotsFromEncodedSnapshots($encodedSnapshots);

        /** @var self<TKey, TValue> $new */
        $new = new self($snapshots);

        $new->encodedSnapshots = $encodedSnapshots;
        // @ infection-ignore-all - TrueValue - alters whether the cache is used, but doesn't affect the outcome
        $new->encodedSnapshotsAreCached = true;

        return $new;
    }



    /**
     * Build snapshots from encoded-snapshots.
     *
     * @param array<TKey, array<integer, mixed[]>> $encodedSnapshots Previously encoded snapshot data.
     * @return array<TKey, TValue>
     * @throws JsonDiffException When the encoded snapshot data is invalid.
     */
    private static function buildSnapshotsFromEncodedSnapshots(array $encodedSnapshots): array
    {
        # $encodedSnapshots = [];
        # $encodedSnapshots[key-1] = initial snapshot data
        # $encodedSnapshots[key-2] = delta changes
        # $encodedSnapshots[key-3] = delta changes
        # … etc

        $firstIteration = true;
        $prevData = null;
        $snapshots = [];
        foreach ($encodedSnapshots as $index => $item) {

            if ($firstIteration) {
                // use the data as is
                $prevData = $item;
                $firstIteration = false;
            } else {
                // treat the value as delta data
                $delta = new JsonDelta($item);
                /** @var TValue $prevData */
                $prevData = JsonDiff::applyDelta($prevData, $delta);
            }
            $snapshots[$index] = $prevData;
        }

        return $snapshots;
    }

    /**
     * Add a snapshot of the data, at a certain point in time.
     *
     * When the key is null, the next one is chosen. If a key is re-used, it will replace the original.
     *
     * @param TValue                              $data The data to record.
     * @param CarbonInterface|integer|string|null $key  The key to use. When a Carbon, its timestamp is used.
     * @return void
     */
    public function addSnapshot(mixed $data, CarbonInterface|int|string|null $key = null): void
    {
        $key = $this->resolveKey($key);

        !is_null($key)
            ? $this[$key] = $data
            : $this[] = $data;
    }

    /**
     * Sets the value at the specified index to newval.
     *
     * @link https://php.net/manual/en/arrayobject.offsetset.php
     *
     * @param TKey   $key   The index being set.
     * @param TValue $value The new value for the index.
     * @return void
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        Support::ensureDataIsValid($value);

        parent::offsetSet($key, $value);
    }

    /**
     * Get all the snapshot data.
     *
     * @return array<TKey, TValue>
     */
    public function getSnapshots(): array
    {
        // remove snapshots that don't have changes, which might affect which key is last
        $this->getEncodedSnapshots();

        /** @var array<TKey, TValue> $return */
        $return = $this->getArrayCopy();
        return $return;
    }

    /**
     * Get the latest snapshot.
     *
     * @return TValue|null
     */
    public function getLatestSnapshot(): ?array
    {
//        // remove snapshots that don't have changes, which might affect which key is last
//        $this->getEncodedSnapshots();

        return $this->count()
            ? $this[$this->keyLast()]
            : null;
    }

    /**
     * Generate an encoded version of the snapshots (containing deltas, instead of all the data to save space) for
     * storing.
     *
     * @return array<TKey, mixed[]>
     */
    public function getEncodedSnapshots(): array
    {
        if ($this->encodedSnapshotsAreCached) {
            return $this->encodedSnapshots;
        }

        # $encodedSnapshots = [];
        # $encodedSnapshots[key-1] = initial snapshot data
        # $encodedSnapshots[key-2] = delta changes
        # $encodedSnapshots[key-3] = delta changes
        # … etc

        $encodedSnapshots = [];
        $firstIteration = true;
        $prevData = null;
        foreach ($this->keys() as $key) {

            if ($firstIteration) {
                // use the value as-is
                $encodedSnapshots[$key] = $this[$key];
                $prevData = $this[$key];
                $firstIteration = false;
            } else {
                // generate the delta data
                $delta = JsonDiff::compare($prevData, $this[$key]);

                if ($delta->hasAlterations()) {
                    $encodedSnapshots[$key] = $delta->getJournal();
                    $prevData = $this[$key];
                } else {
                    // there were no changes, so don't keep this snapshot
                    unset($this[$key]);
                }
            }
        }

        $this->encodedSnapshots = $encodedSnapshots;
        // @ infection-ignore-all - TrueValue - alters whether the cache is used, but doesn't affect the outcome
        $this->encodedSnapshotsAreCached = true;

        return $this->encodedSnapshots;
    }





    /**
     * Resolve which key should be used.
     *
     * @param mixed $key The intended key to use.
     * @return TKey|null
     */
    private function resolveKey(mixed $key)
    {
        if (is_int($key)) {
            return $key;
        }

        if (is_string($key)) {
            return $key;
        }

        if ($key instanceof CarbonInterface) {
            return $key->format('U.u');
        }

        return null;
    }





    /**
     * A hook that's called when the contents of this object has changed.
     *
     * The snapshots have changed, reset any caches.
     *
     * @return void
     */
    protected function onAfterUpdate(): void
    {
//        // - ksort on <= 8.1 doesn't give correct results when both numbers and letters are used as keys e.g.
//        // $a = ['j' => '', 0 => '', 1 => ''];
//        // $b = [0 => '', 1 => '', 'j' => ''];
//        // ksort($a);
//        // ksort($b);
//        // dd($a, $b);
//
//        // ksort the array manually
//
//        // sort the array
//        $keys = $this->keys();
//        sort($keys);
//
//        $sorted = [];
//        foreach ($keys as $key) {
//            $sorted[$key] = $this[$key];
//        }
//
//        // use the ArrayObject exchangeArray() method directly,
//        // to avoid onAfterUpdate() being called (causes recursion)
//        ArrayObject::exchangeArray($sorted);



        // use the ArrayObject exchangeArray() method directly,
        // to avoid onAfterUpdate() being called (causes recursion)
        ArrayObject::ksort();

        $this->encodedSnapshotsAreCached = false;
    }
}
