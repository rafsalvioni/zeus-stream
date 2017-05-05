<?php

namespace ZeusTest\Stream;

use Zeus\Stream\Stream;

/**
 * Test to main features of Stream package.
 * 
 * @author Rafael M. Salvioni
 */
class StreamTest extends \PHPUnit_Framework_TestCase
{
    private $stream;
    private $reader;
    private $writer;
    
    public function setUp()
    {
        $this->stream = new \Zeus\Stream\TempFile();
        $this->reader = Stream::open(__FILE__, 'r');
        $this->writer = new \Zeus\Stream\Output();
        
        $this->stream->eol("\n");
        $this->reader->eol("\n");
        $this->writer->eol("\n");
    }
    
    /**
     * @test
     */
    public function blockingTest()
    {
        $block   = $this->stream->isBlocked();
        $this->stream->toggleBlocking();
        $inverse = $this->stream->isBlocked();
        $this->assertFalse($block && $inverse);
    }
    
    /**
     * @test
     */
    public function checksTest()
    {
        $this->assertTrue(
           !$this->stream->isPersistent() &&
           $this->stream->isSeekable() &&
           $this->stream->isReadable() &&
           $this->stream->isWritable() &&
           $this->reader->isReadable() &&
           !$this->reader->isWritable() &&
           $this->writer->isWritable() &&
           !$this->writer->isReadable() &&
           !$this->writer->isSeekable()
        );
    }

    /**
     * @test
     */
    public function readTest()
    {
        $this->assertEquals($this->reader->read(3), "<?p");
    }
    
    /**
     * @test
     */
    public function readLineTest()
    {
        $this->assertEquals($this->reader->readLine(), "<?php\n");
    }
    
    /**
     * @test
     */
    public function iteratorTest()
    {
        $string = "First\nSecond\nThird line\nFourth";
        $stream = \fopen('php://memory', 'r+');
        $stream = new Stream($stream);
        $stream->write($string);
        $stream->eol("\n");
        $newstring = '';
        
        foreach ($stream as $line) {
            $newstring .= $line;
        }
        
        $this->assertTrue(\trim($string) == \trim($newstring));
    }
    
    /**
     * @test
     */
    public function writeFromTest()
    {
        $bytes = $this->writer->writeFrom($this->reader);
        $this->assertEquals($bytes, \filesize(__FILE__));
    }
    
    /**
     * @test
     */
    public function writeFromMaxLenTest()
    {
        $bytes = $this->writer->writeFrom($this->reader, 10);
        $this->assertEquals($bytes, 10);
    }
    
    /**
     * @test
     */
    public function writeLineTest()
    {
        $line = __CLASS__;
        $this->stream->writeLine($line);
        $this->stream->rewind();
        $len  = \strlen(__CLASS__) + 1;
        $this->assertEquals($line . "\n", $this->stream->read($len));
    }
    
    /**
     * @test
     */
    public function getSizeTest()
    {
        $str = __CLASS__;
        $len = \strlen($str);
        $this->stream->write($str);
        $this->assertEquals($len, $this->stream->getSize());
    }
    
    /**
     * @test
     */
    public function detachTest()
    {
        $stream = $this->stream->detach();
        $this->assertTrue(\is_resource($stream));
        
        try {
            $bytes = $this->stream->write('abcd');
            $this->assertEquals($bytes, 4);
        }
        catch (\RuntimeException $ex) {
            $this->assertTrue(true);
        }
        
        unset($this->stream);
        $this->assertTrue(\is_resource($stream));
    }
}
