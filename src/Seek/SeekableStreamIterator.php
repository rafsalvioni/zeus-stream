<?php

namespace Zeus\Stream\Seek;

use Zeus\Stream\Read\ReadableStreamIterator as ReadIterator;

/**
 * Iterator for seekable streams.
 *
 * @author Rafael M. Salvioni
 */
class SeekableStreamIterator extends ReadIterator
{
    /**
     * 
     * @param SeekableStreamInterface $stream
     */
    public function __construct(SeekableStreamInterface $stream)
    {
        parent::__construct($stream);
    }
    
    /**
     * 
     * @return self
     */
    public function rewind()
    {
        $this->streamReader->seekBegin();
        return parent::rewind();
    }
}
