<?php

namespace Zeus\Stream;

use Zeus\Event\EmitterTrait;

/**
 * Implements a generic stream manager.
 *
 * @author Rafael M. Salvioni
 */
class Stream implements StreamInterface
{
    use EmitterTrait;
    
    /**
     * Readable flag
     *
     * @var int
     */
    const READABLE   = 1;
    /**
     * Writable flag
     *
     * @var int
     */
    const WRITABLE   = 2;
    /**
     * Seekable flag
     *
     * @var int
     */
    const SEEKABLE   = 4;
    /**
     * Persistent flag
     *
     * @var int
     */
    const PERSISTENT = 8;

    /**
     * Stream flags. Is a bitmask
     *
     * @var int
     */
    private   $flags;
    /**
     * Stream resource
     *
     * @var resource
     */
    protected $stream;
    
    /**
     *
     * @param resource $stream Stream
     * @throws \DomainException
     */
    public function __construct(\resource $stream)
    {
        $resType = \get_resource_type($stream);

        if (\preg_match('/stream/i', $resType)) {
            $this->stream   = $stream;
            $metadata       = $this->getMetaData();
            \preg_match('/^([rwax])(\+?)/', $metadata['mode'], $match);

            if ($match[2] == '+') {
                $this->flags = self::READABLE | self::WRITABLE;
            }
            else if ($match[1] == 'r') {
                $this->flags = self::READABLE;
            }
            else {
                $this->flags = self::WRITABLE;
            }

            if (\preg_match('/persistent/i', $resType)) {
                $this->flags |= self::PERSISTENT;
            }
            if ($this->getMetaData('seekable', false)) {
                $this->flags |= self::SEEKABLE;
            }
        }
        else {
            throw new \DomainException("Invalid stream resource");
        }
    }

    /**
     * 
     * @return bool
     */
    public function isBlocked()
    {
        $result = $this->getMetaData('blocked');
        return $result;
    }
    
    /**
     * 
     * @param bool $bool
     * @return self 
     * @throws \DomainException
     */
    public function setBlocking($bool)
    {
        if (\stream_set_blocking($this->stream, $bool ? 1 : 0)) {
            return $this;
        }
        throw new \DomainException('Unable to set blocking mode');
    }

    /**
     *
     * @return self
     */
    public function toggleBlocking()
    {
        $bool = $this->isBlocked();
        $bool = !$bool;
        $this->setBlocking($bool);
        return $this;
    }

    /**
     *
     * @return bool
     */
    public function isWritable()
    {
        return ($this->flags & self::WRITABLE) > 0;
    }

    /**
     *
     * @return bool
     */
    public function isReadable()
    {
        return ($this->flags & self::READABLE) > 0;
    }

    /**
     *
     * @return bool
     */
    public function isSeekable()
    {
        return ($this->flags & self::SEEKABLE) > 0;
    }
    
    /**
     *
     * @return bool
     */
    public function isPersistent()
    {
        return ($this->flags & self::PERSISTENT) > 0;
    }
    
    /**
     * 
     * @return resource
     */
    public function getResource()
    {
        return $this->stream;
    }

    /**
     *
     * @param string $key
     * @return mixed
     */
    public function getMetaData($key = null, $default = null)
    {
        $meta = \stream_get_meta_data($this->stream);
        if ($key) {
            return isset($meta[$key]) ? $meta[$key] : $default;
        }
        else {
            return $meta;
        }
    }
    
    /**
     *
     * @return string
     */
    final public function __toString()
    {
        return \strval($this->stream);
    }

    /**
     *
     * @throws \LogicException
     */
    public function __sleep()
    {
        throw new \LogicException("Cannot serialize a Stream object");
    }

    /**
     *
     *
     */
    public function __destruct()
    {
        if (\is_resource($this->stream)) {
            \fclose($this->stream);
        }
    }
}
