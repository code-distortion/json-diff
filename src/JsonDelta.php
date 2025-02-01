<?php

namespace CodeDistortion\JsonDiff;

use CodeDistortion\JsonDiff\Exceptions\JsonDiffException;

/**
 * Class to represent changes between data structures.
 */
class JsonDelta
{
    /** @var list<mixed[]> The journal of changes. */
    private array $journal = [];

//    public const KEY_TYPE = 'type';
//    public const KEY_PATH = 'path';
//    public const KEY_POSITION = 'position';
//    public const KEY_ORIG_VALUE = 'orig-value';
//    public const KEY_NEW_VALUE = 'new-value';
//
//    public const TYPE_NEW = 'new';
//    public const TYPE_CHANGED = 'changed';
//    public const TYPE_REMOVED = 'removed';

    public const KEY_TYPE = 0;
    public const KEY_PATH = 1;
    public const KEY_POSITION = 2;
    public const KEY_ORIG_VALUE = 3;
    public const KEY_NEW_VALUE = 4;

    public const TYPE_NEW = 'n';
    public const TYPE_CHANGED = 'c';
    public const TYPE_REMOVED = 'r';



    /**
     * Build a new Delta object from a serialized journal.
     *
     * @param array<mixed[]> $journal The record of the changes.
     * @throws JsonDiffException When the journal is invalid.
     */
    public function __construct(array $journal = [])
    {
        if (!array_is_list($journal)) {
            throw JsonDiffException::invalidDeltaJournal();
        }

        foreach ($journal as $entry) {

            // validate the journal entries
            if (!array_key_exists(JsonDelta::KEY_TYPE, $entry)) {
                throw JsonDiffException::invalidDeltaJournal();
            }

            if (!array_key_exists(JsonDelta::KEY_PATH, $entry)) {
                throw JsonDiffException::invalidDeltaJournal();
            }

            if (!array_key_exists(JsonDelta::KEY_POSITION, $entry)) {
                throw JsonDiffException::invalidDeltaJournal();
            }

            $expectOrigValue = $expectNewValue = false;

            if ($entry[JsonDelta::KEY_TYPE] === self::TYPE_NEW) {
                $expectNewValue = true;
            } elseif ($entry[JsonDelta::KEY_TYPE] === self::TYPE_CHANGED) {
                $expectOrigValue = $expectNewValue = true;
            } elseif ($entry[JsonDelta::KEY_TYPE] === self::TYPE_REMOVED) {
                $expectOrigValue = true;
            }

            if ($expectOrigValue) {
                if (!array_key_exists(JsonDelta::KEY_ORIG_VALUE, $entry)) {
                    throw JsonDiffException::invalidDeltaJournal();
                }
            } else {
                // should not exist, or only exist with value null
                if (($entry[JsonDelta::KEY_ORIG_VALUE] ?? null) !== null) {
                    throw JsonDiffException::invalidDeltaJournal();
                }
            }

            if ($expectNewValue) {
                if (!array_key_exists(JsonDelta::KEY_NEW_VALUE, $entry)) {
                    throw JsonDiffException::invalidDeltaJournal();
                }
            } else {
                // should not exist, or only exist with value null
                if (($entry[JsonDelta::KEY_NEW_VALUE] ?? null) !== null) {
                    throw JsonDiffException::invalidDeltaJournal();
                }
            }
        }

        $this->journal = $journal;
    }

    /**
     * Record that a value is new.
     *
     * @param array<integer, string|integer> $path     The key-path of the value that's new.
     * @param mixed                          $value    The new value.
     * @param integer                        $position The position (when inside an array).
     * @return void
     */
    public function recordNewValue(array $path, mixed $value, int $position): void
    {
        $this->addJournalEntry(self::TYPE_NEW, $path, null, $value, $position);
    }

    /**
     * Record that a value changed.
     *
     * @param array<integer, string|integer> $path      The key-path of the value that changed.
     * @param mixed                          $origValue The original value.
     * @param mixed                          $newValue  The new value.
     * @param integer                        $position  The position (when inside an array).
     * @return void
     */
    public function recordChangedValue(array $path, mixed $origValue, mixed $newValue, int $position): void
    {
        $this->addJournalEntry(self::TYPE_CHANGED, $path, $origValue, $newValue, $position);
    }

    /**
     * Record that a value was removed.
     *
     * @param array<integer, string|integer> $path     The key-path of the value that was removed.
     * @param mixed                          $value    The removed value.
     * @param integer                        $position The position (when inside an array).
     * @return void
     */
    public function recordRemovedValue(array $path, mixed $value, int $position): void
    {
        $this->addJournalEntry(self::TYPE_REMOVED, $path, $value, null, $position);
    }

    /**
     * Record a change.
     *
     * @param string                         $type      The type of change.
     * @param array<integer, string|integer> $path      The key-path of the value that was removed.
     * @param mixed                          $origValue The original value.
     * @param mixed                          $newValue  The new value.
     * @param integer                        $position  The position (when inside an array).
     * @return void
     */
    private function addJournalEntry(string $type, array $path, mixed $origValue, mixed $newValue, int $position): void
    {
        $this->journal[] = [
            self::KEY_TYPE => $type,
            self::KEY_PATH => $path,
            self::KEY_POSITION => $position,
            self::KEY_ORIG_VALUE => $origValue,
            self::KEY_NEW_VALUE => $newValue,
        ];
    }

    /**
     * Check to see if this JsonDelta contains alterations.
     *
     * @return boolean
     */
    public function hasAlterations(): bool
    {
        return count($this->journal) > 0;
    }

    /**
     * Check to see if this JsonDelta doesn't contain alterations.
     *
     * @return boolean
     */
    public function doesntHaveAlterations(): bool
    {
        return count($this->journal) === 0;
    }

    /**
     * Get the journal array.
     *
     * @return list<mixed[]>
     */
    public function getJournal(): array
    {
        return $this->journal;
    }
}
