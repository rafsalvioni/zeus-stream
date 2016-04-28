<?php

namespace Zeus\Stream\Read;

/**
 * Trait to implement default methods of ReadableInterface.
 * 
 * !!!! Should be used only in subclasses of Stream class !!!!
 * 
 * @author Rafael M. Salvioni
 */
trait ReadTrait
//class ReadTrait extends \Zeus\Stream\Stream implements ReadableInterface
{
    /**
     *
     * @param int $bytes
     * @return string
     * @throws Exception
     */
    public function read($bytes = 1024)
    {
        try {
            $data = \fread($this->resource, $bytes);
            $this->emit('read', $data);
            return $data;
        }
        catch (\ErrorException $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * 
     * @param string $eol
     * @return string
     * @throws Exception
     */
    public function readLine($eol = \PHP_EOL)
    {
        try {
            $line = \stream_get_line($this->resource, 0, $eol);
            $this->emit('read', $line);
            return $line . $eol;
        }
        catch (\ErrorException $ex) {
            throw new Exception($ex->getMessage());
        }
    }
    
    /**
     * 
     * @return string
     * @throws Exception
     */
    public function readAll()
    {
        try {
            $data = \stream_get_contents($this->resource);
            $this->emit('read', $data);
            return $data;
        }
        catch (\ErrorException $ex) {
            throw new Exception($ex->getMessage());
        }
    }
    
    /**
     * 
     * @return bool
     */
    public function eof()
    {
        return \feof($this->resource);
    }

    /**
     * 
     * @return Iterator
     */
    public function getIterator()
    {
        return new Iterator($this);
    }
}
