<?php

namespace Zeus\Stream\Write;

use Zeus\Stream\Read\ReadableStreamInterface;

/**
 * Trait to implement default methods of WritableInterface.
 * 
 * !!!! Should be used only in subclasses of Stream class !!!!
 * 
 * @author Rafael M. Salvioni
 */
trait WritableStreamTrait
{
    /**
     * 
     * @param string $data
     * @return int
     * @throws Exception
     */
    public function write($data)
    {
        try {
            $data = (string)$data;
            $size = \fwrite($this->resource, $data);
            if (!\is_int($size) || ($size == 0 && !empty($data))) {
                throw new Exception('Unable to write data!');
            }
            $this->emit('write', $data);
            return $size;
        }
        catch (\ErrorException $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * 
     * @param string $line
     * @param string $eol
     * @return int
     */
    public function writeLine($line, $eol = null)
    {
        $eol = \is_null($eol) ? $this->eol : $eol;
        $n   = \strlen($eol);
        if (\substr($line, -$n) != $eol) {
            $line .= $eol;
        }
        return $this->write($line);
    }

    /**
     * 
     * @param ReadableStreamInterface $stream
     * @param int $maxLen
     * @return int
     */
    public function writeFrom(ReadableStreamInterface $stream, $maxLen = -1)
    {
        try {
            /*$bytes = \stream_copy_to_stream(
                $stream->getResource(),
                $this->stream,
                $maxLen
            );*/
            return $maxLen < 0 ?
                $this->copyAll($stream) :
                $this->copyWithLimit($stream, $maxLen);
        }
        catch (\ErrorException $ex) {
            throw new Exception($ex->getMessage());
        }
    }
    
    /**
     * Copy all remainder contents of a stream to this stream.
     * 
     * @param ReadableStreamInterface $from From stream
     * @return int
     */
    private function copyAll(ReadableStreamInterface $from)
    {
        $bytes = 0;
        while (!$from->eof()) {
            $data = $from->read();
            $bytes += $this->write($data);
        }
        return $bytes;
    }
    
    /**
     * Copy $maxLen bytes from a stream.
     * 
     * @param ReadableStreamInterface $from From stream 
     * @param type $maxLen Max lenght of bytes
     * @return int
     */
    private function copyWithLimit(ReadableStreamInterface $from, $maxLen)
    {
        $bytes = 0;
        while (!$from->eof() && $maxLen > 0) {
            $len     = $maxLen > 1024 ? 1024 : $maxLen;
            $maxLen -= $len;
            $data    = $from->read($len);
            $bytes  += $this->write($data);
        }
        return $bytes;
    }
}
