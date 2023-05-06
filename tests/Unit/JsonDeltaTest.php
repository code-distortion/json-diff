<?php

namespace CodeDistortion\JsonDiff\Tests\Unit;

use CodeDistortion\JsonDiff\Exceptions\JsonDiffException;
use CodeDistortion\JsonDiff\JsonDelta;
use CodeDistortion\JsonDiff\Tests\PHPUnitTestCase;
use Throwable;

/**
 * Test the JsonDiff data comparison and transformation functionality.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class JsonDeltaTest extends PHPUnitTestCase
{
    /**
     * Test that invalid delta journals are detected.
     *
     * @test
     * @dataProvider deltaJournalsDataProvider
     *
     * @param array<mixed[]> $deltaJournal    The journal data used to instantiate the JsonDelta object.
     * @param boolean        $expectException Whether to expect an exception or not.
     * @return void
     * @throws Throwable When a test fails.
     */
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
     * @return array<int, array<int, array<int, array<int, array<int, integer>|integer|string>>|boolean>>
     */
    public static function deltaJournalsDataProvider(): array
    {
        $return = [];

        // valid: changed
        $return[] = [
            [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a',
                    JsonDelta::KEY_NEW_VALUE => 'b',
                ]
            ],
            false,
        ];

        // invalid: changed
        $return[] = [
            [
                [
//                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED, // required
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a',
                    JsonDelta::KEY_NEW_VALUE => 'b',
                ]
            ],
            true,
        ];

        // invalid: changed
        $return[] = [
            [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
//                    JsonDelta::KEY_PATH => [0 => 0], // required
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a',
                    JsonDelta::KEY_NEW_VALUE => 'b',
                ]
            ],
            true,
        ];

        // invalid: changed
        $return[] = [
            [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                    JsonDelta::KEY_PATH => [0 => 0],
//                    JsonDelta::KEY_POSITION => 0, // required
                    JsonDelta::KEY_ORIG_VALUE => 'a',
                    JsonDelta::KEY_NEW_VALUE => 'b',
                ]
            ],
            true,
        ];



        // valid: new
        $return[] = [
            [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_NEW,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
//                    JsonDelta::KEY_ORIG_VALUE => 'a',
                    JsonDelta::KEY_NEW_VALUE => 'b',
                ]
            ],
            false,
        ];

        // invalid: new
        $return[] = [
            [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_NEW,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
//                    JsonDelta::KEY_ORIG_VALUE => 'a',
//                    JsonDelta::KEY_NEW_VALUE => 'b', // required
                ]
            ],
            true,
        ];

        // invalid: new
        $return[] = [
            [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_NEW,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a', // shouldn't be here
//                    JsonDelta::KEY_NEW_VALUE => 'b', // required
                ]
            ],
            true,
        ];

        // invalid: new
        $return[] = [
            [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_NEW,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a', // shouldn't be here
                    JsonDelta::KEY_NEW_VALUE => 'b', // required
                ]
            ],
            true,
        ];



        // valid: changed
        $return[] = [
            [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
//                    JsonDelta::KEY_ORIG_VALUE => 'a',
                    JsonDelta::KEY_NEW_VALUE => 'b',
                ]
            ],
            true,
        ];

        // invalid: changed
        $return[] = [
            [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a',
//                    JsonDelta::KEY_NEW_VALUE => 'b', // required
                ]
            ],
            true,
        ];

        // invalid: changed
        $return[] = [
            [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_CHANGED,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
//                    JsonDelta::KEY_ORIG_VALUE => 'a', // required
//                    JsonDelta::KEY_NEW_VALUE => 'b', // required
                ]
            ],
            true,
        ];



        // valid: removed
        $return[] = [
            [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_REMOVED,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a',
//                    JsonDelta::KEY_NEW_VALUE => 'b',
                ]
            ],
            false,
        ];

        // invalid: removed
        $return[] = [
            [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_REMOVED,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
//                    JsonDelta::KEY_ORIG_VALUE => 'a', // required
                    JsonDelta::KEY_NEW_VALUE => 'b', // shouldn't be here
                ]
            ],
            true,
        ];

        // invalid: removed
        $return[] = [
            [
                [
                    JsonDelta::KEY_TYPE => JsonDelta::TYPE_REMOVED,
                    JsonDelta::KEY_PATH => [0 => 0],
                    JsonDelta::KEY_POSITION => 0,
                    JsonDelta::KEY_ORIG_VALUE => 'a',
                    JsonDelta::KEY_NEW_VALUE => 'b', // shouldn't be here
                ]
            ],
            true,
        ];

        return $return;
    }
}
