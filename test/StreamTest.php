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
        $this->stream = new \Zeus\Stream\Temp();
        $this->reader = Stream::open(__FILE__, 'r');
        $this->writer = new \Zeus\Stream\Output();
    }
    
    /**
     * @test
     */
    public function factoryTest()
    {
        $this->assertTrue(
            (Stream::factory(\STDIN) instanceof \Zeus\Stream\Read\Readable) &&
            (Stream::factory(\fopen('php://output', 'w')) instanceof \Zeus\Stream\Write\Writable)
        );
    }

    /**
     * @test
     */
    public function blocking()
    {
        $block   = $this->stream->isBlocked();
        $this->stream->toggleBlocking();
        $inverse = $this->stream->isBlocked();
        $this->assertFalse($block && $inverse);
    }
    
    /**
     * @test
     */
    public function checks()
    {
        $this->assertTrue(
           !$this->stream->isPersistent() &&
           $this->stream->isSeekable() &&
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
    public function read()
    {
        $this->assertEquals($this->reader->read(3), "<?p");
    }
    
    /**
     * @test
     */
    public function readLine()
    {
        $this->assertEquals(\trim($this->reader->readLine("\n")), "<?php");
    }
    
    /**
     * @test
     */
    public function iterator()
    {
        $string = '';
        foreach ($this->reader as $line) {
            $string .= $line;
        }
        $this->assertTrue(true);
    }
    
    /**
     * @test
     * @expectedException \Exception
     */
    public function readableException()
    {
        new \Zeus\Stream\Read\Readable(\STDOUT);
    }
    
    /**
     * @test
     * @expectedException \Zeus\Stream\Write\Exception
     */
    public function writeException()
    {
        $this->reader->write("\n");
    }
    
    /**
     * @test
     */
    public function writeFrom()
    {
        $bytes = $this->writer->writeFrom($this->reader);
        $this->assertEquals($bytes, \filesize(__FILE__));
    }
    
    /**
     * @test
     */
    public function writeFromMaxLen()
    {
        $bytes = $this->writer->writeFrom($this->reader, 10);
        $this->assertEquals($bytes, 10);
    }
    
    /**
     * @test
     */
    public function writeLine()
    {
        $line = __CLASS__;
        $this->stream->writeLine($line, "\n");
        $this->stream->cursorBegin();
        $len  = \strlen(__CLASS__) + 1;
        $this->assertEquals($line . "\n", $this->stream->read($len));
    }
    
    /**
     * @test
     */
    public function getLength()
    {
        $str = __CLASS__;
        $len = \strlen($str);
        $this->stream->write($str);
        $this->assertEquals($len, $this->stream->getLength());
    }
}
