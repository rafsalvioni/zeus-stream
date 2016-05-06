<?php

namespace Zeus\Stream;

use Zeus\Stream\Write\WritableStream;

/**
 * Implements a stream that write data on process standard output.
 * 
 * @author Rafael M. Salvioni
 */
class StdErrStream extends WritableStream
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
        parent::__construct(\STDERR);
    }
}