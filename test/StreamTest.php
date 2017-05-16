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
    private $closed;
    
    public function setUp()
    {
        $this->stream = new \Zeus\Stream\TempFile();
        $this->reader = Stream::open(__FILE__, 'r');
        $this->writer = new \Zeus\Stream\Output();
        $this->closed = (new \Zeus\Stream\Memory())->close();
        
        $this->stream->eol("\n");
        $this->reader->eol("\n");
        $this->writer->eol("\n");
    }
    
    /**
     * @test
     */
    public function openTest()
    {
        try {
            Stream::open('php://abcd', 'r');
            $this->assertTrue(false);
        } catch (\RuntimeException $ex) {
            $this->assertTrue(true);
        }
    }

    /**
     * @test
     */
    public function blockingTest()
    {
        $block   = $this->stream->isBlocked();
        $this->stream->toggleBlocking();
        $inverse = $this->stream->isBlocked();
        $this->assertFalse($block && $inverse && !$this->closed->isBlocked());
    }
    
    /**
     * @test
     */
    public function checksTest()
    {
        $this->assertTrue(
           !$this->stream->isPersistent() &&
           $this->stream->isSeekable() &&
           $this->reader->isReadable() &&
           !$this->reader->isWritable() &&
           $this->writer->isWritable() &&
           !$this->writer->isReadable() &&
           !$this->writer->isSeekable() &&
           $this->reader->isLocal() &&
           !$this->closed->isBlocked() &&
           !$this->closed->isPersistent() &&
           !$this->closed->isReadable() &&
           !$this->closed->isWritable() &&
           !$this->closed->isSeekable()
        );
    }

    /**
     * @test
     */
    public function readTest()
    {
        $this->assertEquals($this->reader->read(3), "<?p");
        try {
            $this->writer->read();
            $this->assertTrue(false);
        } catch (\RuntimeException $ex) {
            $this->assertTrue(true);
        }
    }
    
    /**
     * @test
     */
    public function readLineTest()
    {
        $this->assertEquals($this->reader->readLine(), "<?php\n");
        try {
            $this->writer->readLine();
            $this->assertTrue(false);
        } catch (\RuntimeException $ex) {
            $this->assertTrue(true);
        }
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
        
        $bytes = $this->reader->writeFrom($this->writer);
        $this->assertEquals($bytes, 0);
    }
    
    /**
     * @test
     */
    public function writeFromMaxLenTest()
    {
        $bytes = $this->writer->writeFrom($this->reader, 10);
        $this->assertEquals($bytes, 10);
        
        $bytes = $this->reader->writeFrom($this->writer);
        $this->assertEquals($bytes, 0);
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
        
        try {
            $this->reader->writeLine(__CLASS__);
            $this->assertTrue(false);
        } catch (\RuntimeException $ex) {
            $this->assertTrue(true);
        }
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
        $this->assertTrue($this->writer->getSize() === null);
    }
    
    /**
     * @test
     */
    public function detachTest()
    {
        $this->assertFalse($this->reader->isDetached());
        $this->reader->detach();
        $this->assertTrue($this->reader->isDetached());

        try {
            $this->reader->read();
            $this->assertTrue(false);
        } catch (\RuntimeException $ex) {
            $this->assertTrue(true);
        }
        
        try {
            $this->reader->getIterator();
            $this->assertTrue(false);
        } catch (\RuntimeException $ex) {
            $this->assertTrue(true);
        }
    }
}
