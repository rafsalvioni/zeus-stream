<?php

namespace Zeus\Stream;

use \Zeus\Event\EmitterInterface;

/**
 * Identifies a stream manager.
 * 
 * It is based on PSR-7 StreamInterface, but it doesn't extends it.
 * 
 * @author Rafael M. Salvioni
 */
interface StreamInterface extends EmitterInterface, \IteratorAggregate
{
    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString();

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close();

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach();

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize();

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell();

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof();

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable();

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = \SEEK_SET);

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    public function rewind();

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable();

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string);

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable();

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length);

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents();
    
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
     */
    public function writeLine($line, $eol = null);

    /**
     * Copy data between streams.
     *
     * Returns the quantity of bytes written.
     *
     * @param StreamInterface $stream Stream
     * @param int $maxLen Max bytes to be written
     * @return int
     */
    public function writeFrom(StreamInterface $stream, $maxLen = -1);
    
    /**
     * Truncate the stream to $size bytes.
     * 
     * @see \ftruncate()
     * @param int $size bytes
     * @return bool
     */
    public function truncate($size = 0);
    
    /**
     * Returns whether or not the stream is in blocking mode.
     *
     * @return bool
     */
    public function isBlocked();
    
    /**
     * Returns whether or not the stream is persistent.
     *
     * @return bool
     */
    public function isPersistent();
    
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
