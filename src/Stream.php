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
     * @throws \RuntimeException
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
            throw new \RuntimeException("Invalid stream resource");
        }
    }

    public function __sleep()
    {
        throw new \LogicException('A stream can\'t be serialized!');
    }

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

    public function close()
    {
        if (\is_resource($this->stream)) {
            \fclose($this->stream);
        }
        $this->detach();
        return $this;
    }

    public function detach()
    {
        $stream = $this->stream;
        $this->stream = null;
        $this->flags  = new BitMask();
        return $stream;
    }

    public function eof()
    {
        return \is_resource($this->stream) ?
               \feof($this->stream) :
               true;
    }

    public function eol($eol = null)
    {
        if (!\is_null($eol)) {
            $this->eol = (string)$eol;
        }
        return $this->eol;
    }

    public function getContents()
    {
        $this->checkStream();
        ErrorHandler::start();
        $contents = \stream_get_contents($this->stream);
        $this->emit('read', $contents);
        ErrorHandler::stop();
        return $contents;
    }

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

    public function getSize()
    {
        $pos  = $this->tell();
        $this->seek(0, \SEEK_END);
        $size = $this->tell();
        $this->seek($pos);
        return $size;
    }

    public function isBlocked()
    {
        return $this->getMetaData('blocked', false);
    }

    public function isPersistent()
    {
        return $this->flags->has(self::PERSISTENT);
    }

    public function isReadable()
    {
        return $this->flags->has(self::READABLE);
    }

    public function isSeekable()
    {
        return $this->flags->has(self::SEEKABLE);
    }

    public function isWritable()
    {
        return $this->flags->has(self::WRITABLE);
    }

    public function read($length = 1024)
    {
        $this->checkStream();
        $string = \fread($this->stream, $length);
        $this->emit('read', $string);
        return $string;
    }

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

    public function rewind()
    {
        return $this->seek(0);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        $this->checkStream();
        if (\fseek($this->stream, $offset, $whence) == -1) {
            throw new \RuntimeException('Stream isn\'t seekable!');
        }
        return $this;
    }

    public function setBlocking($bool)
    {
        $this->checkStream();
        ErrorHandler::start();
        \stream_set_blocking($this->stream, (bool)$bool);
        ErrorHandler::stop();
        return $this;
    }

    public function tell()
    {
        $this->checkStream();
        return \ftell($this->stream);
    }

    public function toggleBlocking()
    {
        $block = !$this->isBlocked();
        return $this->setBlocking($block);
    }

    public function truncate($size = 0)
    {
        $this->checkStream();
        ErrorHandler::start();
        \ftruncate($this->stream, $size);
        ErrorHandler::stop();
        return $this;
    }

    public function write($string)
    {
        $this->checkStream();
        $bytes = \fwrite($this->stream, $string);
        $this->emit('write', $string);
        return $bytes;
    }

    public function writeFrom(PsrStreamInterface $stream, $maxLen = -1)
    {
        $bytes = 0;
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
        return $bytes;
    }

    public function writeLine($line, $eol = null)
    {
        $eol = \coalesce($eol, $this->eol);
        $n   = \strlen($eol);
        if (\substr($line, -$n) != $eol) {
            $line .= $eol;
        }
        return $this->write($line);
    }

    private function checkStream()
    {
        if (!\is_resource($this->stream)) {
            throw new \RuntimeException('Stream is closed or detached!');
        }
    }

    public function getIterator()
    {
        $this->checkStream();
        return new StreamIterator($this);
    }
}
