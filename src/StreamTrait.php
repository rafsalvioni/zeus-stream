<?php

namespace Zeus\Stream;

use Zeus\Event\EmitterTrait;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;

/**
 * Trait to implements default methods of StreamInterface.
 * 
 * @author Rafael M. Salvioni
 */
trait StreamTrait
{
    use EmitterTrait;
    
    /**
     * Readable flag
     *
     * @var bool
     */
    private $readable = false;
    /**
     * Writable flag
     *
     * @var bool
     */
    private $writable = false;
    /**
     * Seekable flag
     *
     * @var bool
     */
    private $seekable = false;
    /**
     * Persistent flag
     *
     * @var bool
     */
    private $persistent = false;
    /**
     * Local stream flag
     * 
     * @var bool
     */
    private $local = false;
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
     * @return resource|null
     */
    public function detach()
    {
        $stream = $this->stream;
        $this->stream = null;
        $this->persistent = false;
        $this->seekable = false;
        $this->writable = false;
        $this->readable = false;
        return $stream;
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
     * @return int|null
     */
    public function getSize()
    {
        if (!$this->isSeekable()) {
            return null;
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
        return $this->persistent;
    }

    /**
     * 
     * @return bool
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * 
     * @return bool
     */
    public function isSeekable()
    {
        return $this->seekable;
    }

    /**
     * 
     * @return bool
     */
    public function isWritable()
    {
        return $this->writable;
    }
    
    /**
     * 
     * @return bool
     */
    public function isLocal()
    {
        return $this->local;
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
     * @return self
     */
    public function rewind()
    {
        return $this->seek(0);
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
     * @param PsrStreamInterface $stream
     * @param int $maxLen
     * @return int
     */
    public function writeFrom(PsrStreamInterface $stream, $maxLen = -1)
    {
        if (!$this->isWritable() || !$stream->isReadable()) {
            return 0;
        }
        
        $bytes = $wbytes = 0;
        while ($maxLen < 0 && !$stream->eof()) {
            $data   = $stream->read(StreamInterface::DEFAULT_READ);
            $wbytes = $this->write($data);
            $bytes += $wbytes;
            if (!$wbytes) {
                break;
            }
        }
        while ($maxLen > 0 && !$stream->eof()) {
            $toRead  = $maxLen >= StreamInterface::DEFAULT_READ ?
                        StreamInterface::DEFAULT_READ :
                        $maxLen;
            $data    = $stream->read($toRead);
            $length  = \strlen($data);
            $maxLen -= $length;
            $wbytes  = $this->write($data);
            $bytes += $wbytes;
            if (!$wbytes) {
                break;
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
}
