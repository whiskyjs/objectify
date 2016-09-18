<?php

namespace whiskyjs\objectify;

class EnumerableTest extends \PHPUnit_Framework_TestCase {
    protected static function get_any($case) {
        return self::get_prop($case, 'any');
    }

    protected static function get_prop($case, $prop) {
        $obj = new \ReflectionObject($case);
        $p = $obj->getProperty($prop);
        $p->setAccessible(true);

        return $p->getValue($case);
    }

    protected static function invoke_create_lambda($case, $args) {
        return self::invoke_method($case, "create_lambda", $args);
    }

    protected static function invoke_method($case, $method, $args) {
        $obj = new \ReflectionObject($case);
        $m = $obj->getMethod($method);
        $m->setAccessible(true);

        return $m->invoke($case, ...$args);
    }

    protected static function unwrap($any) {
        if ($any instanceof WEnumerable) {
            return iterator_to_array($any);
        } else {
            return $any;
        }
    }

    protected static function wrap($input) {
        return new WEnumerable($input);
    }

    function caseProviderAChunk() {
        return [
            [[], [], [null]],
            [
                [1, 2, 3, 4, 5],
                [
                    [true, [0 => 1]],
                    [false, [1 => 2, 2 => 3, 3 => 4, 4 => 5]]
                ],
                ['$k === 0']
            ],
            [
                [1, 2, 3, 4, 5],
                [
                    [1, [0 => 1]],
                    [0, [1 => 2]],
                    [1, [2 => 3]],
                    [0, [3 => 4]],
                    [1, [4 => 5]],
                ],
                ['$v % 2']
            ],
        ];
    }

    function caseProviderAChunkWhile() {
        return [
            [[], [], [null]],
            [[1], [[1]], [null]],
            [[1, 2], [[1], [1 => 2]], ["null"]],
            [[2, 43, 6, 7, 44, 13], [[2, 1 => 43], [2 => 6, 3 => 7, 4 => 44], [5 => 13]], ['$v2 > $v1']],
            [['A' => -1, 'B' => 2, 'C' => 3], [['A' => -1], ['B' => 2, 'C' => 3]], ['$v1 + 1 === $v2']],
        ];
    }

    function caseProviderADrop() {
        return [
            [[], [], [null]],
            [[1, 2, 3], [1, 2, 3], [null]],
            [[1, 2, 3], [1 => 2, 2 => 3], [1]],
        ];
    }

    function caseProviderADropWhile() {
        return [
            [[], [], [null]],
            [[5, 7, 8, 13], [5, 7, 8, 13], ["null"]],
            [[5, 7, 8, 13], [2 => 8, 3 => 13], ['$v % 2']],
            [[5, 7, 8, 13], [0 => 5, 1 => 7, 2 => 8, 3 => 13], ['$v < 4']],
        ];
    }

    function caseProviderAFind() {
        return [
            [[], null, [null]],
            [[1, 2, 3, 4, 5], [4, 3], ['$k === 3']],
            [[1, 2, 3, 4, 5], null, ['$k === 8']],
            [[1, 2, 3, 4, 5], 94, ['$k === 8', 94]],
        ];
    }

    function caseProviderAFirst() {
        return [
            [[], null, []],
            [[1, 2, 3, 4, 5], [1, 0], []],
            [[1, 2, 3, 4, 5], [1, 2, 3], [3]],
            [['A' => 1, 2, 3, 4, 5], [1, 'A'], []],
            [['A' => 1, 'B' => 2, 'C' => 3], ['A' => 1, 'B' => 2], [2]],
        ];
    }

    function caseProviderAFlatMap() {
        return [
            [[], [], [null]],
            [[1, 2, 3], [0 => 6, 1 => 8], ['[$v * 2, $k * 4]']],
            [[1, 2, 3], [1 => -1, 2 => 0, 3 => 1], ['[$v => $k - 1]']],
        ];
    }

    function caseProviderAGrep() {
        return [
            [[], [], [null]],
            [[12, 23, 34, 14, 50], [12, 2 => 34, 3 => 14], ['^[13]']],
            [[12, 23, 34, 14, 50], [2 => 34], ['^[13]', '$v > 30']],
        ];
    }

    function caseProviderAGrepV() {
        return [
            [[], [], [null]],
            [[12, 23, 34, 14, 50], [1 => 23, 4 => 50], ['^[13]']],
            [[12, 23, 34, 14, 50], [12, 23, 3 => 14, 4 => 50], ['^[13]', '$v > 30']],
        ];
    }

    function caseProviderAMap() {
        return [
            [[], [], [null]],
            [[1, 2, 3], [0 => '0 => 1', 1 => '1 => 2', 2 => '2 => 3'], ['$k . " => " . $v']],
            [['A' => 1, 'B' => 2, 'C' => 3], [1 => 'A', 2 => 'B', 3 => 'C'], ['[$k, $v]']],
            [['A' => 1, 'B' => 2, 'C' => 3], ['A' => 'A', 'B' => 'B', 'C' => 'C'], ['[$k, $v]',
                Enumerable::$VALUES_ONLY]],
            [['A' => 1, 'B' => 2, 'C' => 3], [1 => 1, 2 => 2, 3 => 3], ['[$k, $v]', Enumerable::$KEYS_ONLY]],
        ];
    }

    function caseProviderAPartition() {
        return [
            [[], [[], []], [null]],
            [[1, 2, 3, 4], [[1, 2 => 3], [1 => 2, 3 => 4]], ['$v % 2']],
        ];
    }

    function caseProviderARSort() {
        return [
            [[], [], []],
            [[2, 3, 1], [1 => 3, 0 => 2, 2 => 1], []],
            [['B' => 2, 'C' => 3, 'A' => 1], ['C' => 3, 'B' => 2, 'A' => 1], []],
            [
                ['B' => 2, 'C' => 3, 'A' => 1],
                ['C' => 3, 'B' => 2, 'A' => 1],
                [Enumerable::$REVERSE | Enumerable::$PRESERVE_KEYS]
            ],
        ];
    }

    function caseProviderAReject() {
        return [
            [[], [], [null]],
            [[1, 2, 3], [0 => 1, 2 => 3], ['$v === 2']],
            [[1, 2, 3], [1, 2, 3], ['$v === 8']],
            [[1, 2, 3], [], ['$v']],
        ];
    }

    function caseProviderASelect() {
        return [
            [[], [], [null]],
            [[1, 2, 3, 4, 5], [1 => 2], ['$k === 1']],
            [[1, 2, 3, 4, 5], [0 => 1, 2 => 3, 4 => 5], ['$v % 2']],
        ];
    }

    function caseProviderASliceAfter() {
        return [
            [[], [], [null]],
            [
                [1, 2, 3, 5, 8, 13, 21, 34],
                [[1], [1 => 2], [2 => 3, 3 => 5, 4 => 8, 5 => 13], [6 => 21], [7 => 34]],
                ['^[12]']
            ],
            [
                [1, 2, 3, 5, 8, 13, 21, 34],
                [[1], [1 => 2, 2 => 3], [3 => 5], [4 => 8, 5 => 13], [6 => 21], [7 => 34]],
                ['$v % 2', Enumerable::$ENABLE_EVAL]
            ],
        ];
    }

    function caseProviderASliceBefore() {
        return [
            [[], [], [null]],
            [
                [1, 2, 3, 5, 8, 13, 21, 34],
                [[1], [1 => 2, 2 => 3, 3 => 5, 4 => 8], [5 => 13], [6 => 21, 7 => 34]],
                ['^[12]']
            ],
            [
                [1, 2, 3, 5, 8, 13, 21, 34],
                [[1, 2], [2 => 3], [3 => 5, 4 => 8], [5 => 13], [6 => 21, 7 => 34]],
                ['$v % 2', Enumerable::$ENABLE_EVAL]
            ],
        ];
    }

    function caseProviderASort() {
        return [
            [[], [], []],
            [[2, 3, 1], [2 => 1, 0 => 2, 1 => 3], []],
            [['B' => 2, 'C' => 3, 'A' => 1], ['A' => 1, 'B' => 2, 'C' => 3], []],
            [['B' => 2, 'C' => 3, 'A' => 1], ['C' => 3, 'B' => 2, 'A' => 1], ['$v2 - $v1']],
            [['B' => 2, 'C' => 3, 'A' => 1], ['C' => 3, 'B' => 2, 'A' => 1], ['$v2 - $v1', Enumerable::$REVERSE]],
        ];
    }

    function caseProviderATake() {
        return [
            [[], [], [null]],
            [['A' => 1, 'B' => 2, 'C' => 3], ['A' => 1], [1]],
            [['A' => 1, 'B' => 2, 'C' => 3], [], [null]],
            [['A' => 1, 'B' => 2, 'C' => 3], ['A' => 1, 'B' => 2, 'C' => 3], [4]],
        ];
    }

    function caseProviderATakeWhile() {
        return [
            [[], [], [null]],
            [['E' => 5, 'F' => 7, 'G' => 8, 'H' => 13], [], ["null"]],
            [['E' => 5, 'F' => 7, 'G' => 8, 'H' => 13], ['E' => 5, 'F' => 7], ['$v % 2']],
            [['E' => 5, 'F' => 7, 'G' => 8, 'H' => 13], [], ['$v < 4']],
        ];
    }

    function caseProviderAZip() {
        return [
            [
                ['X' => 1, 'Y' => 2],
                [['X' => 1, 10 => 'A'], ['Y' => 'B'], [null => null, 'Z' => 'C']],
                [[[10 => 'A', 'Y' => 'B', 'Z' => 'C']]]],
        ];
    }

    function caseProviderAll() {
        return [
            [[], true, [null]],
            [[1, 2, 3], true, []],
            [[1, null, 3], false, []],
            [[1, 2, 3, 4, 5], true, ['$v > 0']],
            [[1, 2, 3, 4, 5], false, ['$k > 0']],
            [[1, 2, 3, 4, 5], false, [function($v, $k, $i) {
                return $i > false;
            }]],
            [[1, 2, 3, 4, 5], true, [function($v, $k, $i) {
                return $i > -1;
            }]],
        ];
    }

    function caseProviderAny() {
        return [
            [[], false, [null]],
            [[1, 2, 3], true, []],
            [[1, 2, 3, 4, 5], true, ['$k === 0']],
            [[1, 2, 3, 4, 5], false, ['$v > 6']],
            [[1, 2, 3, 4, 5], true, [function($v, $k, $i) {
                return ($i === 3) && ($v === 4);
            }]],
        ];
    }

    function caseProviderChunk() {
        return [
            [[], [], [null]],
            [
                [1, 2, 3, 4, 5],
                [
                    [true, [1]],
                    [false, [2, 3, 4, 5]]
                ],
                ['$k === 0']
            ],
            [
                [1, 2, 3, 4, 5],
                [
                    [1, [1]],
                    [0, [2]],
                    [1, [3]],
                    [0, [4]],
                    [1, [5]],
                ],
                ['$v % 2']
            ],
            [
                ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4],
                [
                    [false, ['A' => 1]],
                    [true, ['C' => 3, 'D' => 4]],
                ],
                ['$k === "B" ? null : $v > 2', Enumerable::$PRESERVE_KEYS]
            ],
        ];
    }

    function caseProviderChunkWhile() {
        return [
            [[], [], [null]],
            [[1], [[1]], [null]],
            [[1, 2], [[1], [2]], ["false"]],
            [[2, 43, 6, 7, 44, 13], [[2, 43], [6, 7, 44], [13]], ['$v2 > $v1']],
            [['A' => -1, 'B' => 2, 'C' => 3], [[-1], [2, 3]], ['$v1 + 1 === $v2']],
        ];
    }

    function caseProviderCollect() {
        return [
            [[], [], []],
            [['A' => 4, 'B' => 5], [], []],
        ];
    }

    function caseProviderCount() {
        return [
            [[], 0, [null]],
            [[1, 2, 3, 2, 7, 3], 6, []],
            [[1, 2, 3, 2, 7, 3], 2, [3]],
            [[1, 2, 3, 4, 5], 2, ['!($v % 2)', Enumerable::$ENABLE_EVAL]],
            [['T', 'A', 'C', 'B', 'A'], 2, ['A']],
        ];
    }

    function caseProviderCountEval() {
        return [
            [[], 0, [null]],
            [[1, 2, 3, 2, 7, 3], 6, []],
            [[1, 2, 3, 2, 7, 3], 2, [3]],
            [[1, 2, 3, 4, 5], 2, ['!($v % 2)']],
            [['T', 'A', 'C', 'B', 'A'], 5, ['"A"']],
        ];
    }

    function caseProviderCycle() {
        return [
            // Can't really test if method will indeed run infinitely
            // [[], [], [null]],
            [[], [], [0]],
            [
                [7, 8, 9],
                [
                    [7, 0, 0],
                    [8, 1, 1],
                    [9, 2, 2],
                    [7, 0, 0],
                    [8, 1, 1],
                    [9, 2, 2]
                ],
                [2]
            ],
        ];
    }

    function caseProviderDrop() {
        return [
            [[], [], [null]],
            [[1, 2, 3], [1, 2, 3], [null]],
            [[1, 2, 3], [2, 3], [1]],
        ];
    }

    function caseProviderDropWhile() {
        return [
            [[], [], [null]],
            [[5, 7, 8, 13], [5, 7, 8, 13], ["null"]],
            [[5, 7, 8, 13], [8, 13], ['$v % 2']],
            [[5, 7, 8, 13], [5, 7, 8, 13], ['$v < 4']],
        ];
    }

    function caseProviderEach() {
        return [
            [[], [], []],
            [['A' => 4, 'B' => 5], [[4, 'A', 0], [5, 'B', 1]], []],
        ];
    }

    function caseProviderEachCons() {
        return [
            [[], [], [null]],
            [[1], [[[1], 0]], [1]],
            [
                [1, 2],
                [[[1], 0], [[2], 1]],
                [1]
            ],
            [
                [1, 2, 3],
                [[[1, 2], 0], [[2, 3], 1]],
                [2]
            ],
        ];
    }

    function caseProviderEachSlice() {
        return [
            [[], [], [null]],
            [[1], [[[1], 0]], [1]],
            [
                [1, 2],
                [[[1], 0], [[2], 1]],
                [1]
            ],
            [
                [1, 2, 3],
                [[[1, 2], 0], [[3], 1]],
                [2]
            ],
            [
                [1, 2, 3],
                [[[1, 2, 3], 0]],
                [5]
            ],
        ];
    }

    function caseProviderEachWithObject() {
        return [
            [[], [], ["object"]],
            [['A' => 4, 'B' => 5], [[4, 'A', 0, "object"], [5, 'B', 1, "object"]], ["object"]],
        ];
    }

    function caseProviderFind() {
        return [
            [[], null, ['$v == 2']],
            [[1, 2, 3, 4, 5], 4, ['$k === 3']],
            [[1, 2, 3, 4, 5], null, ['$k === 8']],
        ];
    }

    function caseProviderFindIndex() {
        return [
            [[], null, [128]],
            [['A' => 1, 'C' => 3, 'E' => 5], null, [16]],
            [['A' => 1, 'C' => 3, 'E' => 5], 'C', [3]],
            [['A' => 'one', 'C' => 'three', 'E' => 'five'], 'C', ["three"]],
            [['A' => 'one', 'C' => 'three', 'E' => 'five'], 'E', ['$v === "five"', Enumerable::$ENABLE_EVAL]],
            [['A' => 'one', 'C' => 'three', 'E' => 'five'], 'E', [function($v) { return $v === "five"; }]],
            [['A' => 'one', 'C' => 'three', 'E' => 'five'], null, ['$v === "nine"']],
        ];
    }

    function caseProviderFindIndexEval() {
        return [
            [[], null, [128]],
            [['A' => 1, 'C' => 3, 'E' => 5], null, [16]],
            [['A' => 1, 'C' => 3, 'E' => 5], 'C', [3]],
            [['A' => 'one', 'C' => 'three', 'E' => 'five'], 'A', ["'three'"]],
            [['A' => 'one', 'C' => 'three', 'E' => 'five'], 'E', ['$v === "five"']],
            [['A' => 'one', 'C' => 'three', 'E' => 'five'], 'E', [function($v) { return $v === "five"; }]],
            [['A' => 'one', 'C' => 'three', 'E' => 'five'], null, ['$v === "nine"']],
        ];
    }

    function caseProviderFirst() {
        return [
            [[], null, []],
            [[1, 2, 3, 4, 5], 1, []],
            [[1, 2, 3, 4, 5], [1, 2, 3], [3]],
        ];
    }

    function caseProviderFlatMap() {
        return [
            [[], [], [null]],
            [[1, 2, 3], [2, 0, 4, 4, 6, 8], ['[$v * 2, $k * 4]']],
            [[1, 2, 3], [-1, 0, 1], ['[$k - 1]']],
            [[1, 2, 3], ['00', '11', '22'], ['[$i . $i]']],
        ];
    }

    function caseProviderGrep() {
    return [
        [[], [], [null]],
        [[12, 23, 34, 14, 50], [12, 34, 14], ['^[13]']],
        [[12, 23, 34, 14, 50], [34], ['^[13]', '$v > 30']],
    ];
}

    function caseProviderGrepV() {
        return [
            [[], [], [null]],
            [[12, 23, 34, 14, 50], [23, 50], ['^[13]']],
            [[12, 23, 34, 14, 50], [12, 23, 14, 50], ['^[13]', '$v > 30']],
        ];
    }

    function caseProviderGroupBy() {
        return [
            [[], [], [null]],
            [['A' => 1, 'C' => 3, 'F' => 6], [1 => [1, 3], 0 => [6]], ['$v % 2']],
            [['A' => 1, 'C' => 3, 'F' => 6], [1 => [1, 3], 0 => [6]], [function($v) { return $v % 2; }]],
            [['A' => 1, 'C' => 3, 'F' => 6], [1 => ['A' => 1, 'C' => 3], 0 => ['F' => 6]],
                [function($v) { return $v % 2; }, Enumerable::$PRESERVE_KEYS]],
        ];
    }

    function caseProviderIncludes() {
        return [
            [[], false, [null]],
            [[1, 2, 3], true, [3]],
            [[1, 2, 3], false, [9]],
        ];
    }

    function caseProviderKRSort() {
        return [
            [[], [], []],
            [[2, 3, 1], [2 => 1, 1 => 3, 0 => 2], []],
            [['B' => 9, 'C' => 5, 'A' => 6], ['C' => 5, 'B' => 9, 'A' => 6], []],
            [['B' => 9, 'C' => 5, 'A' => 6], ['C' => 5, 'B' => 9, 'A' => 6], [Enumerable::$REVERSE]],
            [
                ['B' => 9, 'C' => 5, 'A' => 6],
                ['C' => 5, 'B' => 9, 'A' => 6],
                [Enumerable::$REVERSE | Enumerable::$PRESERVE_KEYS]],
        ];
    }

    function caseProviderKSort() {
        return [
            [[], [], []],
            [[2, 3, 1], [2, 3, 1], []],
            [['B' => 9, 'C' => 5, 'A' => 6], ['A' => 6, 'B' => 9, 'C' => 5], []],
            [['B' => 9, 'C' => 5, 'A' => 6], ['C' => 5, 'B' => 9, 'A' => 6], [null, Enumerable::$REVERSE]],
            [
                ['B' => 9, 'C' => 5, 'A' => 6],
                ['C' => 5, 'B' => 9, 'A' => 6],
                [null, Enumerable::$REVERSE | Enumerable::$PRESERVE_KEYS]
            ],
            [
                ['B' => 9, 'C' => 5, 'A' => 6],
                ['C' => 5, 'B' => 9, 'A' => 6],
                ['($k1 > $k2) ? -1 : ($k1 < $k2 ? 1 : 0)', Enumerable::$PRESERVE_KEYS]
            ],
            [
                ['B' => 9, 'C' => 5, 'A' => 6],
                ['C' => 5, 'B' => 9, 'A' => 6],
                [function($k1, $k2) {
                    return ($k1 > $k2) ? -1 : ($k1 < $k2 ? 1 : 0);
                }]
            ],
        ];
    }

    function caseProviderMap() {
        return [
            [[], [], [null]],
            [[1, 2, 3], ['0 => 1', '1 => 2', '2 => 3'], ['$k . " => " . $v']],
            [['A' => 1, 'B' => 2, 'C' => 3], [1 => 'A', 2 => 'B', 3 => 'C'], ['[$k, $v]']],
            [['A' => 1, 'B' => 2, 'C' => 3], ['A' => 'A', 'B' => 'B', 'C' => 'C'], ['[$k, $v]',
                Enumerable::$VALUES_ONLY]],
            [['A' => 1, 'B' => 2, 'C' => 3], [1 => 1, 2 => 2, 3 => 3], ['[$k, $v]', Enumerable::$KEYS_ONLY]],
        ];
    }

    function caseProviderNone() {
        return [
            [[], true, [null]],
            [[1, 2, 3], false, []],
            [[0, [], false], true, []],
            [[4, 5, 6], true, ['$v > 6']],
            [[4, 5, 6], false, ['$v > 5']],
        ];
    }

    function caseProviderOne() {
        return [
            [[], false, []],
            [[1, 2, 4], false, ['$v === 3']],
            [[1, 2, 3], true, ['$v === 3']],
            [[1, 2, 3, 3], false, ['$v === 3']],
        ];
    }

    function caseProviderPartition() {
        return [
            [[], [[], []], [null]],
            [[1, 2, 3, 4], [[1, 3], [2, 4]], ['$v % 2']],
            [[1, 2, 3, 4], [[1, 2 => 3], [1 => 2, 3 => 4]], ['$v % 2', Enumerable::$PRESERVE_KEYS]],
        ];
    }

    function caseProviderRSort() {
        return [
            [[], [], []],
            [[2, 3, 1], [3, 2, 1], []],
            [['B' => 9, 'C' => 5, 'A' => 6], [9, 6, 5], []],
            [['B' => 9, 'C' => 5, 'A' => 6], [9, 6, 5], [Enumerable::$REVERSE]],
            [
                ['B' => 9, 'C' => 5, 'A' => 6],
                ['B' => 9, 'A' => 6, 'C' => 5],
                [Enumerable::$REVERSE | Enumerable::$PRESERVE_KEYS]],
        ];
    }

    function caseProviderReduce() {
        return [
            [[], 0, [0, null]],
            [[1, 2, 3], 6, [0, '$a + $v']],
            [[1, 2, 3], 3, [0, null]],
        ];
    }

    function caseProviderReject() {
        return [
            [[], [], [null]],
            [[1, 2, 3], [1, 3], ['$v === 2']],
            [[1, 2, 3], [1, 2, 3], ['$v === 8']],
            [[1, 2, 3], [], ['$v']],
        ];
    }

    function caseProviderSelect() {
        return [
            [[], [], [null]],
            [[1, 2, 3], [2], ['$k === 1']],
            [[1, 2, 3], [], ['$v === 8']],
            [[1, 2, 3], [3], ['$i === 2']],
        ];
    }

    function caseProviderSliceAfter() {
        return [
            [[], [], [null]],
            [[1], [[1]], [null]],
            [[1, 2, 3, 5, 8, 13, 21, 34], [[1], [2], [3, 5, 8, 13], [21], [34]], ['^[12]']],
            [[1, 2, 3, 5, 8, 13, 21, 34], [[1], [2, 3], [5], [8, 13], [21], [34]], ['$v % 2', Enumerable::$ENABLE_EVAL]],
        ];
    }

    function caseProviderSliceBefore() {
        return [
            [[], [], [null]],
            [[1], [[1]], [null]],
            [[1, 2, 3, 5, 8, 13, 21, 34], [[1], [2, 3, 5, 8], [13], [21, 34]], ['^[12]']],
            [[1, 2, 3, 5, 8, 13, 21, 34], [[1, 2], [3], [5, 8], [13], [21, 34]], ['$v % 2', Enumerable::$ENABLE_EVAL]],
        ];
    }

    function caseProviderSort() {
        return [
            [[], [], []],
            [[2, 3, 1], [1, 2, 3], []],
            [['B' => 2, 'C' => 3, 'A' => 1], [1, 2, 3], []],
            [['B' => 2, 'C' => 3, 'A' => 1], [3, 2, 1], ['$v2 - $v1']],
            [['B' => 2, 'C' => 3, 'A' => 1], [3, 2, 1], ['$v2 - $v1', Enumerable::$REVERSE]],
            [
                ['B' => 2, 'C' => 3, 'A' => 1],
                ['C' => 3, 'B' => 2, 'A' => 1],
                ['$v2 - $v1', Enumerable::$REVERSE | Enumerable::$PRESERVE_KEYS]],
        ];
    }

    function caseProviderTake() {
        return [
            [[], [], [null]],
            [[1, 2, 3], [1], [1]],
            [[1, 2, 3], [], [null]],
            [[1, 2, 3], [1, 2, 3], [4]],
        ];
    }

    function caseProviderTakeWhile() {
        return [
            [[], [], [null]],
            [[5, 7, 8, 13], [], ["null"]],
            [[5, 7, 8, 13], [5, 7], ['$v % 2']],
            [[5, 7, 8, 13], [], ['$v < 4']],
        ];
    }

    function caseProviderToA() {
        return [
            [[], [], []],
            [[1, 2, 3], [1, 2, 3], []],
            [['A' => 1, 'B' => 2, 'C' => 3], [1, 2, 3], []],
            [['A' => 1, 'B' => 2, 'C' => 3], ['A' => 1, 'B' => 2, 'C' => 3], [Enumerable::$PRESERVE_KEYS]],
            [
                function() {
                    yield 'A' => 1;
                    yield 'B' => 2;
                    yield 'C' => 3;
                },
                [1, 2, 3],
                []
            ],
            [
                function() {
                    yield 'A' => 1;
                    yield 'B' => 2;
                    yield 'C' => 3;
                },
                ['A' => 1, 'B' => 2, 'C' => 3],
                [Enumerable::$PRESERVE_KEYS]
            ],
        ];
    }

    function caseProviderToAA() {
        return [
            [[], [], []],
            [[1, 2, 3], [1, 2, 3], []],
            [['A' => 1, 'B' => 2, 'C' => 3], ['A' => 1, 'B' => 2, 'C' => 3], []],
            [['A' => 1, 'B' => 2, 'C' => 3], ['A' => 1, 'B' => 2, 'C' => 3], []],
            [
                function() {
                    yield 'A' => 1;
                    yield 'B' => 2;
                    yield 'C' => 3;
                },
                ['A' => 1, 'B' => 2, 'C' => 3],
                []
            ],
        ];
    }

    function caseProviderZip() {
        return [
            [[], [], []],
            [[1, 2, 3], [[1, 'A'], [2, 'B'], [3, null]], [[['A', 'B']]]],
            [[1, 2], [[1, 'A'], [2, 'B'], [null, 'C']], [[['A', 'B', 'C']]]],
            [
                ['X' => 1, 'Y' => 2],
                [['X' => 1, 10 => 'A'], ['Y' => 'B'], [null => null, 'Z' => 'C']],
                [[[10 => 'A', 'Y' => 'B', 'Z' => 'C']], Enumerable::$PRESERVE_KEYS]],
        ];
    }

    /**
     * @dataProvider caseProviderAChunk
     * @param $input
     * @param $expected
     * @param $args
     */
    function testAChunk($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->achunk(...$args)));
    }

    /**
     * @dataProvider caseProviderAChunkWhile
     * @param $input
     * @param $expected
     * @param $args
     */
    function testAChunkWhile($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->achunk_while(...$args)));
    }

    /**
     * @dataProvider caseProviderAMap
     * @param $input
     * @param $expected
     * @param $args
     */
    function testACollect($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->acollect(...$args)));
    }

    /**
     * @dataProvider caseProviderAFind
     * @param $input
     * @param $expected
     * @param $args
     */
    function testADetect($input, $expected, $args) {
        $result = self::wrap($input)->adetect(...$args);
        $this->assertSame($expected, self::unwrap($result));
    }

    /**
     * @dataProvider caseProviderADrop
     * @param $input
     * @param $expected
     * @param $args
     */
    function testADrop($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->adrop(...$args)));
    }

    /**
     * @dataProvider caseProviderADropWhile
     * @param $input
     * @param $expected
     * @param $args
     */
    function testADropWhile($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->adrop_while(...$args)));
    }

    /**
     * @dataProvider caseProviderAFind
     * @param $input
     * @param $expected
     * @param $args
     */
    function testAFind($input, $expected, $args) {
        $result = self::wrap($input)->afind(...$args);
        $this->assertSame($expected, self::unwrap($result));
    }

    /**
     * @dataProvider caseProviderASelect
     * @param $input
     * @param $expected
     * @param $args
     */
    function testAFindAll($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->afind_all(...$args)));
    }

    /**
     * @dataProvider caseProviderAFirst
     * @param $input
     * @param $expected
     * @param $args
     */
    function testAFirst($input, $expected, $args) {
        $caseResult = self::wrap($input)->afirst(...$args);

        if ($caseResult instanceof WEnumerable) {
            $this->assertSame($expected, iterator_to_array($caseResult));
        } else {
            $this->assertSame($expected, $caseResult);
        }
    }

    /**
     * @dataProvider caseProviderAFlatMap
     * @param $input
     * @param $expected
     * @param $args
     */
    function testAFlatMap($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->aflat_map(...$args)));
    }

    /**
     * @dataProvider caseProviderAGrep
     * @param $input
     * @param $expected
     * @param $args
     */
    function testAGrep($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->agrep(...$args)));
    }

    /**
     * @dataProvider caseProviderAGrepV
     * @param $input
     * @param $expected
     * @param $args
     */
    function testAGrepV($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->agrep_v(...$args)));
    }

    /**
     * @dataProvider caseProviderAMap
     * @param $input
     * @param $expected
     * @param $args
     */
    function testAMap($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->amap(...$args)));
    }

    /**
     * @dataProvider caseProviderAPartition
     * @param $input
     * @param $expected
     * @param $args
     */
    function testAPartition($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->apartition(...$args)));
    }

    /**
     * @dataProvider caseProviderARSort
     * @param $input
     * @param $expected
     * @param $args
     */
    function testARSort($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->arsort(...$args)));
    }

    /**
     * @dataProvider caseProviderAReject
     * @param $input
     * @param $expected
     * @param $args
     */
    function testAReject($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->areject(...$args)));
    }

    /**
     * @dataProvider caseProviderASelect
     * @param $input
     * @param $expected
     * @param $args
     */
    function testASelect($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->aselect(...$args)));
    }

    /**
     * @dataProvider caseProviderASliceAfter
     * @param $input
     * @param $expected
     * @param $args
     */
    function testASliceAfter($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->aslice_after(...$args)));
    }

    /**
     * @dataProvider caseProviderASliceBefore
     * @param $input
     * @param $expected
     * @param $args
     */
    function testASliceBefore($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->aslice_before(...$args)));
    }

    /**
     * @dataProvider caseProviderASort
     * @param $input
     * @param $expected
     * @param $args
     */
    function testASort($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->asort(...$args)));
    }

    /**
     * @dataProvider caseProviderATake
     * @param $input
     * @param $expected
     * @param $args
     */
    function testATake($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->atake(...$args)));
    }

    /**
     * @dataProvider caseProviderATakeWhile
     * @param $input
     * @param $expected
     * @param $args
     */
    function testATakeWhile($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->atake_while(...$args)));
    }

    /**
     * @dataProvider caseProviderAZip
     * @param $input
     * @param $expected
     * @param $args
     */
    function testAZip($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->azip(...$args)));
    }

    /**
     * @dataProvider caseProviderAll
     * @param $input
     * @param $expected
     * @param $args
     */
    function testAll($input, $expected, $args) {
        $this->assertSame($expected, self::wrap($input)->all(...$args));
    }

    /**
     * @dataProvider caseProviderAny
     * @param $input
     * @param $expected
     * @param $args
     */
    function testAny($input, $expected, $args) {
        $this->assertSame($expected, self::wrap($input)->any(...$args));
    }

    /**
     * @dataProvider caseProviderChunk
     * @param $input
     * @param $expected
     * @param $args
     */
    function testChunk($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->chunk(...$args)));
    }

    /**
     * @dataProvider caseProviderChunkWhile
     * @param $input
     * @param $expected
     * @param $args
     */
    function testChunkWhile($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->chunk_while(...$args)));
    }

    /**
     * @dataProvider caseProviderMap
     * @param $input
     * @param $expected
     * @param $args
     */
    function testCollect($input, $expected, $args) {
        $this->assertSame($expected,iterator_to_array(self::wrap($input)->collect(...$args)));
    }

    /**
     * @dataProvider caseProviderFlatMap
     * @param $input
     * @param $expected
     * @param $args
     */
    function testCollectConcat($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->collect_concat(...$args)));
    }

    /**
     * @dataProvider caseProviderCount
     * @param $input
     * @param $expected
     * @param $args
     */
    function testCount($input, $expected, $args) {
        $this->assertSame($expected, self::wrap($input)->count(...$args));
    }

    /**
     * @dataProvider caseProviderCountEval
     * @param $input
     * @param $expected
     * @param $args
     */
    function testCountEval($input, $expected, $args) {
        $this->assertSame($expected, self::wrap($input)->count_eval(...$args));
    }

    /**
     * @dataProvider caseProviderCycle
     * @param $input
     * @param $expected
     * @param $args
     */
    function testCycle($input, $expected, $args) {
        $caseResult = [];

        self::wrap($input)->cycle($args[0], function($v, $k, $i) use (&$caseResult) {
            $caseResult[] = [$v, $k, $i];
        });

        $this->assertSame($expected, $caseResult);
    }

    /**
     * @dataProvider caseProviderFind
     * @param $input
     * @param $expected
     * @param $args
     */
    function testDetect($input, $expected, $args) {
        $result = self::wrap($input)->detect(...$args);
        $this->assertSame($expected, self::unwrap($result));
    }

    /**
     * @dataProvider caseProviderDrop
     * @param $input
     * @param $expected
     * @param $args
     */
    function testDrop($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->drop(...$args)));
    }

    /**
     * @dataProvider caseProviderDropWhile
     * @param $input
     * @param $expected
     * @param $args
     */
    function testDropWhile($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->drop_while(...$args)));
    }

    /**
     * @dataProvider caseProviderEach
     * @param $input
     * @param $expected
     * @param $args
     */
    function testEach($input, $expected, $args) {
        $caseResult = [];

        self::wrap($input)->each(function($v, $k, $i) use (&$caseResult) {
            $caseResult[] = [$v, $k, $i];
        });

        $this->assertSame($expected, $caseResult);
    }

    /**
     * @dataProvider caseProviderEachCons
     * @param $input
     * @param $expected
     * @param $args
     */
    function testEachCons($input, $expected, $args) {
        $caseResult = [];

        self::wrap($input)->each_cons($args[0], function($v, $i) use (&$caseResult) {
            $caseResult[] = [$v, $i];
        });

        $this->assertSame($expected, $caseResult);
    }

    /**
     * @dataProvider caseProviderEach
     * @param $input
     * @param $expected
     * @param $args
     */
    function testEachEntry($input, $expected, $args) {
        $caseResult = [];

        self::wrap($input)->each_entry(function($v, $k, $i) use (&$caseResult) {
            $caseResult[] = [$v, $k, $i];
        });

        $this->assertSame($expected, $caseResult);
    }

    /**
     * @dataProvider caseProviderEach
     * @param $input
     * @param $expected
     * @param $args
     */
    function testEachGlobals($input, $expected, $args) {
        $GLOBALS['caseResult'] = [];

        self::wrap($input)->each('$GLOBALS["caseResult"][] = [$v, $k, $i]');

        $this->assertSame($expected, $GLOBALS['caseResult']);
    }

    /**
     * @dataProvider caseProviderEachSlice
     * @param $input
     * @param $expected
     * @param $args
     */
    function testEachSlice($input, $expected, $args) {
        $caseResult = [];

        self::wrap($input)->each_slice($args[0], function($v, $i) use (&$caseResult) {
            $caseResult[] = [$v, $i];
        });

        $this->assertSame($expected, $caseResult);
    }

    /**
     * @dataProvider caseProviderEach
     * @param $input
     * @param $expected
     * @param $args
     */
    function testEachWithIndex($input, $expected, $args) {
        $caseResult = [];

        self::wrap($input)->each_with_index(function($v, $k, $i) use (&$caseResult) {
            $caseResult[] = [$v, $k, $i];
        });

        $this->assertSame($expected, $caseResult);
    }

    /**
     * @dataProvider caseProviderEachWithObject
     * @param $input
     * @param $expected
     * @param $args
     */
    function testEachWithObject($input, $expected, $args) {
        $caseResult = [];

        $caseObject = self::wrap($input)->each_with_object($args[0], function($v, $k, $i, $o) use (&$caseResult) {
            $caseResult[] = [$v, $k, $i, $o];
        });

        $this->assertSame($expected, $caseResult);
        $this->assertSame($args[0], $caseObject);
    }

    /**
     * @dataProvider caseProviderFind
     * @param $input
     * @param $expected
     * @param $args
     */
    function testFind($input, $expected, $args) {
        $result = self::wrap($input)->find(...$args);
        $this->assertSame($expected, self::unwrap($result));
    }

    /**
     * @dataProvider caseProviderSelect
     * @param $input
     * @param $expected
     * @param $args
     */
    function testFindAll($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->find_all(...$args)));
    }

    /**
     * @dataProvider caseProviderFindIndex
     * @param $input
     * @param $expected
     * @param $args
     */
    function testFindIndex($input, $expected, $args) {
        $this->assertSame($expected, self::wrap($input)->find_index(...$args));
    }

    /**
     * @dataProvider caseProviderFindIndexEval
     * @param $input
     * @param $expected
     * @param $args
     */
    function testFindIndexEval($input, $expected, $args) {
        $this->assertSame($expected, self::wrap($input)->find_index_eval(...$args));
    }

    /**
     * @dataProvider caseProviderFirst
     * @param $input
     * @param $expected
     * @param $args
     */
    function testFirst($input, $expected, $args) {
        $caseResult = self::wrap($input)->first(...$args);

        if ($caseResult instanceof WEnumerable) {
            $this->assertSame($expected, iterator_to_array($caseResult));
        } else {
            $this->assertSame($expected, $caseResult);
        }
    }

    /**
     * @dataProvider caseProviderFlatMap
     * @param $input
     * @param $expected
     * @param $args
     */
    function testFlatMap($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->flat_map(...$args)));
    }

    /**
     * @dataProvider caseProviderGrep
     * @param $input
     * @param $expected
     * @param $args
     */
    function testGrep($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->grep(...$args)));
    }

    /**
     * @dataProvider caseProviderGrepV
     * @param $input
     * @param $expected
     * @param $args
     */
    function testGrepV($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->grep_v(...$args)));
    }

    /**
     * @dataProvider caseProviderGroupBy
     * @param $input
     * @param $expected
     * @param $args
     */
    function testGroupBy($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->group_by(...$args)));
    }

    /**
     * @dataProvider caseProviderIncludes
     * @param $input
     * @param $expected
     * @param $args
     */
    function testIncludes($input, $expected, $args) {
        $this->assertSame($expected, self::wrap($input)->includes(...$args));
    }

    /**
     * @dataProvider caseProviderReduce
     * @param $input
     * @param $expected
     * @param $args
     */
    function testInject($input, $expected, $args) {
        $result = self::wrap($input)->inject(...$args);

        if ($result instanceof WEnumerable) {
            $this->assertSame($expected, iterator_to_array($result));
        } else {
            $this->assertSame($expected, $result);
        }
    }

    /**
     * @dataProvider caseProviderKRSort
     * @param $input
     * @param $expected
     * @param $args
     */
    function testKRSort($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->krsort(...$args)));
    }

    /**
     * @dataProvider caseProviderKSort
     * @param $input
     * @param $expected
     * @param $args
     */
    function testKSort($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->ksort(...$args)));
    }

    /**
     * @dataProvider caseProviderMap
     * @param $input
     * @param $expected
     * @param $args
     */
    function testMap($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->map(...$args)));
    }

    /**
     * @dataProvider caseProviderIncludes
     * @param $input
     * @param $expected
     * @param $args
     */
    function testMember($input, $expected, $args) {
        $this->assertSame($expected, self::wrap($input)->member(...$args));
    }

    /**
     * @dataProvider caseProviderNone
     * @param $input
     * @param $expected
     * @param $args
     */
    function testNone($input, $expected, $args) {
        $this->assertSame($expected, self::wrap($input)->none(...$args));
    }

    /**
     * @dataProvider caseProviderOne
     * @param $input
     * @param $expected
     * @param $args
     */
    function testOne($input, $expected, $args) {
        $this->assertSame($expected, self::wrap($input)->one(...$args));
    }

    /**
     * @dataProvider caseProviderPartition
     * @param $input
     * @param $expected
     * @param $args
     */
    function testPartition($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->partition(...$args)));
    }

    /**
     * @dataProvider caseProviderRSort
     * @param $input
     * @param $expected
     * @param $args
     */
    function testRSort($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->rsort(...$args)));
    }

    /**
     * @dataProvider caseProviderReduce
     * @param $input
     * @param $expected
     * @param $args
     */
    function testReduce($input, $expected, $args) {
        $result = self::wrap($input)->reduce(...$args);

        if ($result instanceof WEnumerable) {
            $this->assertSame($expected, iterator_to_array($result));
        } else {
            $this->assertSame($expected, $result);
        }
    }

    /**
     * @dataProvider caseProviderReject
     * @param $input
     * @param $expected
     * @param $args
     */
    function testReject($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->reject(...$args)));
    }

    /**
     * @dataProvider caseProviderSelect
     * @param $input
     * @param $expected
     * @param $args
     */
    function testSelect($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->select(...$args)));
    }

    /**
     * @dataProvider caseProviderSliceAfter
     * @param $input
     * @param $expected
     * @param $args
     */
    function testSliceAfter($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->slice_after(...$args)));
    }

    /**
     * @dataProvider caseProviderSliceBefore
     * @param $input
     * @param $expected
     * @param $args
     */
    function testSliceBefore($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->slice_before(...$args)));
    }

    /**
     * @dataProvider caseProviderSort
     * @param $input
     * @param $expected
     * @param $args
     */
    function testSort($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->sort(...$args)));
    }

    /**
     * @dataProvider caseProviderTake
     * @param $input
     * @param $expected
     * @param $args
     */
    function testTake($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->take(...$args)));
    }

    /**
     * @dataProvider caseProviderTakeWhile
     * @param $input
     * @param $expected
     * @param $args
     */
    function testTakeWhile($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->take_while(...$args)));
    }

    /**
     * @dataProvider caseProviderToA
     * @param $input
     * @param $expected
     * @param $args
     */
    function testToA($input, $expected, $args) {
        $this->assertSame($expected, self::wrap($input)->to_a(...$args));
    }

    /**
     * @dataProvider caseProviderToAA
     * @param $input
     * @param $expected
     * @param $args
     */
    function testToAA($input, $expected, $args) {
        $this->assertSame($expected, self::wrap($input)->to_aa(...$args));
    }

    /**
     * @dataProvider caseProviderToAA
     * @param $input
     * @param $expected
     * @param $args
     */
    function testToH($input, $expected, $args) {
        $this->assertSame($expected, self::wrap($input)->to_h(...$args));
    }

    /**
     * @dataProvider caseProviderZip
     * @param $input
     * @param $expected
     * @param $args
     */
    function testZip($input, $expected, $args) {
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->zip(...$args)));
    }
}