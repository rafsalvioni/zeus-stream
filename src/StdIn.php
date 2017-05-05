<?php

namespace Zeus\Stream;

/**
 * Implements a stream that read data on process standard input.
 * 
 * @author Rafael M. Salvioni
 */
class StdIn extends Stream
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
        $stream = Stream::open('php://stdin', 'r', false);
        parent::__construct($stream);
    }
}
