<?php

namespace Zeus\Stream;

use Zeus\Stream\Write\WritableStream;

/**
 * Implements a stream that write data on php output buffer.
 * 
 * @author Rafael M. Salvioni
 */
class OutputStream extends WritableStream
{
    /**
     * Return the singleton instance.
     * 
     * @return self
     */
    public static function getInstance()
    {
        static $instance = null;
        if (!$instance) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * 
     */
    public function __construct()
    {
        $stream = StreamWrapper::open('php://output', 'w', false);
        parent::__construct($stream);
    }
}
