<?php

namespace whiskyjs\objectify;

function _($any) {
    return WDispatcher::wrap($any);
}

function e($any) {
    return new WEnumerable($any);
}