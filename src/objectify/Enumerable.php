<?php

namespace whiskyjs\objectify;

trait Enumerable {
    public static $VALUES_ONLY = 1 << 1;
    public static $KEYS_ONLY = 1 << 2;

    public static $DISCARD_KEYS = 1 << 3;
    public static $PRESERVE_KEYS = 1 << 4;

    public static $DISABLE_EVAL = 1 << 5;
    public static $ENABLE_EVAL = 1 << 6;

    public static $REVERSE = 1 << 7;
    public static $BY_KEYS = 1 << 8;

    protected static function add($v, $k, &$result, $mode) {
        if (!($mode & self::$PRESERVE_KEYS)) {
            $result[] = $v;
        } else {
            $result[$k] = $v;
        }
    }

    protected static function create_lambda($lambda = null, $returns_value = true, $alt_args = null) {
        $std_args = '$v, $k, $i';

        if (is_callable($lambda)) {
            return $lambda;
        } else {
            if ($returns_value) {
                return create_function($alt_args ?: $std_args, 'return (' . ($lambda ?: '$v') . ');');
            } else {
                return create_function($alt_args ?: $std_args, $lambda . ';');
            }
        }
    }

    protected static function matches($pattern, $value) {
        return (bool) preg_match("#" . $pattern . "#", strval($value));
    }

    protected function _filter($lambda, $include = true, $mode = null) {
        return new static(function() use ($lambda, $include, $mode) {
            $fn = self::create_lambda($lambda);

            $i = 0;
            foreach ($this as $k => $v) {
                try {
                    $fn_result = $fn($v, $k, $i);

                    if ($fn_result && $include || !$fn_result && !$include) {
                        if (!($mode & self::$PRESERVE_KEYS)) {
                            yield $v;
                        } else {
                            yield $k => $v;
                        }
                    }
                } catch (Stop $e) {
                    break;
                } catch (Next $e) {
                    continue;
                }

                $i++;
            }
        });
    }

    protected function _first($n = null, $lambda = null, $mode = null) {
        return function() use ($n, $lambda, $mode) {
            if (isset($lambda)) {
                $fn = self::create_lambda($lambda);
            } else {
                $fn = null;
            }

            $counter = 0;
            if ($n !== null) {
                $limit = intval($n);
            } else {
                $limit = 1;
            }

            $i = 0;
            foreach ($this as $k => $v) {
                try {
                    if ($counter >= $limit) {
                        break;
                    }

                    if ($fn && $fn($v, $k, $i) || !$fn) {
                        if (!($mode & self::$PRESERVE_KEYS)) {
                            yield $v;
                        } else {
                            yield $k => $v;
                        }
                        $counter++;
                    }
                } catch (Stop $e) {
                    break;
                } catch (Next $e) {
                    continue;
                }

                $i++;
            }
        };
    }

    protected function _grep($pattern, $lambda = null, $mode = null, $include = true) {
        return new static(function() use ($pattern, $lambda, $mode, $include) {
            if (isset($lambda)) {
                $fn = self::create_lambda($lambda);
            } else {
                $fn = null;
            }

            $i = 0;
            foreach ($this as $k => $v) {
                try {
                    $is_rx_match = self::matches($pattern, $v);
                    $is_match =  $is_rx_match && (!$fn || $fn && $fn($v, $k, $i));

                    if ($include ? $is_match : !$is_match ) {
                        if (!($mode & self::$PRESERVE_KEYS)) {
                            yield $v;
                        } else {
                            yield $k => $v;
                        }
                    }

                } catch (Stop $e) {
                    break;
                } catch (Next $e) {
                    continue;
                }

                $i++;
            }
        });
    }

    protected function _slice($pattern_or_lambda, $mode = null, $before = true) {
        return new static(function() use ($pattern_or_lambda, $mode, $before) {
            if (is_callable($pattern_or_lambda) || is_string($pattern_or_lambda) && $mode & Enumerable::$ENABLE_EVAL) {
                $fn = self::create_lambda($pattern_or_lambda);
            } else {
                $fn = null;
            }

            $acc = [];
            $i = 0;
            foreach ($this as $k => $v) {
                try {
                    $result = $fn ? $fn($v, $k, $i) : self::matches($pattern_or_lambda, $v);

                    if (!$result) {
                        self::add($v, $k, $acc, $mode);
                    } else {
                        if ($before) {
                            if ($acc) {
                                yield $acc;
                            }
                            $acc = [];
                            self::add($v, $k, $acc, $mode);
                        } else {
                            self::add($v, $k, $acc, $mode);
                            yield $acc;
                            $acc = [];
                        }
                    }
                } catch (Stop $e) {
                    break;
                } catch (Next $e) {
                    continue;
                }

                $i++;
            }

            if ($acc) {
                yield $acc;
            }
        });
    }

    protected function _sort($lambda = null, $mode = null) {
        $ary = iterator_to_array($this);

        if (!$lambda) {
            if (!($mode & self::$PRESERVE_KEYS) && !($mode & self::$BY_KEYS)) {
                if (!($mode & self::$REVERSE)) {
                    sort($ary);
                } else {
                    rsort($ary);
                }
            } else {
                if ($mode & self::$BY_KEYS) {
                    if (!($mode & self::$REVERSE)) {
                        ksort($ary);
                    } else {
                        krsort($ary);
                    }
                } else {
                    if (!($mode & self::$REVERSE)) {
                        asort($ary);
                    } else {
                        arsort($ary);
                    }
                }
            }
        } else {
            if (!($mode & self::$BY_KEYS)) {
                $fn = self::create_lambda($lambda, true, '$v1, $v2');

                if (!($mode & self::$PRESERVE_KEYS)) {
                    usort($ary, $fn);
                } else {
                    uasort($ary, $fn);
                }
            } else {
                $fn = self::create_lambda($lambda, true, '$k1, $k2');

                uksort($ary, $fn);
            }
        }

        return new static($ary);
    }

    /**
     * Enumerates over the collection, chunking consequtive elements together based on the return value of the $lambda.
     * null specifies that the element should be dropped. Does not support :\_separator and :\_alone.
     * <br><br>
     * Preserves keys in associative collections.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function achunk($lambda, $mode = null) {
        return $this->chunk($lambda, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Enumerates over the collection, chunking adjacent elements together based on return value of the $lambda.
     * <br><br>
     * Preserves keys in associative collections.
     * <br><br>
     * Example: $_(['A' => -1, 'B' => 2, 'C' => 3])->chunk_while('$v1 + 1 === $v2')
     * <br><br>
     * Result: [['A' => -1], ['B' => 2, 'C' => 3]]
     *
     * @param callable|string $lambda ($v1, $v2, $k1, $k2, $i)
     * @param null|int $mode
     * @return static
     */
    public function achunk_while($lambda, $mode = null) {
        return $this->chunk_while($lambda, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Alias for #amap.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function acollect($lambda, $mode = null) {
        return $this->map($lambda, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Alias for #afind.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param mixed $if_none
     * @param null|int $mode
     * @return mixed
     */
    public function adetect($lambda, $if_none = null, $mode = null) {
        return $this->find($lambda, $if_none, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Drops first $n elements from collection, and returns rest elements.
     * <br><br>
     * Preserves keys in associative collections.
     *
     * @param int $n
     * @param null|int $mode
     * @return static
     */
    public function adrop($n, $mode = null) {
        return $this->drop($n, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Drops elements up to, but not including, the first element for which the $lambda returns falsy value
     * and returns the remaining elements.
     * <br><br>
     * Preserves keys in associative collections.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function adrop_while($lambda, $mode = null) {
        return $this->drop_while($lambda, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Returns array with value and key of the first element in collection for which the given $lambda returns
     * a truthy value.
     *
     * @param callable|string $lambda
     * @param mixed $if_null
     * @param null|int $mode
     * @return mixed
     */
    public function afind($lambda, $if_null = null, $mode = null) {
        return $this->find($lambda, $if_null, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Returns all elements of collection for which the given $lambda returns a truthy value.
     * <br><br>
     * Preserves keys in associative collections.
     *
     * @param $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     *
     */
    public function afind_all($lambda, $mode = null) {
        return $this->select($lambda, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * If $n is specified, returns first $n elements in collection for which $lambda returns a truthy value.
     * Otherwise returns array with value and key of the first matching element.
     * If none matching are found, returns null
     *
     * @param null|int $n
     * @param null|int $mode
     * @return mixed
     */
    public function afirst($n = null, $mode = null) {
        return $this->first($n, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Returns concatenated results of calling $lambda once for every element in collection.
     * <br><br>
     * Preserves keys in associative collections.
     *
     * @param callable|string $lambda ($v, $k, $i): array
     * @param null|int $mode
     * @return static
     */
    public function aflat_map($lambda, $mode = null) {
        return $this->flat_map($lambda, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Traverses the collection and yields elements whose string value matches the regular expression $pattern.
     * If $lambda is provided, it also must return truthy value for an element to be yielded.
     *
     * Preserves keys in associative arrays.
     *
     * @param string $pattern
     * @param null|callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function agrep($pattern, $lambda = null, $mode = null) {
        return $this->_grep($pattern, $lambda, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Traverses the collection and yields elements whose string value does not match the regular expression $pattern.
     * If $lambda is provided, it also must return truthy value for an element to be skipped.
     *
     * Preserves keys in associative arrays.
     *
     * @param string $pattern
     * @param null|callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function agrep_v($pattern, $lambda = null, $mode = null) {
        return $this->_grep($pattern, $lambda, $mode | self::$PRESERVE_KEYS, false);
    }

    /**
     * Passes each element of the enum to the given $lambda.
     * The method returns true if the $lambda never returns falsy value.
     * If the $lambda is not given, #all will return true when none of the collection values are falsy.
     *
     * @param null|callable|string $lambda ($v, $k, $i)
     * @return bool
     */
    public function all($lambda = null) {
        if (isset($lambda)) {
            $fn = self::create_lambda($lambda);
        } else {
            $fn = null;
        }

        $acc = true;

        $i = 0;
        foreach ($this as $k => $v) {
            try {
                $acc = $acc && (isset($fn) ? $fn($v, $k, $i) : $v);

                if (!$acc) {
                    break;
                }
            } catch (Stop $e) {
                break;
            } catch (Next $e) {
                continue;
            }

            $i++;
        }

        return $acc;
    }

    /**
     * Returns a new collection with the results of running $lambda once for every element in the current one.
     * <br><br>
     * Preserves keys in associative collections.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function amap($lambda, $mode = null) {
        return $this->map($lambda, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Passes each element of the collection to the given $lambda.
     * The method returns true if the $lambda ever returns a truthy value.
     * If the $lambda is not given, #any will return true if at least one of the collection values is truthy.
     *
     * @param null|callable|string $lambda ($v, $k, $i)
     * @return bool
     */
    public function any($lambda = null) {
        if (isset($lambda)) {
            $fn = self::create_lambda($lambda);
        } else {
            $fn = null;
        }

        $acc = false;

        $i = 0;
        foreach ($this as $k => $v) {
            try {
                if ($fn && $fn($v, $k, $i) || (!$fn && $v)) {
                    $acc = true;
                    break;
                }
            } catch (Stop $e) {
                break;
            } catch (Next $e) {
                continue;
            }

            $i++;
        }

        return $acc;
    }

    /**
     * Returns two arrays, the first containing the elements of collection for which the $lambda evaluates to
     * a truthy value, the second containing the rest.
     * <br><br>
     * Preserves keys in associative collections.
     *
     * @param $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function apartition($lambda, $mode = null) {
        return $this->partition($lambda, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Returns all elements of collection for which the given $lambda returns a falsy value.
     * <br><br>
     * Preserves keys in associative collections.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function areject($lambda, $mode = null) {
        return $this->_filter($lambda, false, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Sorts the collection by reverse natural order
     * <br><br>
     * Preserves keys in associative collections.
     *
     * @param null|int $mode
     * @return static
     */
    public function arsort($mode = null) {
        return $this->_sort(null, $mode | self::$PRESERVE_KEYS | Enumerable::$REVERSE);
    }

    /**
     * Returns all elements of collection for which the given $lambda returns a truthy value.
     * <br><br>
     * Preserves keys in associative collections.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function aselect($lambda, $mode = null) {
        return $this->_filter($lambda, true, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Chunks elements of the collection together, based on provided criteria.
     * If $pattern_or_lambda is a lambda and it returns truthy value, or is a pattern and it
     * matches the element's string value, the current element will be last in a chunk.
     *
     * Preserves keys in associative araays.
     *
     * @param string|callable $pattern_or_lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function aslice_after($pattern_or_lambda, $mode = null) {
        return $this->_slice($pattern_or_lambda, $mode | self::$PRESERVE_KEYS, false);
    }

    /**
     * Chunks elements of the collection together, based on provided criteria.
     * If $pattern_or_lambda is a lambda and it returns truthy value, or is a pattern and it
     * matches the element's string value, the current element will be first in a next chunk.
     *
     * Preserves keys in associative arrays.
     *
     * @param string|callable $pattern_or_lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function aslice_before($pattern_or_lambda, $mode = null) {
        return $this->_slice($pattern_or_lambda, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Sorts the collection using the given $lambda or by natural order if $lambda is not provided.
     * <br><br>
     * Preserves keys in associative collections.
     *
     * @param null|callable|string $lambda ($v1, $v2)
     * @param null $mode
     * @return static
     */
    public function asort($lambda = null, $mode = null) {
        return $this->_sort($lambda, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Returns first $n elements from collection.
     * <br><br>
     * Preserves keys in associative collections.
     *
     * @param int $n
     * @param null|int $mode
     * @return static
     */
    public function atake($n, $mode = null) {
        return $this->take($n, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Returns elements of collection until $lambda returns a falsy value.
     * <br><br>
     * Preserves keys in associative collections.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function atake_while($lambda, $mode = null) {
        return $this->take_while($lambda, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Takes one element from collection and merges with corresponding elements from $enums.
     * <br><br>
     * Preserves keys in associative collections.
     *
     * @param \Traversable|array|callable $enums
     * @param null|int $mode
     * @return static
     */
    public function azip($enums = [], $mode = null) {
        return $this->zip($enums, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Enumerates over the collection, chunking consequtive elements together based on the return value of the $lambda.
     * _null_ specifies that the element should be dropped. Does not support _:\_separator_ and _:\_alone_.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function chunk($lambda, $mode = null) {
        return new static(function() use ($lambda, $mode) {
            $fn = self::create_lambda($lambda);

            $acc = [];
            $prev_result = null;
            $i = 0;
            foreach ($this as $k => $v) {
                try {
                    $result = $fn($v, $k, $i);

                    if (isset($result)) {
                        if ($i > 0 && $result !== $prev_result) {
                            yield [$prev_result, $acc];
                            $acc = [];
                        }

                        self::add($v, $k, $acc, $mode);
                        $prev_result = $result;
                    }
                } catch (Stop $e) {
                    break;
                } catch (Next $e) {
                    continue;
                }

                $i++;
            }

            if ($acc) {
                yield [$prev_result, $acc];
            }
        });
    }

    /**
     * Enumerates over the collection, chunking adjacent elements together based on the return value of the $lambda.
     * <br><br>
     * Example: $_(['A' => -1, 'B' => 2, 'C' => 3])->chunk_while('$v1 + 1 === $v2')
     *
     * Result: [[-1], [2, 3]]
     *
     * @param callable|string $lambda ($v1, $v2, $k1, $k2, $i)
     * @param null|int $mode
     * @return static
     */
    public function chunk_while($lambda, $mode = null) {
        return new static(function() use ($lambda, $mode) {
            $fn = self::create_lambda($lambda, true, '$v1, $v2, $k1, $k2, $i');

            $acc = [];
            $prev_element = null;
            $i = 0;
            foreach ($this as $k => $v) {
                try {
                    if ($i > 0 && !$fn($prev_element[0], $v, $prev_element[1], $k, $i)) {
                        yield $acc;
                        $acc = [];
                    }

                    self::add($v, $k, $acc, $mode);
                    $prev_element = [$v, $k];
                } catch (Stop $e) {
                    break;
                } catch (Next $e) {
                    continue;
                }

                $i++;
            }

            if ($acc) {
                yield $acc;
            }
        });
    }

    /**
     * Alias for #map.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function collect($lambda, $mode = null) {
        return $this->map($lambda, $mode);
    }

    /**
     * Alias for #flat_map.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function collect_concat($lambda, $mode = null) {
        return $this->flat_map($lambda, $mode);
    }

    /**
     * Returns the number of items in collection through enumeration.
     * If an value is given, the number of items in collection that are equal to it are counted.
     * If a $lambda is given, it counts the number of elements returning a truthy value.
     * <br><br>
     * String $lambda bodies are disabled by default, set Enumerable::$ENABLE_EVAL flag to enable.
     *
     * @param mixed $value_or_lambda ($v, $k, $i)
     * @param null|int $mode
     * @return int
     */
    public function count($value_or_lambda = null, $mode = null) {
        $counter = 0;

        if (!isset($value_or_lambda)) {
            foreach ($this as $k => $v) {
                $counter++;
            }
        } else if (is_callable($value_or_lambda)
            || is_string($value_or_lambda) && ($mode & Enumerable::$ENABLE_EVAL)) {

            $fn = self::create_lambda($value_or_lambda);

            $i = 0;
            foreach ($this as $k => $v) {
                try {
                    if ($fn($v, $k, $i)) {
                        $counter++;
                    }
                } catch (Stop $e) {
                    break;
                } catch (Next $e) {
                    continue;
                }

                $i++;
            }
        } else {
            foreach ($this as $k => $v) {
                if ($v === $value_or_lambda) {
                    $counter++;
                }
            }
        }

        return $counter;
    }

    /**
     * Returns the number of items in collection through enumeration.
     * If an value is given, the number of items in collection that are equal to it are counted.
     * If a $lambda is given, it counts the number of elements returning a truthy value.
     * <br><br>
     * String $lambda bodies are enabled, use #count to search for string values.
     *
     * @param mixed $value_or_lambda ($v, $k, $i)
     * @param null|int $mode
     * @return int
     */
    public function count_eval($value_or_lambda = null, $mode = null) {
        return $this->count($value_or_lambda, $mode | Enumerable::$ENABLE_EVAL);
    }

    /**
     * Calls $lambda for each element of collection repeatedly n times or forever if null is given.
     * If a non-positive number is given or the collection is empty, does nothing.
     * \#cycle saves elements in an internal array so changes to collection after the first pass have no effect.
     *
     * @param null|int $n
     * @param callable|string $lambda ($v, $k, $i)
     *
     */
    public function cycle($n = null, $lambda) {
        $fn = self::create_lambda($lambda, false);

        $cache = [];
        for ($j = 0; isset($n) ? $j < $n : true; $j++) {
            $i = 0;
            foreach ($this as $k => $v) {
                try {
                    if ($j === 0) {
                        $cache[] = [$v, $k];
                        $fn($v, $k, $i);
                    } else {
                        $fn($cache[$i][0], $cache[$i][1], $i);
                    }
                } catch (Stop $e) {
                    break;
                } catch (Next $e) {
                    continue;
                }

                $i++;
            }
        }
    }

    /**
     * Alias for #find.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param mixed $if_none
     * @param null|int $mode
     * @return mixed
     */
    public function detect($lambda, $if_none = null, $mode = null) {
        return $this->find($lambda, $if_none, $mode);
    }

    /**
     * Drops first n elements from collection, and returns rest elements.
     *
     * @param int $n
     * @param null|int $mode
     * @return static
     */
    public function drop($n, $mode = null) {
        return new static(function() use ($n, $mode) {
            $counter = 0;

            foreach ($this as $k => $v) {
                if ($counter < $n) {
                    $counter++;
                } else {
                    if (!($mode & self::$PRESERVE_KEYS)) {
                        yield $v;
                    } else {
                        yield $k => $v;
                    }
                }
            }
        });
    }

    /**
     * Drops elements up to, but not including, the first element for which the $lambda returns falsy value
     * and returns the remaining elements.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function drop_while($lambda, $mode = null) {
        return new static(function() use ($lambda, $mode) {
            $fn = self::create_lambda($lambda);

            $i = 0;
            $stop_dropping = false;
            foreach ($this as $k => $v) {
                try {
                    if (!$stop_dropping) {
                        $stop_dropping = !$fn($v, $k, $i);
                    }

                    if ($stop_dropping) {
                        if (!($mode & self::$PRESERVE_KEYS)) {
                            yield $v;
                        } else {
                            yield $k => $v;
                        }
                    }
                } catch (Stop $e) {
                    break;
                } catch (Next $e) {
                    continue;
                }

                $i++;
            }
        });
    }

    /**
     * Calls $lambda for each element in collection.
     *
     * @param callable|string $lambda ($v, $k, $i)
     */
    public function each($lambda) {
        $fn = self::create_lambda($lambda, false);

        $i = 0;
        foreach ($this as $k => $v) {
            try {
                $fn($v, $k, $i);
            } catch (Stop $e) {
                break;
            } catch (Next $e) {
                continue;
            }

            $i++;
        }
    }

    /**
     * Calls $lambda for each array of consecutive elements in collection.
     *
     * @param int $n
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     */
    public function each_cons($n, $lambda, $mode = null) {
        $fn = self::create_lambda($lambda, false, '$v, $i');

        $i = 0;
        $acc = [];
        foreach ($this as $k => $v) {
            try {
                if (count($acc) < $n) {
                    self::add($v, $k, $acc, $mode);
                } else {
                    $fn($acc, $i++);
                    self::add($v, $k, $acc, $mode);
                    array_splice($acc, 0, 1);
                }
            } catch (Stop $e) {
                break;
            } catch (Next $e) {
                continue;
            }
        }

        if ($acc) {
            $fn($acc, $i);
        }
    }

    /**
     * De-facto alias of #each.
     *
     * @param callable|string $lambda ($v, $k, $i)
     */
    public function each_entry($lambda) {
        $this->each($lambda);
    }

    /**
     * Invokes the given $lambda for each slice of $n elements.
     *
     * @param int $n
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     */
    public function each_slice($n, $lambda, $mode = null) {
        $fn = self::create_lambda($lambda, false, '$v, $i');

        $i = 0;
        $acc = [];
        foreach ($this as $k => $v) {
            try {
                if (count($acc) < $n) {
                    self::add($v, $k, $acc, $mode);
                } else {
                    $fn($acc, $i++);
                    $acc = [];
                    self::add($v, $k, $acc, $mode);
                }
            } catch (Stop $e) {
                break;
            } catch (Next $e) {
                continue;
            }
        }

        if ($acc) {
            $fn($acc, $i);
        }
    }

    /**
     * De-facto alias of #each.
     *
     * @param callable|string $lambda ($v, $k, $i)
     */
    public function each_with_index($lambda) {
        $this->each($lambda);
    }

    /**
     * Invokes the given $lambda for each element with an arbitrary object given,
     * and returns the initially given object.
     *
     * @param mixed $object
     * @param callable|string $lambda ($v, $k, $i)
     * @return mixed
     */
    public function each_with_object($object, $lambda) {
        $fn = self::create_lambda($lambda, false, '$v, $k, $i, $o');

        $i = 0;
        foreach ($this as $k => $v) {
            try {
                $fn($v, $k, $i, $object);
            } catch (Stop $e) {
                break;
            } catch (Next $e) {
                continue;
            }

            $i++;
        }

        return $object;
    }

    /**
     * Returns value of the first element in collection for which the given $lambda returns a truthy value.
     * If Enumerable::PRESERVE_KEYS is set, returns an array with element value and key.
     *
     * @param callable|string $lambda
     * @param mixed $if_none
     * @param null|int $mode
     * @return mixed
     */
    public function find($lambda, $if_none = null, $mode = null) {
        $fn = self::create_lambda($lambda);

        $result = null;

        $i = 0;
        foreach ($this as $k => $v) {
            try {
                if ($fn($v, $k, $i)) {
                    if (!($mode & self::$PRESERVE_KEYS)) {
                        $result = $v;
                    } else {
                        $result = [$v, $k];
                    }

                    break;
                }
            } catch (Stop $e) {
                break;
            } catch (Next $e) {
                continue;
            }

            $i++;
        }

        return isset($result) ? WDispatcher::wrap($result) : $if_none;
    }

    /**
     * Returns all elements of collection for which the given $lambda returns a truthy value.
     *
     * @param $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     *
     */
    public function find_all($lambda, $mode = null) {
        return $this->select($lambda, $mode);
    }

    /**
     * Returns all elements of collection for which the given $lambda returns a truthy value.
     * <br><br>
     * String $lambda bodies are disabled by default, set Enumerable::$ENABLE_EVAL flag to enable.
     *
     * @param mixed $value_or_lambda
     * @param null|int $mode
     * @return null|int
     */
    public function find_index($value_or_lambda, $mode = null) {
        if (is_callable($value_or_lambda) || is_string($value_or_lambda) && ($mode & Enumerable::$ENABLE_EVAL)) {
            $fn = self::create_lambda($value_or_lambda);

            $i = 0;
            foreach ($this as $k => $v) {
                try {
                    if ($fn($v, $k, $i)) {
                        return $k;
                    }
                } catch (Stop $e) {
                    break;
                } catch (Next $e) {
                    continue;
                }

                $i++;
            }
        } else {
            foreach ($this as $k => $v) {
                if ($v === $value_or_lambda) {
                    return $k;
                }
            }
        }

        return null;
    }

    /**
     * Returns all elements of collection for which the given $lambda returns a truthy value.
     * <br><br>
     * String $lambda bodies are enabled, use #count to search for string values.
     *
     * @param mixed $value_or_lambda
     * @param null|int $mode
     * @return null|int
     */
    public function find_index_eval($value_or_lambda, $mode = null) {
        return $this->find_index($value_or_lambda, $mode | Enumerable::$ENABLE_EVAL);
    }

    /**
     * If $n is specified, returns first $n elements in collection for which $lambda returns a truthy value.
     * Otherwise returns value of the first matching element, or array with its value and key,
     * if self::$PRESERVE_KEYS flag is set.
     * If none matching are found, returns null
     *
     * @param null|int $n
     * @param null|int $mode
     * @return mixed
     */
    public function first($n = null, $mode = null) {
        $gen = $this->_first($n, null, $mode);

        if (!isset($n)) {
            $result = $gen();

            if ($result->valid()) {
                $first_key = $result->key();
                $first_value = $result->current();

                if (!($mode & self::$PRESERVE_KEYS)) {
                    return WDispatcher::wrap($first_value);
                } else {
                    return WDispatcher::wrap([$first_value, $first_key]);
                }
            } else {
                return null;
            }
        } else {
            return new static($gen);
        }
    }

    /**
     * Returns concatenated results of calling $lambda once for every element in collection.
     *
     * @param callable|string $lambda ($v, $k, $i): array
     * @param null|int $mode
     * @return static
     */
    public function flat_map($lambda, $mode = null) {
        return new static(function() use ($lambda, $mode) {
            $fn = self::create_lambda($lambda);

            $i = 0;
            foreach ($this as $k => $v) {
                try {
                    $resultAry = $fn($v, $k, $i);

                    foreach ($resultAry as $k1 => $v1) {
                        if (!($mode & self::$PRESERVE_KEYS)) {
                            yield $v1;
                        } else {
                            yield $k1 => $v1;
                        }
                    }
                } catch (Stop $e) {
                    break;
                } catch (Next $e) {
                    continue;
                }

                $i++;
            }
        });
    }

    /**
     * Traverses the collection and yields elements whose string value matches the regular expression $pattern.
     * If $lambda is provided, it also must return truthy value for an element to be yielded.
     *
     * @param string $pattern
     * @param null|callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function grep($pattern, $lambda = null, $mode = null) {
        return $this->_grep($pattern, $lambda, $mode);
    }

    /**
     * Traverses the collection and yields elements whose string value does not match the regular expression $pattern.
     * If $lambda is provided, it also must return truthy value for an element to be skipped.
     *
     * @param string $pattern
     * @param null|callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function grep_v($pattern, $lambda = null, $mode = null) {
        return $this->_grep($pattern, $lambda, $mode, false);
    }

    /**
     * Groups the collection by result of the $lambda.
     * Returns an associative array where the keys are the evaluated result from the $lambda and the values
     * are arrays of elements in the collection that correspond to the key.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function group_by($lambda, $mode = null) {
        $fn = self::create_lambda($lambda);

        $acc = [];
        $i = 0;
        foreach ($this as $k => $v) {
            try {
                $result = $fn($v, $k, $i);

                if (!isset($acc[$result])) {
                    $acc[$result] = [];
                }

                self::add($v, $k, $acc[$result], $mode);
            } catch (Stop $e) {
                break;
            } catch (Next $e) {
                continue;
            }

            $i++;
        }

        return new static($acc);
    }

    /**
     * Returns true if value of any element in collection equals $value.
     *
     * @param int $value
     * @return bool
     */
    public function includes($value) {
        $result = false;

        foreach ($this as $k => $v) {
            if ($v === $value) {
                $result = true;

                break;
            }
        }

        return $result;
    }

    /**
     * Alias for #reduce.
     *
     * @param mixed $init_value
     * @param callable|string $lambda
     * @return mixed
     */
    public function inject($init_value, $lambda) {
        return $this->reduce($init_value, $lambda);
    }

    /**
     * Sorts the collection by natural order of keys
     *
     * @param null|int $mode
     * @return static
     */
    public function krsort($mode = null) {
        return $this->_sort(null, $mode | Enumerable::$BY_KEYS | Enumerable::$REVERSE);
    }

    /**
     * Sorts the collection by natural order of keys
     *
     * @param null|callable|string $lambda ($k1, $k2)
     * @param null|int $mode
     * @return static
     */
    public function ksort($lambda = null, $mode = null) {
        return $this->_sort($lambda, $mode | Enumerable::$BY_KEYS);
    }

    /**
     * Returns a new collection with the results of running $lambda once for every element in the current one.
     *
     * @param callable|static $lambda
     * @param null|int $mode
     * @return static
     */
    public function map($lambda, $mode = null) {
        return new static(function() use ($lambda, $mode) {
            $fn = self::create_lambda($lambda);

            $i = 0;
            foreach ($this as $k => $v) {
                try {
                    $result = $fn($v, $k, $i);

                    if (is_array($result)) {
                        switch (true) {
                            case $mode & self::$VALUES_ONLY:
                                yield $k => $result[0];
                                break;
                            case $mode & self::$KEYS_ONLY:
                                yield $result[1] => $v;
                                break;
                            default:
                                yield $result[1] => $result[0];
                        }
                    } else {
                        if (!($mode & self::$PRESERVE_KEYS)) {
                            yield $result;
                        } else {
                            yield $k => $result;
                        }
                    }
                } catch (Stop $e) {
                    break;
                } catch (Next $e) {
                    continue;
                }

                $i++;
            }
        });
    }

    /**
     * Alias for #includes.
     *
     * @param int $value
     * @return bool
     */
    public function member($value) {
        return $this->includes($value);
    }

    /**
     * Passes each element of the collection to the given $lambda.
     * The method returns true if the $lambda never returns a truthy value for all elements.
     * If the $lambda is not given, #none will return true only if none of the collection members is truthy.
     *
     * @param null|callable|string $lambda ($v, $k, $i)
     * @return bool
     */
    public function none($lambda = null) {
        return !$this->any($lambda);
    }

    /**
     * Returns true if the given $lambda returns a truthy value for exactly one element in collection
     *
     * @param $lambda ($v, $k, $i)
     * @return bool
     */
    public function one($lambda = null) {
        if ($lambda) {
            $fn = self::create_lambda($lambda);
        } else {
            $fn = null;
        }

        $i = 0;
        $counter = 0;
        foreach ($this as $k => $v) {
            try {
                if ($fn && $fn($v, $k, $i) || !$fn && $v) {
                    $counter++;
                }
            } catch (Stop $e) {
                break;
            } catch (Next $e) {
                continue;
            }

            $i++;
        }

        return $counter === 1;
    }

    /**
     * Returns two arrays, the first containing the elements of collection for which the $lambda evaluates to
     * a truthy value, the second containing the rest.
     *
     * @param $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function partition($lambda, $mode = null) {
        $fn = self::create_lambda($lambda);

        $i = 0;
        $result = [[], []];
        foreach ($this as $k => $v) {
            try {
                if ($fn($v, $k, $i)) {
                    self::add($v, $k, $result[0], $mode);
                } else {
                    self::add($v, $k, $result[1], $mode);
                }
            } catch (Stop $e) {
                break;
            } catch (Next $e) {
                continue;
            }

            $i++;
        }

        return new static($result);
    }

    /**
     * Combines all elements of collection by applying an operation specified by $lambda.
     *
     * @param mixed $init_value
     * @param callable|string $lambda ($v, $a, $k, $i)
     * @return mixed
     */
    public function reduce($init_value, $lambda) {
        $fn = self::create_lambda($lambda, true, '$v, $a, $k, $i');

        $acc = $init_value;

        $i = 0;
        foreach ($this as $k => $v) {
            try {
                $acc = $fn($v, $acc, $k, $i);
            } catch (Stop $e) {
                break;
            } catch (Next $e) {
                continue;
            }

            $i++;
        }

        return WDispatcher::wrap($acc);
    }

    /**
     * Returns all elements of collection for which the given $lambda returns a falsy value.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function reject($lambda, $mode = null) {
        return $this->_filter($lambda, false, $mode);
    }

    /**
     * Sorts the collection by reverse natural order
     *
     * @param null|int $mode
     * @return static
     */
    public function rsort($mode = null) {
        return $this->_sort(null, $mode | Enumerable::$REVERSE);
    }

    /**
     * Returns all elements of collection for which the given $lambda returns a truthy value.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function select($lambda, $mode = null) {
        return $this->_filter($lambda, true, $mode);
    }

    /**
     * Chunks elements of the collection together, based on provided criteria.
     * If $pattern_or_lambda is a lambda and it returns truthy value, or is a pattern and it
     * matches the element's string value, the current element will be last in a chunk.
     *
     * @param string|callable $pattern_or_lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function slice_after($pattern_or_lambda, $mode = null) {
        return $this->_slice($pattern_or_lambda, $mode, false);
    }

    /**
     * Chunks elements of the collection together, based on provided criteria.
     * If $pattern_or_lambda is a lambda and it returns truthy value, or is a pattern and it
     * matches the element's string value, the current element will be first in a next chunk.
     *
     * @param string|callable $pattern_or_lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function slice_before($pattern_or_lambda, $mode = null) {
        return $this->_slice($pattern_or_lambda, $mode);
    }

    /**
     * Sorts the collection using the given $lambda or by natural order if $lambda is not provided.
     *
     * @param null|callable|string $lambda ($v1, $v2)
     * @param null $mode
     * @return static
     */
    public function sort($lambda = null, $mode = null) {
        return $this->_sort($lambda, $mode);
    }

    /**
     * Returns first $n elements from collection.
     *
     * @param int $n
     * @param null|int $mode
     * @return static
     */
    public function take($n, $mode = null) {
        return new static(function() use ($n, $mode) {
            $counter = 0;

            foreach ($this as $k => $v) {
                if ($counter >= $n) {
                    break;
                }

                if (!($mode & self::$PRESERVE_KEYS)) {
                    yield $v;
                } else {
                    yield $k => $v;
                }

                $counter++;
            }
        });
    }

    /**
     * Returns elements of collection until $lambda returns a falsy value.
     *
     * @param callable|string $lambda ($v, $k, $i)
     * @param null|int $mode
     * @return static
     */
    public function take_while($lambda, $mode = null) {
        return new static(function() use ($lambda, $mode) {
            $fn = self::create_lambda($lambda);

            $i = 0;
            foreach ($this as $k => $v) {
                try {
                    if ($fn($v, $k, $i)) {
                        if (!($mode & self::$PRESERVE_KEYS)) {
                            yield $v;
                        } else {
                            yield $k => $v;
                        }
                    } else {
                        break;
                    }
                } catch (Stop $e) {
                    break;
                } catch (Next $e) {
                    continue;
                }

                $i++;
            }
        });
    }

    /**
     * Forces current collection to an array, which is then returned.
     *
     * @param null|int $mode
     * @return array
     */
    public function to_a($mode = null) {
        return iterator_to_array($this, $mode);
    }

    /**
     * Forces current collection to an array, which is then returned.
     * <br><br>
     * Preserves keys in associative collections.
     *
     * @param null|int $mode
     * @return array
     */
    public function to_aa($mode = null) {
        return iterator_to_array($this, $mode | self::$PRESERVE_KEYS);
    }

    /**
     * Forces current collection to an array, which is then returned.
     * <br><br>
     * Preserves keys in associative collections.
     *
     * @param null|int $mode
     * @return array
     */
    public function to_h($mode = null) {
        return $this->to_aa($mode);
    }

    /**
     * Takes one element from collection and merges with corresponding elements from $enums.
     *
     * @param \Traversable|array|callable $enums
     * @param null|int $mode
     * @return static
     */
    public function zip($enums = [], $mode = null) {
        return new static(function() use ($enums, $mode) {
            $iters = [$this->getIterator()];
            foreach ($enums as $enum) {
                $iters[] = (new static($enum))->getIterator();
            }

            while (true) {
                $iters_valid = [];
                $has_valid_iters = false;

                foreach ($iters as $iter) {
                    $iter_valid = $iter->valid();
                    $iters_valid[] = $iter_valid;
                    $has_valid_iters |= $iter_valid;
                }

                if (!$has_valid_iters) {
                    break;
                }

                $result = [];
                if (!($mode & self::$PRESERVE_KEYS)) {
                    foreach ($iters as $k => $iter) {
                        $result[] = $iters_valid[$k] ? $iter->current() : null;
                    }
                } else {
                    foreach ($iters as $k => $iter) {
                        $result[$iters_valid[$k] ? $iter->key() : null] = $iters_valid[$k] ? $iter->current() : null;
                    }
                }

                yield $result;

                foreach ($iters as $iter) {
                    $iter->next();
                }
            }
        });
    }
}