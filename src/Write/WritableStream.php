<?php

namespace Zeus\Stream\Write;

use Zeus\Stream\StreamWrapper;

/**
 * Manages a write only stream.
 * 
 * @author Rafael M. Salvioni
 * @package Zeus\Stream
 */
class WritableStream extends StreamWrapper implements WritableStreamInterface
{
    use WritableStreamTrait;
    
    /**
     * 
     * @param resource $stream
     * @throws \DomainException If a unwritable stream
     */
    public function __construct($stream)
    {
        parent::__construct($stream);
        if (!$this->isWritable()) {
            throw new \DomainException('Unwritable stream!');
        }
    }
}
