[![Build Status](http://img.shields.io/travis/whiskyjs/objectify.svg?style=flat-square)](https://travis-ci.org/whiskyjs/objectify)
[![Dependency Status](http://img.shields.io/gemnasium/whiskyjs/objectify.svg?style=flat-square)](https://gemnasium.com/whiskyjs/objectify)
[![License](http://img.shields.io/:license-mit-blue.svg?style=flat-square)](http://badges.mit-license.org)

# objectify

Ruby-inspired object-oriented wrapper over builtin PHP types.

Currently implemented classes and mixins:

- Enumerable

## Usage

### Enumerable

Generally mirrors the functionality of Ruby's Enumerable with the following alterations:

- Due to the nature of array type implementation in PHP (as is it de-facto being an ordered map) methods
are denied knowledge of whether the collection is ordered or not and if the keys need to be preserved
in resulting iterable(s). To overcome the issue, the _$mode_ parameter containing optional 
flag _$PRESERVE_KEYS_ has been added to most standard methods, as well as dedicated shortcuts 
for associative arrays (**#amap**, **#aselect** etc).

- Short lambda bodies can be passed as strings. In that case _create_function_ will be used.

- All methods which return new iterable(s) are lazy by default. Use **#to_a** or **#to_aa** to force iterable to an array.

- All methods which used to use extended comparison operator (**===**) are now only accept regular expressions
string patterns which is then matched to a string value of an array item. 

## License
Published under [MIT license](https://github.com/whiskyjs/objectify/blob/master/LICENSE.txt).