<?php

namespace Zeus\Stream;

use Zeus\Stream\Read\ReadableInterface;
use Zeus\Stream\Read\ReadTrait;
use Zeus\Stream\Write\WritableInterface;
use Zeus\Stream\Write\WriteTrait;

/**
 * Represents a read-write stream, without seekable feature.
 * 
 * @author Rafael M. Salvioni
 */
class ReadWrite extends Stream implements WritableInterface, ReadableInterface
{
    use WriteTrait, ReadTrait;
}
