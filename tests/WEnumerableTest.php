<?php

namespace whiskyjs\objectify;

class WEnumerableTest extends \PHPUnit_Framework_TestCase {
    function caseProviderInvalid() {
        return [
            [
                1
            ],
            [
                1.01
            ],
            [
                "Totally a string"
            ],
            [
                function() {
                    return "Definitely not a generator";
                }
            ]
        ];
    }

    function caseProviderValid() {
        $ary = [
            "A" => 1,
            "B" => 2,
            "C" => 3,
            "D" => 4,
            "E" => 5,
            "F" => 6,
            "G" => 7,
            "H" => 8,
            "I" => 9,
            "J" => 0,
        ];

        $gen = function() use ($ary) {
            foreach ($ary as $k => $v) {
                yield $k => $v;
            }
        };

        return [
            [
                $ary,
                $ary,
                range(0, count($ary) - 1),
            ],
            [
                $gen,
                $ary,
                range(0, count($ary) - 1),
            ]
        ];
    }

    function caseProviderEnumerable() {
        $result = $this->caseProviderValid();

        foreach ($result as &$case) {
            $case[0] = new WEnumerable($case[0]);
        }

        return $result;
    }

    /**
     * @dataProvider caseProviderValid
     * @param $case
     */
    function testConstructCorrect($case) {
        new WEnumerable($case);
    }

    /**
     * @dataProvider caseProviderInvalid
     * @expectedException \InvalidArgumentException
     * @param $case
     */
    function testConstructInvalid($case) {
        new WEnumerable($case);
    }

    /**
     * @dataProvider caseProviderValid
     * @param $case
     */
    function testAny($case) {
        $this->assertSame($case, (new WEnumerable($case))->get());
    }
}