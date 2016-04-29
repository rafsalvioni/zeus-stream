<?php

namespace Zeus\Stream;

use \Zeus\Event\EmitterInterface;

/**
 * Identifies a stream manager.
 * 
 * @author Rafael M. Salvioni
 */
interface StreamInterface extends EmitterInterface
{
    /**
     * Setting if a stream should be in blicking mode or not.
     * 
     * @param bool $bool On/off
     * @return StreamInterface
     * @throws Exception
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
     * Shows if stream is in blocking mode.
     *
     * @return bool
     */
    public function isBlocked();
    
    /**
     * Shows if the stream can be written.
     *
     * @return bool
     */
    public function isWritable();

    /**
     * Shows if the stream can be readen.
     *
     * @return bool
     */
    public function isReadable();

    /**
     * Shows if the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable();
    
    /**
     * Shows if the stream is persistent.
     *
     * @return bool
     */
    public function isPersistent();
    
    /**
     * Returns the PHP stream resource.
     * 
     * @return resource
     */
    public function getResource();
    
    /**
     * Get stream metadata
     * 
     * @param string $key Value key
     * @param mixed $default Default value
     * @return mixed
     */
    public function getMetaData($key = null, $default = null);

    /**
     *
     * @return string
     */
    public function __toString();

    /**
     * Disable a stream serialization.
     *
     * @throws \LogicException
     */
    public function __sleep();

    /**
     *
     */
    public function __destruct();
}
