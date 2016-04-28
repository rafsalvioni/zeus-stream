<?php

namespace Zeus\Stream\Seek;

use Zeus\Stream\Stream;
use Zeus\Stream\Read\ReadTrait;
use Zeus\Stream\Write\WriteTrait;

/**
 * Implements a seekable stream.
 * 
 * @author Rafael M. Salvioni
 */
class Seekable extends Stream implements SeekableInterface
{
    use ReadTrait, WriteTrait;
    
    /**
     * Defines the stream's cursor position.
     *
     * @param int $offset Position
     * @param int $whence Mode (\SEEK_* constants)
     * @return self
     */
    protected function setCursor($offset, $whence = \SEEK_SET)
    {
        \fseek($this->resource, $offset, $whence);
        return $this;
    }

    /**
     * 
     * @param resource $stream
     * @throws \DomainException Se o stream não for considerado local
     */
    public function __construct($stream)
    {
        parent::__construct($stream);
        if (!$this->isSeekable()) {
            throw new \DomainException('The stream isn\'t a local one');
        }
    }
    
    /**
     *
     * @return self
     */
    public function cursorBegin()
    {
        return $this->setCursor(0);
    }

    /**
     *
     * @return self
     */
    public function cursorEnd()
    {
        return $this->setCursor(0, \SEEK_END);
    }
    
    /**
     * 
     * @return self
     */
    public function cursorNext()
    {
        return $this->cursorTo(1, true);
    }

    /**
     * 
     * @return self
     */
    public function cursorPrevious()
    {
        return $this->cursorTo(-1, true);
    }

    /**
     *
     * @return self
     */
    public function cursorTo($offset, $add = false)
    {
        $whence = $add ? \SEEK_CUR : \SEEK_SET;
        return $this->setCursor($offset, $whence);
    }

    /**
     *
     * @return int
     */
    public function cursorPos()
    {
        return \ftell($this->resource);
    }
    
    /**
     *
     * @see \ftruncate()
     * @param int $size Size in bytes
     * @return bool
     */
    public function truncate($size = 0)
    {
        return \ftruncate($this->resource, $size);
    }

    /**
     *
     * @return int
     */
    public function getLength()
    {
        $offset = $this->cursorPos();
        $this->cursorEnd();
        $length = $this->cursorPos();
        $this->cursorTo($offset);
        return $length;
    }
    
    /**
     * 
     * @return Iterator
     */
    public function getIterator()
    {
        return new Iterator($this);
    }
}
