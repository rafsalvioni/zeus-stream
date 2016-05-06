<?php

namespace Zeus\Stream\Seek;

use Zeus\Stream\Read\ReadableStreamInterface;
use Zeus\Stream\Write\WritableStreamInterface;

/**
 * Identifies a seekable stream.
 * 
 * @author Rafael M. Salvioni
 */
interface SeekableStreamInterface extends ReadableStreamInterface, WritableStreamInterface
{
    /**
     * Moves cursor to the begin of stream.
     *
     * @return self
     */
    public function cursorBegin();

    /**
     * Moves cursor to the end of stream.
     *
     * @return self
     */
    public function cursorEnd();
    
    /**
     * Moves cursor to a defined position.
     *
     * If $add is true, $offset will be sum to current position,
     * defining a new position.
     *
     * @return self
     */
    public function cursorTo($offset, $add = false);

    /**
     * Moves the cursor to the next position.
     * 
     * @return self
     */
    public function cursorNext();

    /**
     * Moves cursor to the previous position.
     * 
     * @return self
     */
    public function cursorPrevious();
    
    /**
     * Returns the current position.
     *
     * @return int
     */
    public function cursorPos();
    
    /**
     * Truncate the stream to $size bytes.
     * 
     * @see \ftruncate()
     * @param int $size bytes
     * @return bool
     */
    public function truncate($size = 0);

    /**
     * Return the stream length, in bytes.
     *
     * @return int
     */
    public function getLength();
}

