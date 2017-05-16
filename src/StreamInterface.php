<?php

namespace Zeus\Stream;

use \Zeus\Event\EmitterInterface;
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
     * Default length of bytes to read (8KB)
     * 
     * @var int
     */
    const DEFAULT_READ = 8196;
    
    /**
     * Setting if a stream should be in blocking mode or not.
     * 
     * @param bool $bool On/off
     * @return StreamInterface
     * @throws \RuntimeException
     */
    public function setBlocking($bool);

    /**
     * Toggle into blocking mode. If turned on, will turn off and vice-versa.
     *
     * @return StreamInterface
     * @throws \RuntimeException
     */
    public function toggleBlocking();
    
    /**
     * Sets / returns a default string used by end of line.
     * 
     * @param string $eol End of line string
     * @return string
     */
    public function eol($eol = null);

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
    public function readLine($eol = null);
    
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
     * @throws \RuntimeException
     */
    public function writeLine($line, $eol = null);

    /**
     * Copy data between streams.
     *
     * Returns the quantity of bytes written.
     *
     * @param PsrStreamInterface $stream Stream
     * @param int $maxLen Max bytes to be written
     * @return int
     * @throws \RuntimeException
     */
    public function writeFrom(PsrStreamInterface $stream, $maxLen = -1);
    
    /**
     * Truncate the stream to $size bytes.
     * 
     * @see \ftruncate()
     * @param int $size bytes
     * @return bool
     * @throws \RuntimeException
     */
    public function truncate($size = 0);
    
    /**
     * Checks if stream is in blocking mode.
     *
     * @return bool
     */
    public function isBlocked();
    
    /**
     * Checks if the stream is persistent.
     *
     * @return bool
     */
    public function isPersistent();
    
    /**
     * Checks if the stream is a local stream or not.
     *
     * @return bool
     */
    public function isLocal();
    
    /**
     * Checks if the stream is detached.
     *
     * @return bool
     */
    public function isDetached();
    
    /**
     * Get stream metadata
     * 
     * @param string $key Value key
     * @param mixed $default Default value
     * @return mixed
     */
    public function getMetaData($key = null, $default = null);
}
