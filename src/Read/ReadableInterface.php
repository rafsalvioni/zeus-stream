<?php

namespace Zeus\Stream\Read;

use Zeus\Stream\StreamInterface;

/**
 * Identifies a readable stream.
 * 
 * @author Rafael M. Salvioni
 */
interface ReadableInterface extends StreamInterface, \IteratorAggregate
{
    /**
     * Returns a quantity of bytes from stream.
     *
     * @param int $bytes How much bytes?
     * @return string
     * @throws Exception
     */
    public function read($bytes = 1024);

    /**
     * Returns a line of data.
     * 
     * $eol will be appended in returned value.
     *
     * @param string $eol "end of line" satring
     * @return string
     * @throws Exception
     */
    public function readLine($eol = \PHP_EOL);
    
    /**
     * Retruns all remaining data of stream.
     * 
     * @return string
     * @throws Exception
     */
    public function readAll();
    
    /**
     * Checks if a stream is ended.
     * 
     * @return bool
     */
    public function eof();
}
