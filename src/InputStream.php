<?php

namespace Zeus\Stream;

use Zeus\Stream\Read\ReadableStream;

/**
 * Implements a stream that read data from PHP input.
 * 
 * @author Rafael M. Salvioni
 * @package Zeus\Stream
 */
class InputStream extends ReadableStream
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
        $stream = StreamWrapper::open('php://input', 'r', false);
        parent::__construct($stream);
    }
}
