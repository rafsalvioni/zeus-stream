<?php

namespace Zeus\Stream;

use Zeus\Stream\Write\Writable;

/**
 * Implements a stream that write data on standard output.
 * 
 * However, the standard output depends of environment. In CLI mode uses
 * "php://stdout". Else, uses "php://output".
 *
 * @author Rafael M. Salvioni
 */
class Output extends Writable
{
    /**
     * 
     * @param string $data Initial data
     */
    public function __construct($data = null)
    {
        $cmd    = 'php://' . (isset($_SERVER['REMOTE_ADDR']) ? 'output' : 'stdout');
        $stream = Stream::open($cmd, 'w', false);
        parent::__construct($stream);
        $this->write($data);
    }
}
