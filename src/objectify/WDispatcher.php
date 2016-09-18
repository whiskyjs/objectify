<?php

namespace whiskyjs\objectify;

class WDispatcher {
    public static function wrap($any) {
        switch (true) {
            case WEnumerable::accepts($any):
                return new WEnumerable($any);
            default:
                return $any;
        }
    }
}