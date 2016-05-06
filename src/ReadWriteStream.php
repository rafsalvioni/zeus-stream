<?php

namespace Zeus\Stream;

use Zeus\Stream\Read\ReadableStreamInterface;
use Zeus\Stream\Read\ReadTrait;
use Zeus\Stream\Write\WritableStreamInterface;
use Zeus\Stream\Write\WriteTrait;

/**
 * Represents a read-write stream, without seekable feature.
 * 
 * @author Rafael M. Salvioni
 */
class ReadWriteStream extends StreamWrapper implements WritableStreamInterface, ReadableStreamInterface
{
    use WritableStreamTrait, ReadableStreamTrait;
}
