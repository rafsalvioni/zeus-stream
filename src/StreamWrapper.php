<?php

namespace Zeus\Stream;

use Zeus\Event\EmitterTrait;
use Zeus\Core\BitMask;

/**
 * Implements a generic stream manager.
 *
 * @author Rafael M. Salvioni
 */
class StreamWrapper implements StreamInterface
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
     * Stream flags
     *
     * @var BitMask
     */
    private   $flags;
    /**
     * Stream resource
     *
     * @var resource
     */
    protected $resource;
    /**
     * End of line default
     * 
     * @var string
     */
    protected $eol = \PHP_EOL;


    /**
     * Open a seekable stream, returning a resource or a instance of this class.
     * 
     * Add automatically the flag "b" in $mode, if it is undefined.
     * 
     * @param string $path
     * @param string $mode
     * @param bool $returnSelf Should be return a self-instance?
     * @return self
     * @throws Exception
     */
    public static function open($path, $mode, $returnSelf = true)
    {
        if (\substr($mode, -1) != 'b') {
            $mode .= 'b';
        }
        
        try {
            $stream = \fopen($path, $mode);
        }
        catch (\ErrorException $ex) {
            throw new Exception($ex->getMessage());
        }
        
        if ($returnSelf) {
            return static::factory($stream);
        }
        return $stream;
    }
    
    /**
     * Factory to create stream manager using stream meta data.
     * 
     * @param resource $stream Stream resource
     * @return self
     */
    public static function factory($stream)
    {
        $self     = new static($stream);
        $instance = null;
        if ($self->isSeekable()) {
            $instance = new Seek\SeekableStream($stream);
        }
        else if ($self->isReadable() && $self->isWritable()) {
            $instance = new ReadWriteStream($stream);
        }
        else if ($self->isReadable()) {
            $instance = new Read\ReadableStream($stream);
        }
        else {
            $instance = new Write\WritableStream($stream);
        }
        $self->resource = null;
        return $instance;
    }
    
    /**
     *
     * @param resource $stream Stream
     * @throws \DomainException
     */
    public function __construct($stream)
    {
        if (!\is_resource($stream)) {
            throw new \InvalidArgumentException('Argument should be a resource');
        }
        
        $resType = \get_resource_type($stream);

        if (\preg_match('/stream/i', $resType)) {
            $this->resource = $stream;
            $metadata       = $this->getMetaData();
            \preg_match('/^([rwax])(\+?)/', $metadata['mode'], $match);

            $this->flags = new BitMask();
            
            if ($match[2] == '+') {
                $this->flags->add(self::READABLE)->add(self::WRITABLE);
            }
            else if ($match[1] == 'r') {
                $this->flags->add(self::READABLE);
            }
            else {
                $this->flags->add(self::WRITABLE);
            }

            if (\preg_match('/persistent/i', $resType)) {
                $this->flags->add(self::PERSISTENT);
            }
            if ($this->getMetaData('seekable', false)) {
                $this->flags->add(self::SEEKABLE);
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
        if (\stream_set_blocking($this->resource, $bool ? 1 : 0)) {
            return $this;
        }
        throw new Exception('Unable to set blocking mode');
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
     * @param string $eol
     * @return string
     */
    public function eol($eol = null)
    {
        if (!\is_null($eol)) {
            $this->eol = (string)$eol;
        }
        return $this->eol;
    }

    /**
     *
     * @return bool
     */
    public function isWritable()
    {
        return $this->flags->has(self::WRITABLE);
    }

    /**
     *
     * @return bool
     */
    public function isReadable()
    {
        return $this->flags->has(self::READABLE);
    }

    /**
     *
     * @return bool
     */
    public function isSeekable()
    {
        return $this->flags->has(self::SEEKABLE);
    }
    
    /**
     *
     * @return bool
     */
    public function isPersistent()
    {
        return $this->flags->has(self::PERSISTENT);
    }
    
    /**
     * 
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     *
     * @param string $key
     * @return mixed
     */
    public function getMetaData($key = null, $default = null)
    {
        $meta = \stream_get_meta_data($this->resource);
        if ($key) {
            return \array_get($meta, $key, $default);
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
        return \strval($this->resource);
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
        if (\is_resource($this->resource)) {
            \fclose($this->resource);
        }
    }
}
