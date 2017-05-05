<?php

namespace Zeus\Stream;

/**
 * Implements a stream of data in memory.
 * 
 * However, allows create a temp file if a memory limit is fired.
 * 
 * @author Rafael M. Salvioni
 */
class Memory extends Stream
{
    /**
     *
     * @param string $data Initial data
     * @param int $memSize Memory limit
     * @param string $mode Open mode
     */
    public function __construct(
        string $data = null, int $memSize = null, string $mode = 'w+'
    ){
        $file = 'php://';
        if ($memSize > 0) {
            $file .= "temp/maxmemory:$memSize";
        }
        else {
            $file .= 'memory';
        }

        $stream = Stream::open($file, $mode, false);
        parent::__construct($stream);

        if ($data) {
            $this->write($data);
        }
    }
}
