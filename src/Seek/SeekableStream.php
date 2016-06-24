<?php

namespace Zeus\Stream\Seek;

use Zeus\Stream\StreamWrapper;
use Zeus\Stream\Read\ReadableStreamTrait;
use Zeus\Stream\Write\WritableStreamTrait;

/**
 * Implements a seekable stream.
 * 
 * @author Rafael M. Salvioni
 */
class SeekableStream extends StreamWrapper implements SeekableStreamInterface
{
    use ReadableStreamTrait, WritableStreamTrait;
    
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
            throw new \DomainException('The stream isn\'t seekable');
        }
    }
    
    /**
     *
     * @return self
     */
    public function seekBegin()
    {
        return $this->setCursor(0);
    }

    /**
     *
     * @return self
     */
    public function seekEnd()
    {
        return $this->setCursor(0, \SEEK_END);
    }
    
    /**
     *
     * @return self
     */
    public function seek($offset, $add = false)
    {
        $whence = $add ? \SEEK_CUR : \SEEK_SET;
        return $this->setCursor($offset, $whence);
    }

    /**
     *
     * @return int
     */
    public function tell()
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
    public function getSize()
    {
        $offset = $this->tell();
        $this->seekEnd();
        $length = $this->tell();
        $this->seek($offset);
        return $length;
    }
    
    /**
     * 
     * @return SeekableStreamIterator
     */
    public function getIterator()
    {
        return new SeekableStreamIterator($this);
    }
}
