<?php

namespace Zeus\Stream;

use Zeus\Stream\Seek\SeekableStream;

/**
 * Implements a stream that using temperary files.
 * 
 * @author Rafael M. Salvioni
 */
class TempFileStream extends SeekableStream
{
    /**
     *
     * @param string $data Initial data
     * @param string $mode Open mode
     */
    public function __construct($data = null, $mode = 'r+')
    {
        $stream = StreamWrapper::open('php://temp', $mode, false);
        parent::__construct($stream);

        $this->write($data);
    }
}
