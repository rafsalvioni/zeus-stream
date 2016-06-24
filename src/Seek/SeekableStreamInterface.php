<?php

namespace Zeus\Stream\Seek;

use Zeus\Stream\Read\ReadableStreamInterface;
use Zeus\Stream\Write\WritableStreamInterface;

/**
 * Identifies a seekable stream.
 * 
 * @author Rafael M. Salvioni
 */
interface SeekableStreamInterface extends
    ReadableStreamInterface,
    WritableStreamInterface
{
    /**
     * Moves cursor to the begin of stream.
     *
     * @return self
     */
    public function seekBegin();

    /**
     * Moves cursor to the end of stream.
     *
     * @return self
     */
    public function seekEnd();
    
    /**
     * Moves cursor to a defined position.
     *
     * If $add is true, $offset will be sum to current position,
     * defining a new position.
     *
     * @return self
     */
    public function seek($offset, $add = false);

    /**
     * Returns the current position.
     *
     * @return int
     */
    public function tell();
    
    /**
     * Truncate the stream to $size bytes.
     * 
     * @see \ftruncate()
     * @param int $size bytes
     * @return bool
     */
    public function truncate($size = 0);

    /**
     * Return the stream size, in bytes.
     *
     * @return int
     */
    public function getSize();
}

