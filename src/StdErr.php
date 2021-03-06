<?php

namespace Zeus\Stream;

/**
 * Implements a stream that write data on process standard error.
 * 
 * @author Rafael M. Salvioni
 */
class StdErr extends Stream
{
    /**
     * Return the singleton instance.
     * 
     * @return self
     */
    public static function getInstance(): self
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
        $stream = Stream::open('php://stderr', 'w', false);
        parent::__construct($stream);
    }
}
