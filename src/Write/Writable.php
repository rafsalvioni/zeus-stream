<?php

namespace Zeus\Stream\Write;

use Zeus\Stream\Stream;

/**
 * Manages a write only stream.
 * 
 * @author Rafael M. Salvioni
 * @package Zeus\Stream
 */
class Writable extends Stream implements WritableInterface
{
    use WriteTrait;
    
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
