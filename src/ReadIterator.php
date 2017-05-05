<?php

namespace Zeus\Stream;

/**
 * Iterator for readable streams.
 *
 * @author Rafael M. Salvioni
 */
class ReadIterator implements \Iterator
{
    /**
     * Stream
     * 
     * @var StreamInterface 
     */
    protected $stream;
    /**
     * Current line key
     * 
     * @var int
     */
    protected $currentLine;
    
    /**
     * 
     * @param StreamInterface $stream
     */
    public function __construct(StreamInterface $stream)
    {
        $this->stream      = $stream;
        $this->currentLine = -1;
    }

    /**
     * 
     * @return string
     */
    public function current()
    {
        $data = $this->stream->readLine();
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
        if ($this->stream->isSeekable()) {
            $this->stream->rewind();
        }
        $this->currentLine = -1;
        return $this;
    }

    /**
     * 
     * @return bool
     */
    public function valid()
    {
        return !$this->stream->eof();
    }
}
