<?php

namespace whiskyjs\objectify;

class FlowControlTest extends \PHPUnit_Framework_TestCase {
    /**
     * @expectedException \whiskyjs\Objectify\FlowControl
     */
    function test() {
        throw new FlowControl();
    }
}