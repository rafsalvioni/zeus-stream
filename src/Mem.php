<?php

namespace Zeus\Stream;

use Zeus\Stream\Seek\Seekable;

/**
 * Implements a stream of data in memory.
 * 
 * However, allows create a temp file if a memory limit is fired.
 * 
 * @author Rafael M. Salvioni
 */
class Mem extends Seekable
{
    /**
     *
     * @param string $data Initial data
     * @param int $memSize Memory limit
     * @param string $mode Open mode
     */
    public function __construct($data = null, $memSize = null, $mode = 'w+')
    {
        $memSize = \intval($memSize);
        $file    = 'php://';

        if ($memSize > 0) {
            $file .= "temp/maxmemory:$memSize";
        }
        else {
            $file .= 'memory';
        }

        $stream = Stream::open($file, $mode, false);
        parent::__construct($stream);

        $this->write($data);
    }
}
