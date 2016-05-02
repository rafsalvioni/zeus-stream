<?php

namespace Zeus\Stream;

use Zeus\Stream\Read\Readable;

/**
 * Implements a stream that read data from standard input.
 * 
 * However, the standard output depends of environment. In CLI mode uses
 * "php://stdin". Else, uses "php://input".
 * 
 * @author Rafael M. Salvioni
 * @package Zeus\Stream
 */
class Input extends Readable
{
    /**
     * 
     */
    public function __construct()
    {
        $cmd    = 'php://' . (\PHP_ON_WEB ? 'input' : 'stdin');
        $stream = Stream::open($cmd, 'r', false);
        parent::__construct($stream);
    }
}
