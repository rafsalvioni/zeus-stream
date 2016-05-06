<?php

namespace ZeusTest\Stream;

use Zeus\Stream\StreamWrapper;

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
        $this->stream = new \Zeus\Stream\TempFileStream();
        $this->reader = StreamWrapper::open(__FILE__, 'r');
        $this->writer = new \Zeus\Stream\OutputStream();
        
        $this->stream->eol("\n");
        $this->reader->eol("\n");
        $this->writer->eol("\n");
    }
    
    /**
     * @test
     */
    public function factoryTest()
    {
        $this->assertTrue(
            (StreamWrapper::factory(\STDIN) instanceof \Zeus\Stream\Read\ReadableStream) &&
            (StreamWrapper::factory(\fopen('php://output', 'w')) instanceof \Zeus\Stream\Write\WritableStream)
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
        $this->assertEquals($this->reader->readLine(), "<?php\n");
    }
    
    /**
     * @test
     */
    public function iterator()
    {
        $string = '';
        $this->reader->cursorBegin();
        foreach ($this->reader as $line) {
            $string .= $line;
        }
        $this->assertTrue(\trim($string) == \trim(\file_get_contents(__FILE__)));
    }
    
    /**
     * @test
     * @expectedException \Exception
     */
    public function readableException()
    {
        new \Zeus\Stream\Read\ReadableStream(\STDOUT);
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
        $this->stream->writeLine($line);
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
