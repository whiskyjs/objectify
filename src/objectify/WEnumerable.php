<?php

namespace whiskyjs\objectify;

class WEnumerable extends WAny implements \IteratorAggregate {
    use Enumerable;

    public function __construct($any) {
        if (!static::accepts($any)) {
            throw new \InvalidArgumentException();
        }

        parent::__construct($any);
    }

    public static function accepts($any) {
        return (is_array($any)
            || ($any instanceof \Traversable)
            || (is_callable($any) && (new \ReflectionFunction($any))->isGenerator()));
    }

    public function getIterator() {
        if (is_callable($this->any)) {
            return $this->any->__invoke();
        } else {
            return new \ArrayIterator($this->any);
        }
    }
}