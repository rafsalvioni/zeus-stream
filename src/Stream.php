<?php

namespace Zeus\Stream;

use Zeus\Core\ErrorHandler;

/**
 * Implements a generic stream manager.
 *
 * @author Rafael M. Salvioni
 * @event read When stream was read
 * @event write When stream was write
 * @event seek When stream was seek
 * @event block When stream was block/unblock
 */
class Stream implements StreamInterface
{
    use StreamTrait;
    
    /**
     * Open a stream, returning a resource or a instance of this class.
     * 
     * Add automatically the flag "b" in $mode, if it is undefined.
     * 
     * @param string $path
     * @param string $mode
     * @param bool $returnSelf Should be return a self-instance?
     * @return self
     * @throws \RuntimeException
     */
    public static function open($path, $mode, $returnSelf = true)
    {
        if (\substr($mode, -1) != 'b') {
            $mode .= 'b';
        }
        
        try {
            ErrorHandler::start();
            $stream = \fopen($path, $mode);
            ErrorHandler::stop();
        }
        catch (\Exception $ex) {
            throw new \RuntimeException('Unable to open stream!', 0, $ex);
        }
        
        if ($returnSelf) {
            return new static($stream);
        }
        return $stream;
    }
    
    /**
     *
     * @param resource $stream Stream
     * @throws \InvalidArgumentException
     */
    public function __construct($stream)
    {
        if (!\is_resource($stream)) {
            throw new \InvalidArgumentException('Argument should be a resource');
        }
        
        $resType = \get_resource_type($stream);

        if (\preg_match('/stream/i', $resType)) {
            $this->stream = $stream;
            $metadata     = $this->getMetaData();
            \preg_match('/^([rwax])(\+?)/', $metadata['mode'], $match);

            if ($match[2] == '+') {
                $this->readable = $this->writable = true;
            }
            else if ($match[1] == 'r') {
                $this->readable = true;
            }
            else {
                $this->writable = true;
            }
            
            $this->local      = \stream_is_local($this->stream);
            $this->persistent = \stripos($resType, 'persistent') !== false;
            $this->seekable   = $this->getMetaData('seekable', false);
        }
        else {
            throw new \InvalidArgumentException('Invalid stream resource');
        }
    }

    /**
     * 
     * @throws \LogicException
     */
    public function __sleep()
    {
        throw new \LogicException('A stream can\'t be serialized!');
    }

    /**
     * 
     * @return self
     */
    public function close()
    {
        if (\is_resource($this->stream)) {
            \fclose($this->stream);
        }
        $this->detach();
        return $this;
    }

    /**
     * 
     * @return bool
     */
    public function eof()
    {
        return $this->stream ?
               \feof($this->stream) :
               true;
    }

    /**
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetaData($key = null, $default = null)
    {
        if (\is_resource($this->stream)) {
            $meta = \stream_get_meta_data($this->stream);
            if (\is_null($key)) {
                return $meta;
            }
            else {
                return \array_get($meta, $key, $default);
            }
        }
        return $default;
    }

    /**
     * 
     * @param int $length
     * @return string
     * @throws \RuntimeException
     */
    public function read($length = StreamInterface::DEFAULT_READ)
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream isn\'t readable!');
        }
        $string = \fread($this->stream, $length);
        $this->emit('read', $string);
        return $string;
    }

    /**
     * 
     * @param string $eol
     * @return string
     * @throws \RuntimeException
     */
    public function readLine($eol = null)
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream isn\'t readable!');
        }
        
        $eol  = \coalesce($eol, $this->eol);
        $line = \stream_get_line($this->stream, 0, $eol);
        if ($line) {
            $line .= $eol;
        }

        $this->emit('read', $line);
        return $line;
    }

    /**
     * 
     * @return string
     * @throws \RuntimeException
     */
    public function getContents()
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream isn\'t readable!');
        }
        $contents = \stream_get_contents($this->stream);
        $this->emit('read', $contents);
        return $contents;
    }

    /**
     * 
     * @param string $string
     * @return int
     * @throws \RuntimeException
     */
    public function write($string)
    {
        if (!$this->isWritable()) {
            throw new \RuntimeException('Stream isn\'t writable!');
        }
        $bytes = \fwrite($this->stream, $string);
        $this->emit('write', $string);
        return $bytes;
    }

    /**
     * 
     * @param int $offset
     * @param int $whence
     * @return self
     * @throws \RuntimeException
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->isSeekable()) {
            throw new \RuntimeException('Stream isn\'t seekable!');
        }
        if (\fseek($this->stream, $offset, $whence) == -1) {
            throw new \RuntimeException('Seek error!');
        }

        $this->emit('seek', $offset, $whence);
        return $this;
    }

    /**
     * 
     * @param bool $bool
     * @return self
     * @throws \RuntimeException
     */
    public function setBlocking($bool)
    {
        if ($this->stream && \stream_set_blocking($this->stream, (bool)$bool)) {
            $this->emit('block', (bool)$bool);
            return $this;
        }
        throw new \RuntimeException('Cannot (un)block the stream!');
    }

    /**
     * 
     * @return int
     * @throws \RuntimeException
     */
    public function tell()
    {
        if (!$this->isSeekable()) {
            throw new \RuntimeException('Stream isn\'t seekable!');
        }
        $pos = \ftell($this->stream);
        return $pos;
    }

    /**
     * 
     * @param int $size
     * @return self
     * @throws \RuntimeException
     */
    public function truncate($size = 0)
    {
        if (!$this->stream || !\ftruncate($this->stream, $size)) {
            throw new \RuntimeException('Unable to truncate stream!');
        }
        return $this;
    }

    /**
     * 
     * @return LineIterator
     */
    public function getIterator()
    {
        return new LineIterator($this);
    }

    /**
     * 
     */
    public function __destruct()
    {
        if (!$this->isPersistent()) {
            $this->close();
        }
    }
}
