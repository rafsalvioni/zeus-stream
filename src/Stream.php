<?php

namespace Zeus\Stream;

use Zeus\Event\EmitterTrait;
use Zeus\Core\BitMask;
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
     * @return mixed
     * @throws \RuntimeException
     */
    public static function open(
        string $path, string $mode, bool $returnSelf = true
    ){
        if (\substr($mode, -1) != 'b') {
            $mode .= 'b';
        }
        
        try {
            $stream = \fopen($path, $mode);
        }
        catch (\Throwable $ex) {
            throw new \RuntimeException('Unable to open stream!', $ex->getCode(), $ex);
        }
        
        if ($returnSelf) {
            return new static($stream);
        }
        return $stream;
    }
    
    /**
     * Converts a Psr\Http\Message\StreamInterface object to a
     * Zeus\Stream\StreamInterface object.
     * 
     * @param PsrStreamInterface $psrStream
     * @return StreamInterface
     */
    public static function fromPsr(PsrStreamInterface $psrStream): StreamInterface
    {
        if (!($psrStream instanceof StreamInterface)) {
            $stream = $psrStream->detach();
            return new self($stream);
        }
        return $psrStream;
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

            $this->flags = new BitMask();
            
            if ($match[2] == '+') {
                $this->flags->add(self::READABLE, self::WRITABLE);
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
        catch (\Throwable $ex) {
            $contents = '';
        }
        return $contents;
    }

    /**
     * 
     */
    public function close()
    {
        if (\is_resource($this->stream)) {
            \fclose($this->stream);
        }
        $this->detach();
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
        return \is_resource($this->stream) ?
               \feof($this->stream) :
               true;
    }

    /**
     * 
     * @param string $eol
     * @return string
     */
    public function eol(string $eol = null): string
    {
        if (!\is_null($eol)) {
            $this->eol = (string)$eol;
        }
        return $this->eol;
    }

    /**
     * 
     * @return string
     */
    public function getContents()
    {
        try {
            $contents = \stream_get_contents($this->stream);
            $this->emit('read', $contents);
            return $contents;
        }
        catch (\Throwable $ex) {
            throw new \RuntimeException('Unable to read the stream!', $ex->getCode(), $ex);
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
                return $meta[$key] ?? $default;
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
    public function isBlocked(): bool
    {
        return $this->getMetaData('blocked', false);
    }

    /**
     * 
     * @return bool
     */
    public function isPersistent(): bool
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
     * @param int $length
     * @return string
     */
    public function read($length = 1024)
    {
        try {
            $string = \fread($this->stream, $length);
            $this->emit('read', $string);
            return $string;
        }
        catch (\Throwable $ex) {
            throw new \RuntimeException('Unable to read the stream!', $ex->getCode(), $ex);
        }
    }

    /**
     * 
     * @param string $eol
     * @return string
     */
    public function readLine(string $eol = null): string
    {
        try {
            $eol  = $eol ?? $this->eol;
            $line = \stream_get_line($this->stream, 0, $eol);
            if ($line) {
                $line .= $eol;
            }
            $this->emit('read', $line);
            return $line;
        }
        catch (\Throwable $ex) {
            throw new \RuntimeException('Unable to read the stream!', $ex->getCode(), $ex);
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
    public function seek($offset, $whence = \SEEK_SET)
    {
        try {
            if (\fseek($this->stream, $offset, $whence) < 0) {
                throw new \RuntimeException('Stream isn\'t seekable!');
            }
            $this->emit('seek', $offset, $whence);
            return $this;
        }
        catch (\Throwable $ex) {
            throw new \RuntimeException('Unable to read the stream!', $ex->getCode(), $ex);
        }
    }

    /**
     * 
     * @param bool $bool
     * @return StreamInterface
     * @throws \RuntimeException
     */
    public function setBlocking(bool $bool): StreamInterface
    {
        try {
            \stream_set_blocking($this->stream, $bool);
            $this->emit('block', $bool);
            return $this;
        }
        catch (\Throwable $ex) {
            throw new \RuntimeException('Unable to read the stream!', $ex->getCode(), $ex);
        }
    }

    /**
     * 
     * @return int
     */
    public function tell()
    {
        return \ftell($this->stream);
    }

    /**
     * 
     * @return StreamInterface
     */
    public function toggleBlocking(): StreamInterface
    {
        $block = !$this->isBlocked();
        return $this->setBlocking($block);
    }

    /**
     * 
     * @param int $size
     * @return bool
     * @throws \RuntimeException
     */
    public function truncate(int $size = 0): bool
    {
        try {
            return \ftruncate($this->stream, $size);
        }
        catch (\Throwable $ex) {
            throw new \RuntimeException('Unable to read the stream!', $ex->getCode(), $ex);
        }
    }

    /**
     * 
     * @param string $string
     * @return int
     */
    public function write($string)
    {
        try {
            $bytes = \fwrite($this->stream, $string);
            $this->emit('write', $string);
            return $bytes;
        }
        catch (\Throwable $ex) {
            throw new \RuntimeException('Unable to write in stream!', $ex->getCode(), $ex);
        }
    }

    /**
     * 
     * @param PsrStreamInterface $from
     * @param int $maxLen
     * @return int
     */
    public function writeFrom(PsrStreamInterface $from, int $maxLen = -1): int
    {
        $bytes = 0;
        if ($this->isWritable() && $from->isReadable()) {
            while ($maxLen < 0 && !$from->eof()) {
                $data   = $from->read(1024);
                $bytes += $this->write($data);
            }
            while ($maxLen > 0 && !$from->eof()) {
                $data    = $from->read($maxLen >= 1024 ? 1024 : $maxLen);
                $length  = \strlen($data);
                $maxLen -= $length;
                $bytes  += $this->write($data);
            }
        }
        return $bytes;
    }
    
    /**
     * 
     * @param string $line
     * @param string $eol
     * @return int
     */
    public function writeLine(string $line, string $eol = null): int
    {
        $eol = $eol ?? $this->eol;
        $n   = \strlen($eol);
        if (\substr($line, -$n) != $eol) {
            $line .= $eol;
        }
        return $this->write($line);
    }

    /**
     * 
     * @return ReadIterator
     */
    public function getIterator(): ReadIterator
    {
        return new ReadIterator($this);
    }
    
    /**
     * Closes the stream
     * 
     */
    public function __destruct()
    {
        if (!$this->isPersistent()) {
            $this->close();
        }
    }
}
