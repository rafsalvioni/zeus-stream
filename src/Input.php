<?php

namespace Zeus\Stream;

/**
 * Implements a stream that read data from PHP input.
 * 
 * @author Rafael M. Salvioni
 * @package Zeus\Stream
 */
class Input extends Stream
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
        $stream = Stream::open('php://input', 'r', false);
        parent::__construct($stream);
    }
}
