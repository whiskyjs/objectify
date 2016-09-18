<?php

namespace whiskyjs\objectify;

class NextTest extends \PHPUnit_Framework_TestCase {
    /**
     * @expectedException \whiskyjs\Objectify\Next
     */
    function test() {
        throw new Next();
    }
}