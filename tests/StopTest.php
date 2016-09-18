<?php

namespace whiskyjs\objectify;

class StopTest extends \PHPUnit_Framework_TestCase {
    /**
     * @expectedException \whiskyjs\Objectify\Stop
     */
    function test() {
        throw new Stop();
    }
}