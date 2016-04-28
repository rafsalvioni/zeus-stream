<?php

namespace Zeus\Stream\Read;

use Zeus\Stream\Stream;

/**
 * Manages a read only stream.
 * 
 * @author Rafael M. Salvioni
 * @package Zeus\Stream
 */
class Readable extends Stream implements ReadableInterface
{
    use ReadTrait;
    
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
