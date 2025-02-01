<?php

namespace CodeDistortion\JsonDiff\Tests\Unit;

use CodeDistortion\JsonDiff\Exceptions\JsonDiffException;
use CodeDistortion\JsonDiff\JsonDelta;
use CodeDistortion\JsonDiff\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Throwable;

/**
 * Test the JsonDelta class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class JsonDeltaTest extends PHPUnitTestCase
{
    /**
     * Test that invalid delta journals are detected.
     *
     * @param array<mixed[]> $deltaJournal    The journal data used to instantiate the JsonDelta object.
     * @param boolean        $expectException Whether to expect an exception or not.
     * @return void
     * @throws Throwable When a test fails.
     */
    #[Test]
    #[DataProvider('deltaJournalsDataProvider')]
    public static function test_that_invalid_delta_journals_are_detected(
        array $deltaJournal,
        bool $expectException
    ): void {

        $exceptionWasThrown = false;
        try {
            new JsonDelta($deltaJournal);
        } catch (JsonDiffException $e) {
//            var_dump("Exception: \"{$e->getMessage()}\" in {$e->getFile()}:{$e->getLine()}");
            $exceptionWasThrown = true;
        }

        self::assertSame($expectException, $exceptionWasThrown);
    }


    /**
     * DataProvider for test_that_invalid_delta_journals_are_detected().
     *
     * @return array<integer,array<string,array<integer|string,array<integer,integer|list<integer>|string>>|boolean>>
     */
    public static function deltaJournalsDataProvider(): array
    {
        $return = [];

        // invalid: not a list
        $return[] = [
            'deltaJournal' => [
                'invalid-key' => [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a',
                    JsonDelta::KEY_NEW_VALUE => 'b',
                ]
            ],
            'expectException' => true,
        ];

        // valid: changed
        $return[] = [
            'deltaJournal' => [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a',
                    JsonDelta::KEY_NEW_VALUE => 'b',
                ]
            ],
            'expectException' => false,
        ];

        // invalid: changed
        $return[] = [
            'deltaJournal' => [
                [
//                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED, // required
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a',
                    JsonDelta::KEY_NEW_VALUE => 'b',
                ]
            ],
            'expectException' => true,
        ];

        // invalid: changed
        $return[] = [
            'deltaJournal' => [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
//                    JsonDelta::KEY_PATH => [0 => 0], // required
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a',
                    JsonDelta::KEY_NEW_VALUE => 'b',
                ]
            ],
            'expectException' => true,
        ];

        // invalid: changed
        $return[] = [
            'deltaJournal' => [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                    JsonDelta::KEY_PATH => [0 => 0],
//                    JsonDelta::KEY_POSITION => 0, // required
                    JsonDelta::KEY_ORIG_VALUE => 'a',
                    JsonDelta::KEY_NEW_VALUE => 'b',
                ]
            ],
            'expectException' => true,
        ];



        // valid: new
        $return[] = [
            'deltaJournal' => [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_NEW,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
//                    JsonDelta::KEY_ORIG_VALUE => 'a',
                    JsonDelta::KEY_NEW_VALUE => 'b',
                ]
            ],
            'expectException' => false,
        ];

        // invalid: new
        $return[] = [
            'deltaJournal' => [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_NEW,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
//                    JsonDelta::KEY_ORIG_VALUE => 'a',
//                    JsonDelta::KEY_NEW_VALUE => 'b', // required
                ]
            ],
            'expectException' => true,
        ];

        // invalid: new
        $return[] = [
            'deltaJournal' => [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_NEW,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a', // shouldn't be here
//                    JsonDelta::KEY_NEW_VALUE => 'b', // required
                ]
            ],
            'expectException' => true,
        ];

        // invalid: new
        $return[] = [
            'deltaJournal' => [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_NEW,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a', // shouldn't be here
                    JsonDelta::KEY_NEW_VALUE => 'b', // required
                ]
            ],
            'expectException' => true,
        ];



        // valid: changed
        $return[] = [
            'deltaJournal' => [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
//                    JsonDelta::KEY_ORIG_VALUE => 'a',
                    JsonDelta::KEY_NEW_VALUE => 'b',
                ]
            ],
            'expectException' => true,
        ];

        // invalid: changed
        $return[] = [
            'deltaJournal' => [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a',
//                    JsonDelta::KEY_NEW_VALUE => 'b', // required
                ]
            ],
            'expectException' => true,
        ];

        // invalid: changed
        $return[] = [
            'deltaJournal' => [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
//                    JsonDelta::KEY_ORIG_VALUE => 'a', // required
//                    JsonDelta::KEY_NEW_VALUE => 'b', // required
                ]
            ],
            'expectException' => true,
        ];



        // valid: removed
        $return[] = [
            'deltaJournal' => [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_REMOVED,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a',
//                    JsonDelta::KEY_NEW_VALUE => 'b',
                ]
            ],
            'expectException' => false,
        ];

        // invalid: removed
        $return[] = [
            'deltaJournal' => [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_REMOVED,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
//                    JsonDelta::KEY_ORIG_VALUE => 'a', // required
                    JsonDelta::KEY_NEW_VALUE => 'b', // shouldn't be here
                ]
            ],
            'expectException' => true,
        ];

        // invalid: removed
        $return[] = [
            'deltaJournal' => [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_REMOVED,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a',
                    JsonDelta::KEY_NEW_VALUE => 'b', // shouldn't be here
                ]
            ],
            'expectException' => true,
        ];

        return $return;
    }

    /**
     * Test that delta values can be manipulated.
     *
     * @return void
     */
    #[Test]
    public static function test_delta_values_can_be_manipulated(): void
    {
        // start with an empty delta
        $delta = new JsonDelta();
        self::assertFalse($delta->hasAlterations());
        self::assertTrue($delta->doesntHaveAlterations());
        self::assertSame([], $delta->getJournal());

        // add a new value
        $delta->recordNewValue([], 'string', 0);
        self::assertTrue($delta->hasAlterations());
        self::assertFalse($delta->doesntHaveAlterations());
        self::assertSame([
            [
                JsonDelta::KEY_TYPE => JsonDelta::TYPE_NEW,
                JsonDelta::KEY_PATH => [],
                JsonDelta::KEY_POSITION => 0,
                JsonDelta::KEY_ORIG_VALUE => null,
                JsonDelta::KEY_NEW_VALUE => 'string',
            ]
        ], $delta->getJournal());

        // change a value
        $delta->recordChangedValue([], 'string', 1234, 0);
        self::assertTrue($delta->hasAlterations());
        self::assertFalse($delta->doesntHaveAlterations());
        self::assertSame([
            [
                JsonDelta::KEY_TYPE => JsonDelta::TYPE_NEW,
                JsonDelta::KEY_PATH => [],
                JsonDelta::KEY_POSITION => 0,
                JsonDelta::KEY_ORIG_VALUE => null,
                JsonDelta::KEY_NEW_VALUE => 'string',
            ],
            [
                JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                JsonDelta::KEY_PATH => [],
                JsonDelta::KEY_POSITION => 0,
                JsonDelta::KEY_ORIG_VALUE => 'string',
                JsonDelta::KEY_NEW_VALUE => 1234,
            ],
        ], $delta->getJournal());

        // remove a value
        $delta->recordRemovedValue([], 1234, 0);
        self::assertTrue($delta->hasAlterations());
        self::assertFalse($delta->doesntHaveAlterations());
        self::assertSame([
            [
                JsonDelta::KEY_TYPE => JsonDelta::TYPE_NEW,
                JsonDelta::KEY_PATH => [],
                JsonDelta::KEY_POSITION => 0,
                JsonDelta::KEY_ORIG_VALUE => null,
                JsonDelta::KEY_NEW_VALUE => 'string',
            ],
            [
                JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                JsonDelta::KEY_PATH => [],
                JsonDelta::KEY_POSITION => 0,
                JsonDelta::KEY_ORIG_VALUE => 'string',
                JsonDelta::KEY_NEW_VALUE => 1234,
            ],
            [
                JsonDelta::KEY_TYPE => JsonDelta::TYPE_REMOVED,
                JsonDelta::KEY_PATH => [],
                JsonDelta::KEY_POSITION => 0,
                JsonDelta::KEY_ORIG_VALUE => 1234,
                JsonDelta::KEY_NEW_VALUE => null,
            ]
        ], $delta->getJournal());
    }
}
