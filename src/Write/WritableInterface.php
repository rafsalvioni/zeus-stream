<?php

namespace Zeus\Stream\Write;

use Zeus\Stream\StreamInterface;
use Zeus\Stream\Read\ReadableInterface;

/**
 * Identifies a writable stream.
 * 
 * @author Rafael M. Salvioni
 */
interface WritableInterface extends StreamInterface
{
    /**
     * Write data on stream.
     *
     * Returns the quantity of bytes written.
     *
     * @param string $data Data
     * @return int
     * @throws Exception
     */
    public function write($data);
    
    /**
     * Write a line data.
     *
     * If the line data doesn't have a "end of line" string, it is appended. If
     * $eof isn't given, the default object's eol will be used.
     *
     * Returns the quantity of bytes written.
     *
     * @param string $line Line
     * @param string $eol End of line string
     * @return int
     */
    public function writeLine($line, $eol = null);

    /**
     * Copy data between streams.
     *
     * Returns the quantity of bytes written.
     *
     * @param ReadableInterface $stream Stream
     * @param int $maxLen Max bytes to be written
     * @return int
     */
    public function writeFrom(ReadableInterface $stream, $maxLen = -1);
}
