<?php

namespace whiskyjs\objectify;

class WAny {
    protected $any;

    public function __construct($any) {
        $this->any = $any;
    }

    public function get() {
        return $this->any;
    }
}