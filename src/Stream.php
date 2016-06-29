<?php

namespace Zeus\Stream;

use Zeus\Event\EmitterTrait;
use Zeus\Core\BitMask;
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
            throw new \RuntimeException('Unable to open stream!');
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
        return \is_resource($this->stream) ?
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
     * @throws \ErrorException
     * @throws \RuntimeException
     */
    public function getContents()
    {
        $this->checkStream();
        ErrorHandler::start();
        $contents = \stream_get_contents($this->stream);
        $this->emit('read', $contents);
        ErrorHandler::stop();
        return $contents;
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
     * @param int $length
     * @return string
     */
    public function read($length = 1024)
    {
        $this->checkStream();
        $string = \fread($this->stream, $length);
        $this->emit('read', $string);
        return $string;
    }

    /**
     * 
     * @param string $eol
     * @return string
     */
    public function readLine($eol = null)
    {
        $this->checkStream();
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
        $this->checkStream();
        if (\fseek($this->stream, $offset, $whence) == -1) {
            throw new \RuntimeException('Stream isn\'t seekable!');
        }
        $this->emit('seek', $offset, $whence);
        return $this;
    }

    /**
     * 
     * @param bool $bool
     * @return self
     * @throws \ErrorException
     * @throws \RuntimeException
     */
    public function setBlocking($bool)
    {
        $this->checkStream();
        ErrorHandler::start();
        \stream_set_blocking($this->stream, (bool)$bool);
        $this->emit('block', (bool)$bool);
        ErrorHandler::stop();
        return $this;
    }

    /**
     * 
     * @return int
     */
    public function tell()
    {
        $this->checkStream();
        return \ftell($this->stream);
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
     * @throws \ErrorException
     * @throws \RuntimeException
     */
    public function truncate($size = 0)
    {
        $this->checkStream();
        ErrorHandler::start();
        \ftruncate($this->stream, $size);
        ErrorHandler::stop();
        return $this;
    }

    /**
     * 
     * @param string $string
     * @return int
     */
    public function write($string)
    {
        $this->checkStream();
        $bytes = \fwrite($this->stream, $string);
        $this->emit('write', $string);
        return $bytes;
    }

    /**
     * 
     * @param StreamInterface $stream
     * @param int $maxLen
     * @return int
     */
    public function writeFrom(StreamInterface $stream, $maxLen = -1)
    {
        $bytes = 0;
        if ($stream->isReadable() && $this->isWritable()) {
            if ($maxLen < 0) {
                while (!$stream->eof()) {
                    $data = $stream->read(1024);
                    $bytes += $this->write($data);
                }
            }
            else if ($maxLen > 0) {
                while (!$stream->eof() && $maxLen > 0) {
                    $data    = $stream->read($maxLen >= 1024 ? 1024 : $maxLen);
                    $length  = \strlen($data);
                    $maxLen -= $length;
                    $bytes  += $this->write($data);
                }
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
        $this->checkStream();
        return new StreamIterator($this);
    }

    /**
     * Checks if the stream resource is valid and, if not, throws a exception.
     * 
     * @throws \RuntimeException
     */
    private function checkStream()
    {
        if (!\is_resource($this->stream)) {
            throw new \RuntimeException('Stream is closed or detached!');
        }
    }
}
