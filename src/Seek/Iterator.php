<?php

namespace Zeus\Stream\Seek;

use Zeus\Stream\Read\Iterator as ReadIterator;

/**
 * Iterator for seekable streams.
 *
 * @author Rafael M. Salvioni
 */
class Iterator extends ReadIterator
{
    /**
     * 
     * @param SeekableInterface $stream
     */
    public function __construct(SeekableInterface $stream)
    {
        parent::__construct($stream);
    }
    
    /**
     * 
     * @return self
     */
    public function rewind()
    {
        $this->streamReader->cursorBegin();
        return parent::rewind();
    }
}
