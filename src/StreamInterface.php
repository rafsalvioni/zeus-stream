<?php

namespace Zeus\Stream;

use Zeus\Event\EmitterInterface;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;

/**
 * Identifies a stream manager.
 * 
 * @author Rafael M. Salvioni
 */
interface StreamInterface extends
    EmitterInterface,
    PsrStreamInterface,
    \IteratorAggregate
{
    /**
     * Setting if a stream should be in blocking mode or not.
     * 
     * @param bool $bool On/off
     * @return StreamInterface
     * @throws \RuntimeException
     */
    public function setBlocking(bool $bool): StreamInterface;

    /**
     * Toggle into blocking mode. If turned on, will turn off and vice-versa.
     *
     * @return StreamInterface
     */
    public function toggleBlocking(): StreamInterface;
    
    /**
     * Sets / returns a default string used by end of line.
     * 
     * @param string $eol End of line string
     * @return string
     */
    public function eol(string $eol = null): string;

    /**
     * Returns a line of data.
     * 
     * $eol will be appended in returned value. If $eol isn't given,
     * the default eol of object will be used.
     *
     * @param string $eol "end of line" string
     * @return string
     * @throws \RuntimeException
     */
    public function readLine(string $eol = null): string;
    
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
    public function writeLine(string $line, string $eol = null): int;

    /**
     * Copy data from another stream.
     *
     * Returns the quantity of bytes written.
     *
     * @param PsrStreamInterface $from Stream
     * @param int $maxLen Max bytes to be written
     * @return int
     */
    public function writeFrom(PsrStreamInterface $from, int $maxLen = -1): int;
    
    /**
     * Truncate the stream to $size bytes.
     * 
     * @see \ftruncate()
     * @param int $size bytes
     * @return bool
     */
    public function truncate(int $size = 0): bool;
    
    /**
     * Shows if stream is in blocking mode.
     *
     * @return bool
     */
    public function isBlocked(): bool;
    
    /**
     * Shows if the stream is persistent.
     *
     * @return bool
     */
    public function isPersistent(): bool;
    
    /**
     * Get stream metadata
     * 
     * @param string $key Value key
     * @param mixed $default Default value
     * @return mixed
     */
    public function getMetaData($key = null, $default = null);

    /**
     * Disable a stream serialization.
     *
     * @throws \LogicException
     */
    public function __sleep();
}
