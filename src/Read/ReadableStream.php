<?php

namespace Zeus\Stream\Read;

use Zeus\Stream\StreamWrapper;

/**
 * Manages a read only stream.
 * 
 * @author Rafael M. Salvioni
 * @package Zeus\Stream
 */
class ReadableStream extends StreamWrapper implements ReadableStreamInterface
{
    use ReadableStreamTrait;
    
    /**
     * 
     * @param resource $stream
     * @throws \DomainException If a unreadable stream
     */
    public function __construct($stream)
    {
        parent::__construct($stream);
        if (!$this->isReadable()) {
            throw new \DomainException('Unreadable stream!');
        }
    }
}
