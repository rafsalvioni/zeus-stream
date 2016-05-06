<?php

namespace Zeus\Stream\Read;

/**
 * Iterator for readable streams.
 *
 * @author Rafael M. Salvioni
 */
class ReadableStreamIterator implements \Iterator
{
    /**
     * Stream reader
     * 
     * @var ReadableStreamInterface 
     */
    protected $streamReader;
    /**
     * Current line key
     * 
     * @var int
     */
    protected $currentLine;
    
    /**
     * 
     * @param ReadableStreamInterface $stream
     */
    public function __construct(ReadableStreamInterface $stream)
    {
        $this->streamReader = $stream;
        $this->currentLine  = -1;
    }

    /**
     * 
     * @return string
     */
    public function current()
    {
        $data = $this->streamReader->readLine();
        $this->currentLine++;
        return $data;
    }

    /**
     * 
     * @return int
     */
    public function key()
    {
        return $this->currentLine;
    }

    /**
     * 
     * @return self
     */
    public function next()
    {
        return $this;
    }

    /**
     * 
     * @return self
     */
    public function rewind()
    {
        $this->currentLine = -1;
        return $this;
    }

    /**
     * 
     * @return bool
     */
    public function valid()
    {
        return !$this->streamReader->eof();
    }
}