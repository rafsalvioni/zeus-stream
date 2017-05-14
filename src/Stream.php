<?php

namespace Zeus\Stream;

use Zeus\Event\EmitterTrait;
use Zeus\Core\BitMask;
use Zeus\Core\ErrorHandler;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;

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
     * Local stream flag
     * 
     * @var int
     */
    const ISLOCAL    = 16;
    /**
     * Default bytes length
     * 
     * @var int
     */
    const DEFAULT_BYTES = 8192;

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
    protected $stream;
    /**
     * End of line default
     * 
     * @var string
     */
    protected $eol = \PHP_EOL;

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

            $this->flags = new BitMask(
                \stream_is_local($this->stream) ? self::ISLOCAL : 0
            );
            
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
     * If stream isn'\t readable, return a empty string. Else, if it is
     * seekable, returns all contents of stream, independetely of its position.
     * Else, returns only the remainder data, if have.
     * 
     * @return string
     */
    public function __toString()
    {
        try {
            if (!$this->isReadable()) {
                $contents = '';
            }
            else if ($this->isSeekable()) {
                $pos = $this->tell();
                $this->rewind();
                $contents = $this->getContents();
                $this->seek($pos);
            }
            else {
                $block    = $this->isBlocked();
                $this->setBlocking(false);
                $contents = $this->getContents();
                $this->setBlocking($block);
            }
        }
        catch (\Exception $ex) {
            $contents = '';
        }
        return $contents;
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
     * @return resource|null
     */
    public function detach()
    {
        $stream = $this->stream;
        $this->stream = null;
        $this->flags  = new BitMask();
        return $stream;
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
     * @return string
     * @throws \RuntimeException
     */
    public function getContents()
    {
        try {
            ErrorHandler::start();
            $contents = \stream_get_contents($this->stream);
            ErrorHandler::stop();
            $this->emit('read', $contents);
            return $contents;
        }
        catch (\ErrorException $ex) {
            throw new \RuntimeException('Unable to read stream!', 0, $ex);
        }
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
     * @return int
     */
    public function getSize()
    {
        if (!$this->isSeekable()) {
            return -1;
        }
        
        $pos  = $this->tell();
        $this->seek(0, \SEEK_END);
        $size = $this->tell();
        $this->seek($pos);
        return $size;
    }

    /**
     * 
     * @return bool
     */
    public function isBlocked()
    {
        return $this->getMetaData('blocked', false);
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
    public function isWritable()
    {
        return $this->flags->has(self::WRITABLE);
    }
    
    /**
     * 
     * @return bool
     */
    public function isLocal()
    {
        return $this->flags->has(self::ISLOCAL);
    }
    
    /**
     * 
     * @return bool
     */
    public function isDetached()
    {
        return !$this->stream;
    }

    /**
     * 
     * @param int $length
     * @return string
     */
    public function read($length = self::DEFAULT_BYTES)
    {
        try {
            ErrorHandler::start();
            $string = \fread($this->stream, $length);
            ErrorHandler::stop();
            $this->emit('read', $string);
            return $string;
        }
        catch (\ErrorException $ex) {
            throw new \RuntimeException('Unable to read stream!', 0, $ex);
        }
    }

    /**
     * 
     * @param string $eol
     * @return string
     */
    public function readLine($eol = null)
    {
        try {
            $eol  = \coalesce($eol, $this->eol);
            
            ErrorHandler::start();
            $line = \stream_get_line($this->stream, 0, $eol);
            ErrorHandler::stop();
            
            if ($line) {
                $line .= $eol;
            }
            
            $this->emit('read', $line);
            return $line;
        }
        catch (\ErrorException $ex) {
            throw new \RuntimeException('Unable to read stream!', 0, $ex);
        }
    }

    /**
     * 
     * @return self
     */
    public function rewind()
    {
        return $this->seek(0);
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
        try {
            ErrorHandler::start();
            if (\fseek($this->stream, $offset, $whence) == -1) {
                throw new \RuntimeException('Stream isn\'t seekable!');
            }
            ErrorHandler::stop();
            
            $this->emit('seek', $offset, $whence);
            return $this;
        }
        catch (\ErrorException $ex) {
            throw new \RuntimeException('Unable to seek stream!', 0, $ex);
        }
    }

    /**
     * 
     * @param bool $bool
     * @return self
     * @throws \RuntimeException
     */
    public function setBlocking($bool)
    {
        try {
            ErrorHandler::start();
            \stream_set_blocking($this->stream, (bool)$bool);
            ErrorHandler::stop();
            $this->emit('block', (bool)$bool);
            return $this;
        }
        catch (\ErrorException $ex) {
            throw new \RuntimeException('Unable to (un)block stream!', 0, $ex);
        }
    }

    /**
     * 
     * @return int
     */
    public function tell()
    {
        try {
            $stream =& $this;
            return ErrorHandler::tryThis(function() use (&$stream) {
                return \ftell($stream->stream);
            });
        }
        catch (\ErrorException $ex) {
            throw new \RuntimeException('Unable to get stream position!', 0, $ex);
        }
    }

    /**
     * 
     * @return self
     */
    public function toggleBlocking()
    {
        $block = !$this->isBlocked();
        return $this->setBlocking($block);
    }

    /**
     * 
     * @param int $size
     * @return self
     * @throws \RuntimeException
     */
    public function truncate($size = 0)
    {
        try {
            $me =& $this;
            ErrorHandler::tryThis(function () use (&$me, $size) {
                \ftruncate($me->stream, $size);
            });
            return $this;
        }
        catch (\ErrorException $ex) {
            throw new \RuntimeException('Unable to truncate stream!', 0, $ex);
        }
    }

    /**
     * 
     * @param string $string
     * @return int
     * @throws \RuntimeException
     */
    public function write($string)
    {
        try {
            ErrorHandler::start();
            $bytes = \fwrite($this->stream, $string);
            ErrorHandler::stop();
            $this->emit('write', $string);
            return $bytes;
        }
        catch (\ErrorException $ex) {
            throw new \RuntimeException('Unable to write stream!', 0, $ex);
        }
    }

    /**
     * 
     * @param PsrStreamInterface $stream
     * @param int $maxLen
     * @return int
     */
    public function writeFrom(PsrStreamInterface $stream, $maxLen = -1)
    {
        if (!$this->isWritable() || !$stream->isReadable()) {
            return 0;
        }
        
        $bytes = 0;
        try {
            ErrorHandler::start();
            while ($maxLen < 0 && !$stream->eof()) {
                $data = \fread($stream->stream, self::DEFAULT_BYTES);
                $bytes += \fwrite($this->stream, $data);
            }
            while ($maxLen > 0 && !$stream->eof()) {
                $toRead  = $maxLen >= self::DEFAULT_BYTES ?
                            self::DEFAULT_BYTES :
                            $maxLen;
                $data    = \fread($stream->stream, $toRead);
                $length  = \strlen($data);
                $maxLen -= $length;
                $bytes  += \fwrite($this->stream, $data);
            }
            ErrorHandler::stop();
        } 
        catch (\ErrorException $ex) {
            throw new \RuntimeException('Unable to copy streams!', 0, $ex);
        }
        return $bytes;
    }

    /**
     * 
     * @param string $line
     * @param string $eol
     * @return int
     */
    public function writeLine($line, $eol = null)
    {
        $eol = \coalesce($eol, $this->eol);
        $n   = \strlen($eol);
        if (\substr($line, -$n) != $eol) {
            $line .= $eol;
        }
        return $this->write($line);
    }

    /**
     * 
     * @return StreamIterator
     */
    public function getIterator()
    {
        return new StreamIterator($this);
    }
}
