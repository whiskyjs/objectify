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
            [[1, 4, 5, 8], true, [function($v, $k, $i) {
                if ($i === 3) {
                    throw new Stop();
                }

                return $v < 8;
            }]],
            [[2, 4, 5, 6], true, [function($v, $k, $i) {
                static $prev_i = [];

                if ($i == 2) {
                    throw new Next();
                }

                if (in_array($i, $prev_i)) {
                    throw new \LogicException();
                } else {
                    $prev_i[] = $i;
                    return $v % 2 == 0;
                }
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
            [[1, 4, 5, 8], false, [function($v, $k, $i) {
                if ($i === 3) {
                    throw new Stop();
                }

                return $v === 8;
            }]],
            [[2, 4, 5, 6], false, [function($v, $k, $i) {
                static $prev_i = [];

                if ($i == 2) {
                    throw new Next();
                }

                if (in_array($i, $prev_i)) {
                    throw new \LogicException();
                } else {
                    $prev_i[] = $i;
                    return $v === 5;
                }
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
            [
                [1, 2, 5, 8],
                [
                    [1, [1]],
                    [0, [2]],
                ],
                [
                    function($v, $k, $i) {
                        if ($i === 2) {
                            throw new Stop();
                        }

                        return $v % 2;
                    }
                ]
            ],
            [
                [2, 4, 5, 8],
                [
                    [0, [2, 4, 8]],
                ],
                [
                    function($v, $k, $i) {
                        static $prev_i = [];

                        if ($i == 2) {
                            throw new Next();
                        }

                        if (in_array($i, $prev_i)) {
                            throw new \LogicException();
                        } else {
                            $prev_i[] = $i;
                            return $v % 2;
                        }
                    }
                ],
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
            [
                [1, 2, 5, 8],
                [
                    [1, 2]
                ],
                [
                    function($v1, $v2, $k1, $k2, $i) {
                        if ($i === 2) {
                            throw new Stop();
                        }

                        return $v1 < 3;
                    }
                ]
            ],
            [
                [2, 4, 5, 8],
                [
                    [2, 4, 8]
                ],
                [
                    function($v1, $v2, $k1, $k2, $i) {
                        static $prev_i = [];

                        if ($i == 2) {
                            throw new Next();
                        }

                        if (in_array($i, $prev_i)) {
                            throw new \LogicException();
                        } else {
                            $prev_i[] = $i;
                            return $v1 % 2 === 0;
                        }
                    }
                ],
            ],
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
            [[1, 5, 5, 8], 1, [function($v, $k, $i) {
                if ($i === 2) {
                    throw new Stop();
                }

                return $v === 5;
            }]],
            [[2, 5, 8, 5], 2, [function($v, $k, $i) {
                static $prev_i = [];

                if ($i == 2) {
                    throw new Next();
                }

                if (in_array($i, $prev_i)) {
                    throw new \LogicException();
                } else {
                    $prev_i[] = $i;
                    return $v === 5;
                }
            }]],
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
            [
                [1, 4, 5, 8],
                [
                    [1, 0, 0],
                    [4, 1, 1],
                    [1, 0, 0],
                    [4, 1, 1],
                ],
                [
                    2,
                    function($v, $k, $i) {
                        if ($i === 2) {
                            throw new Stop();
                        }
                    }
                ]
            ],
            [
                [2, 4, 5, 6],
                [
                    [2, 0, 0],
                    [4, 1, 1],
                    [6, 3, 3],
                    [2, 0, 0],
                    [4, 1, 1],
                    [6, 3, 3],
                ],
                [
                    2,
                    function($v, $k, $i) {
                        if ($i == 2) {
                            throw new Next();
                        }
                    }
                ]
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
            [[5, 7, 9, 11, 8, 12], [9, 11, 8, 12], [function($v, $k, $i) {
                static $prev_i = [];

                if ($i === 2) {
                    throw new Stop();
                }

                if ($prev_i === $i) {
                    return false;
                } else {
                    $prev_i = $i;
                    return $v % 2;
                }
            }]],
            [[5, 7, 9, 11, 8, 12], [7, 8, 12], [function($v, $k, $i) {
                static $prev_i = [];

                if ($i === 1) {
                    throw new Next();
                }

                if (in_array($i, $prev_i)) {
                    throw new \LogicException();
                } else {
                    $prev_i[] = $i;
                    return $v % 2;
                }
            }]],
        ];
    }

    function caseProviderEach() {
        return [
            [[], [], []],
            [['A' => 4, 'B' => 5], [[4, 'A', 0], [5, 'B', 1]], []],
            [
                [1, 2, 5, 6],
                [
                    [1, 0, 0],
                    [2, 1, 1],
                ],
                [
                    function($v, $k, $i) {
                        if ($i === 2) {
                            throw new Stop();
                        }
                    }
                ]
            ],
            [
                [1, 2, 5, 6],
                [
                    [1, 0, 0],
                    [2, 1, 1],
                    [6, 3, 3],
                ],
                [
                    function($v, $k, $i) {
                        if ($i === 2) {
                            throw new Next();
                        }
                    }
                ]
            ]
        ];
    }

    function caseProviderEachGlobals() {
        return [
            [[], [], []],
            [['A' => 4, 'B' => 5], [[4, 'A', 0], [5, 'B', 1]], []],
            [
                [1, 2, 5, 6],
                [
                    [1, 0, 0],
                    [2, 1, 1],
                ],
                [
                    function($v, $k, $i) {
                        if ($i === 2) {
                            throw new Stop();
                        }
                    }
                ]
            ],
            [
                [1, 2, 5, 6],
                [
                    [1, 0, 0],
                    [2, 1, 1],
                    [6, 3, 3],
                ],
                [
                    function($v, $k, $i) {
                        if ($i === 2) {
                            throw new Next();
                        }
                    }
                ]
            ],
            [['A' => 4, 'B' => 5], [[4, 'A', 0], [5, 'B', 1]], ['if (($i > 0) && ($GLOBALS[\'caseResult\'][$i - 1][2] !== $i - 1)) throw new \LogicException();']],
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
            [
                [1, 2, 3, 5, 6],
                [
                    [[1, 2], 0],
                    [[2, 3], 1],
                ],
                [2, function($v, $i) {
                    if ($i === 2) {
                        throw new Stop();
                    }
                }]
            ],
            [
                [1, 2, 3, 5, 6],
                [
                    [[1, 2], 0],
                    [[2, 3], 1],
                ],
                [2, function($v, $i) {
                    if ($v === [3, 5]) {
                        throw new Stop();
                    }
                }]
            ],
            [
                [1, 2, 3, 5, 6],
                [
                    [[1, 2], 0],
                    [[2, 3], 1],
                    [[5, 6], 2],
                ],
                [2, function($v, $i) {
                    if ($v === [3, 5]) {
                        throw new Next();
                    }
                }]
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
            [
                [1, 2, 3, 5, 6],
                [
                    [[1, 2], 0],
                ],
                [2, function($v, $i) {
                    if ($v === [3, 5]) {
                        throw new Stop();
                    }
                }]
            ],
            [
                [1, 2, 3, 5, 6],
                [
                    [[1, 2], 0],
                    [[6], 1],
                ],
                [2, function($v, $i) {
                    if ($v === [3, 5]) {
                        throw new Next();
                    }
                }]
            ],
        ];
    }

    function caseProviderEachWithObject() {
        return [
            [[], [], ["object"]],
            [['A' => 4, 'B' => 5], [[4, "object", 'A', 0], [5, "object", 'B', 1]], ["object"]],
            [
                [1, 4, 6, 2],
                [
                    [1, "object", 0, 0],
                    [4, "object", 1, 1],
                    [2, "object", 3, 3],
                ],
                [
                    "object",
                    function($v, $o, $k, $i) {
                        if ($i === 2) {
                            throw new Next();
                        }
                    }
                ]
            ],
            [
                [1, 4, 6, 2],
                [
                    [1, "object", 0, 0],
                    [4, "object", 1, 1],
                ],
                [
                    "object",
                    function($v, $o, $k, $i) {
                        if ($i === 2) {
                            throw new Stop();
                        }
                    }
                ]
            ]
        ];
    }

    function caseProviderFind() {
        return [
            [[], null, ['$v == 2']],
            [[1, 2, 3, 4, 5], 4, ['$k === 3']],
            [[1, 2, 3, 4, 5], null, ['$k === 8']],
            [
                [1, 5, 3, 12, 91],
                null,
                [
                    function($v, $k, $i) {
                        if ($i === 2) {
                            throw new Stop();
                        }

                        return $v === 91;
                    }
                ]
            ],
            [
                [1, 5, 3, 12, 91],
                null,
                [
                    function($v, $k, $i) {
                        static $prev_i = [];

                        if ($i === 1) {
                            throw new Next();
                        }

                        if (in_array($i, $prev_i)) {
                            throw new \LogicException();
                        } else {
                            $prev_i[] = $i;
                            return $v === 5;
                        }
                    }
                ]
            ],
        ];
    }

    function caseProviderFindIndex() {
        return [
            [[], null, [128]],
            [['A' => 1, 'C' => 3, 'E' => 5], null, [16]],
            [['A' => 1, 'C' => 3, 'E' => 5], 'C', [3]],
            [['A' => 'one', 'C' => 'three', 'E' => 'five'], 'C', ["three"]],
            [['A' => 'one', 'C' => 'three', 'E' => 'five'], 'E', ['$v === "five"', Enumerable::$ENABLE_EVAL]],
            [['A' => 'one', 'C' => 'three', 'E' => 'five'], 'E', [function($v) {
                return $v === "five";
            }]],
            [['A' => 'one', 'C' => 'three', 'E' => 'five'], null, ['$v === "nine"']],
            [
                [1, 5, 3, 12, 91],
                null,
                [
                    function($v, $k, $i) {
                        if ($i === 2) {
                            throw new Stop();
                        }

                        return $v === 91;
                    }
                ]
            ],
            [
                [1, 5, 3, 12, 91],
                null,
                [
                    function($v, $k, $i) {
                        static $prev_i = [];

                        if ($i === 1) {
                            throw new Next();
                        }

                        if (in_array($i, $prev_i)) {
                            throw new \LogicException();
                        } else {
                            $prev_i[] = $i;
                            return $v === 5;
                        }
                    }
                ]
            ],
        ];
    }

    function caseProviderFindIndexEval() {
        return [
            [[], null, [128]],
            [['A' => 1, 'C' => 3, 'E' => 5], null, [16]],
            [['A' => 1, 'C' => 3, 'E' => 5], 'C', [3]],
            [['A' => 'one', 'C' => 'three', 'E' => 'five'], 'A', ["'three'"]],
            [['A' => 'one', 'C' => 'three', 'E' => 'five'], 'E', ['$v === "five"']],
            [['A' => 'one', 'C' => 'three', 'E' => 'five'], 'E', [function($v) {
                return $v === "five";
            }]],
            [['A' => 'one', 'C' => 'three', 'E' => 'five'], null, ['$v === "nine"']],
        ];
    }

    function caseProviderFirst() {
        return [
            [[], null, []],
            [[1, 2, 3, 4, 5], 1, []],
            [[1, 2, 3, 4, 5], [1, 2, 3], [3]],
            [[1, 2, 3, 4, 5], [1, 2, 3], [3]],
        ];
    }

    function caseProviderFlatMap() {
        return [
            [[], [], [null]],
            [[1, 2, 3], [2, 0, 4, 4, 6, 8], ['[$v * 2, $k * 4]']],
            [[1, 2, 3], [-1, 0, 1], ['[$k - 1]']],
            [[1, 2, 3], ['00', '11', '22'], ['[$i . $i]']],
            [
                [1, 12, 3, 7, 6],
                ['001', '1112'],
                [
                    function($v, $k, $i) {
                        if ($i === 2) {
                            throw new Stop();
                        }

                        return [$i . $k . $v];
                    }
                ]
            ],
            [
                [1, 12, 3, 7, 6],
                ['001', '1112', '337', '446'],
                [
                    function($v, $k, $i) {
                        static $prev_i = [];

                        if ($i === 2) {
                            throw new Next();
                        }

                        if (in_array($i, $prev_i)) {
                            throw new \LogicException();
                        } else {
                            $prev_i[] = $i;
                            return [$i . $k . $v];
                        }
                    }
                ]
            ],
        ];
    }

    function caseProviderGrep() {
        return [
            [[], [], [null]],
            [[12, 23, 34, 14, 50], [12, 34, 14], ['^[13]']],
            [[12, 23, 34, 14, 50], [34], ['^[13]', '$v > 30']],
            [
                [12, 23, 34, 50, 24],
                [23],
                [
                    '^[123]',
                    function($v, $k, $i) {
                        if ($i === 2) {
                            throw new Stop();
                        }

                        return $v > 20;
                    }
                ]
            ],
            [
                [12, 23, 34, 50, 24],
                [23, 24],
                [
                    '^[123]',
                    function($v, $k, $i) {
                        static $prev_i = [];

                        if ($i === 2) {
                            throw new Next();
                        }

                        if (in_array($i, $prev_i)) {
                            throw new \LogicException();
                        } else {
                            $prev_i[] = $i;
                            return $v > 20;
                        }
                    }
                ]
            ],
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
            [['A' => 1, 'C' => 3, 'F' => 6], [1 => [1, 3], 0 => [6]], [function($v) {
                return $v % 2;
            }]],
            [['A' => 1, 'C' => 3, 'F' => 6], [1 => ['A' => 1, 'C' => 3], 0 => ['F' => 6]],
                [function($v) {
                    return $v % 2;
                }, Enumerable::$PRESERVE_KEYS]
            ],
            [
                [5, 2, 12, 8, 13],
                [
                    1 => [5],
                    0 => [2, 12],
                ],
                [
                    function($v, $k, $i) {
                        if ($i == 3) {
                            throw new Stop();
                        }

                        return $v % 2;
                    }
                ]
            ],
            [
                [5, 2, 12, 8, 13],
                [
                    1 => [5, 13],
                    0 => [2, 12],
                ],
                [
                    function($v, $k, $i) {
                        static $prev_i = [];

                        if ($i == 3) {
                            throw new Next();
                        }

                        if (in_array($i, $prev_i)) {
                            throw new \LogicException();
                        } else {
                            $prev_i[] = $i;
                            return $v % 2;
                        }
                    }
                ]
            ]
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
            [
                [3, 5, 2, 7, 12],
                [6, 10, 4],
                [
                    function($v, $k, $i) {
                        if ($i === 3) {
                            throw new Stop();
                        }

                        return $v * 2;
                    }
                ]
            ],
            [
                [3, 5, 2, 7, 12],
                [6, 10, 4, 24],
                [
                    function($v, $k, $i) {
                        static $prev_i = [];

                        if ($i === 3) {
                            throw new Next();
                        }

                        if (in_array($i, $prev_i)) {
                            throw new \LogicException();
                        } else {
                            $prev_i[] = $i;
                            return $v * 2;
                        }
                    }
                ]
            ]
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
            [
                [6, 5, 12, 11, 7],
                false,
                [
                    function ($v, $k, $i) {
                        if ($i === 3) {
                            throw new Stop();
                        }

                        return $v === 7;
                    }
                ]
            ],
            [
                [6, 5, 12, 11, 7],
                false,
                [
                    function ($v, $k, $i) {
                        static $prev_i = [];

                        if ($i === 3) {
                            throw new Next();
                        }

                        if (in_array($i, $prev_i)) {
                            throw new \LogicException();
                        } else {
                            $prev_i[] = $i;
                            return $v === 11;
                        }
                    }
                ]
            ]
        ];
    }

    function caseProviderPartition() {
        return [
            [[], [[], []], [null]],
            [[1, 2, 3, 4], [[1, 3], [2, 4]], ['$v % 2']],
            [[1, 2, 3, 4], [[1, 2 => 3], [1 => 2, 3 => 4]], ['$v % 2', Enumerable::$PRESERVE_KEYS]],
            [
                [1, 2, 3, 4],
                [
                    [1],
                    [2, 4]
                ],
                [
                    function($v, $k, $i) {
                        static $prev_i = [];

                        if ($i === 2) {
                            throw new Next();
                        }

                        if (in_array($i, $prev_i)) {
                            throw new \LogicException();
                        } else {
                            $prev_i[] = $i;
                            return $v % 2;
                        }
                    }
                ]
            ],
            [
                [1, 2, 3, 4],
                [
                    [1],
                    [2]
                ],
                [
                    function($v, $k, $i) {
                        if ($i === 2) {
                            throw new Stop();
                        }

                        return $v % 2;
                    }
                ]
            ]
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
            [
                [4, 3, 56, 1],
                "43",
                [
                    "",
                    function ($v, $a, $k, $i) {
                        if ($i === 2) {
                            throw new Stop();
                        }

                        return $a . $v;
                    }
                ]
            ],
            [
                [4, 3, 56, 1],
                "431",
                [
                    "",
                    function ($v, $a, $k, $i) {
                        static $prev_i = [];

                        if ($i === 2) {
                            throw new Next();
                        }

                        if (in_array($i, $prev_i)) {
                            throw new \LogicException();
                        } else {
                            $prev_i[] = $i;
                            return $a . $v;
                        }
                    }
                ]
            ]
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
            [
                [1, 2, 4, 3, 7],
                [1],
                [
                    function($v, $k, $i) {
                        if ($i === 3) {
                            throw new Stop();
                        }

                        return $v % 2;
                    }
                ]
            ],
            [
                [1, 2, 4, 3, 7],
                [1, 7],
                [
                    function($v, $k, $i) {
                        static $prev_i = [];

                        if ($i === 3) {
                            throw new Next();
                        }

                        if (in_array($i, $prev_i)) {
                            throw new \LogicException();
                        } else {
                            $prev_i[] = $i;
                            return $v % 2;
                        }
                    }
                ]
            ],
        ];
    }

    function caseProviderSliceAfter() {
        return [
            [[], [], [null]],
            [[1], [[1]], [null]],
            [[1, 2, 3, 5, 8, 13, 21, 34], [[1], [2], [3, 5, 8, 13], [21], [34]], ['^[12]']],
            [[1, 2, 3, 5, 8, 13, 21, 34], [[1], [2, 3], [5], [8, 13], [21], [34]], ['$v % 2', Enumerable::$ENABLE_EVAL]],
            [[1, 2, 3, 5, 8, 13, 21, 34], [[1], [2], [3, 5, 8, 13], [21], [34]], ['^[12]']],
            [
                [1, 2, 3, 5, 8, 13, 21, 34],
                [[1], [2], [3]],
                [
                    function($v, $k, $i) {
                        if ($i === 3) {
                            throw new Stop();
                        }

                        return preg_match('#^[12]#', $v);
                    }
                ]
            ],
            [
                [1, 2, 3, 5, 8, 13, 21, 34],
                [[1], [2], [3, 8, 13], [21], [34]],
                [
                    function($v, $k, $i) {
                        static $prev_i = [];

                        if ($i === 3) {
                            throw new Next();
                        }

                        if (in_array($i, $prev_i)) {
                            throw new \LogicException();
                        } else {
                            $prev_i[] = $i;
                            return preg_match('#^[12]#', $v);
                        }
                    }
                ]
            ],
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
            [
                [5, 7, 8, 13],
                [5, 7],
                [
                    function($v, $k, $i) {
                        if ($i === 2) {
                            throw new Stop();
                        }

                        return $v < 10;
                    }
                ]
            ],
            [
                [5, 7, 8, 13],
                [5, 8],
                [
                    function($v, $k, $i) {
                        static $prev_i = [];

                        if ($i === 1) {
                            throw new Next();
                        }

                        if (in_array($i, $prev_i)) {
                            throw new \LogicException();
                        } else {
                            $prev_i[] = $i;
                            return $v < 10;
                        }
                    }
                ]
            ],
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
                [[[10 => 'A', 'Y' => 'B', 'Z' => 'C']], Enumerable::$PRESERVE_KEYS]
            ],
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
        $this->assertSame($expected, iterator_to_array(self::wrap($input)->collect(...$args)));
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

        self::wrap($input)->cycle($args[0], function($v, $k, $i) use (&$caseResult, $args) {
            if (isset($args[1])) {
                call_user_func($args[1], $v, $k, $i);
            }
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

        self::wrap($input)->each(function($v, $k, $i) use (&$caseResult, $args) {
            if (isset($args[0])) {
                call_user_func($args[0], $v, $k, $i);
            }
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

        self::wrap($input)->each_cons($args[0], function($v, $i) use (&$caseResult, $args) {
            if (isset($args[1])) {
                call_user_func($args[1], $v, $i);
            }
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

        self::wrap($input)->each_entry(function($v, $k, $i) use (&$caseResult, $args) {
            if (isset($args[0])) {
                call_user_func($args[0], $v, $k, $i);
            }
            $caseResult[] = [$v, $k, $i];
        });

        $this->assertSame($expected, $caseResult);
    }

    /**
     * @dataProvider caseProviderEachGlobals
     * @param $input
     * @param $expected
     * @param $args
     */
    function testEachGlobals($input, $expected, $args) {
        $GLOBALS['caseResult'] = [];

        $wrappee = self::wrap($input);

        $wrappee->each(function($v, $k, $i) use ($args, $wrappee) {
            if (isset($args[0])) {
                if (is_callable($args[0])) {
                    call_user_func($args[0], $v, $k, $i);
                } else {
                    $fn = self::invoke_create_lambda($wrappee, [$args[0], false]);
                    $fn($v, $k, $i);
                }
            }
            $GLOBALS["caseResult"][] = [$v, $k, $i];
        });

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

        self::wrap($input)->each_slice($args[0], function($v, $i) use (&$caseResult, $args) {
            if (isset($args[1])) {
                call_user_func($args[1], $v, $i);
            }
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

        self::wrap($input)->each_with_index(function($v, $k, $i) use (&$caseResult, $args) {
            if (isset($args[0])) {
                call_user_func($args[0], $v, $k, $i);
            }
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

        $caseObject = self::wrap($input)->each_with_object($args[0], function($v, $o, $k, $i) use (&$caseResult, $args) {
            if (isset($args[1])) {
                call_user_func($args[1], $v, $o, $k, $i);
            }
            $caseResult[] = [$v, $o, $k, $i];
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