<?php

namespace Zeus\Stream;

use Zeus\Stream\Write\Writable;

/**
 * Implements a stream that write data on process standard output.
 * 
 * @author Rafael M. Salvioni
 */
class StdErr extends Writable
{
    /**
     * 
     * @param string $data Initial data
     */
    public function __construct($data = null)
    {
        parent::__construct(\STDERR);
        $this->write($data);
    }
}
