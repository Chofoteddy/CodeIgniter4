<?php namespace CodeIgniter\Cache\Handlers;

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline, array $errcontext) {
    //throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
});

class FileHandlerTest extends \CIUnitTestCase
{
    private static $directory = 'FileHandler';
    private static $key1      = 'key1';
    private static $key2      = 'key2';
    private static $key3      = 'key3';

    private static function getKeyArray()
    {
        return [
            self::$key1,
            self::$key2,
            self::$key3,
        ];
    }

    private static $dummy = 'dymmy';
    private        $fileHandler;
    private        $config;

    public function setUp()
    {
        //Initialize path
        $this->config            = new \Config\Cache();
        $this->config->storePath .= self::$directory;

        if (! is_dir($this->config->storePath))
        {
            mkdir($this->config->storePath, 0777, true);
        }

        $this->fileHandler = new FileHandler($this->config);
        $this->fileHandler->initialize();
    }

    public function tearDown()
    {
        if (is_dir($this->config->storePath))
        {
            chmod($this->config->storePath, 0777);

            foreach (self::getKeyArray() as $key)
            {
                if (is_file($this->config->storePath.DIRECTORY_SEPARATOR.$key))
                {
                    chmod($this->config->storePath.DIRECTORY_SEPARATOR.$key, 0777);
                    unlink($this->config->storePath.DIRECTORY_SEPARATOR.$key);
                }
            }

            rmdir($this->config->storePath);
        }
    }

    public function testNew()
    {
        $this->assertInstanceOf(FileHandler::class, $this->fileHandler);
    }

    public function testSetDefaultPath()
    {
        //Initialize path
        $config            = new \Config\Cache();
        $config->storePath = null;

        $this->fileHandler = new FileHandler($config);
        $this->fileHandler->initialize();

        $this->assertInstanceOf(FileHandler::class, $this->fileHandler);
    }

    public function testGet()
    {
        $this->fileHandler->save(self::$key1, 'value', 1);

        $this->assertSame('value', $this->fileHandler->get(self::$key1));
        $this->assertFalse($this->fileHandler->get(self::$dummy));

        \CodeIgniter\CLI\CLI::wait(2);
        $this->assertFalse($this->fileHandler->get(self::$key1));
    }

    public function testSave()
    {
        $this->assertTrue($this->fileHandler->save(self::$key1, 'value'));

        // The FileHandler always ensures the directory is writable...
        chmod($this->config->storePath, 0444);
        $this->assertTrue($this->fileHandler->save(self::$key2, 'value'));
    }

    public function testDelete()
    {
        $this->fileHandler->save(self::$key1, 'value');

        $this->assertTrue($this->fileHandler->delete(self::$key1));
        $this->assertFalse($this->fileHandler->delete(self::$dummy));
    }

    public function testIncrement()
    {
        $this->fileHandler->save(self::$key1, 1);
        $this->fileHandler->save(self::$key2, 'value');

        $this->assertSame(11, $this->fileHandler->increment(self::$key1, 10));
        $this->assertFalse($this->fileHandler->increment(self::$key2, 10));
        $this->assertSame(10, $this->fileHandler->increment(self::$key3, 10));
    }

    public function testDecrement()
    {
        $this->fileHandler->save(self::$key1, 10);
        $this->fileHandler->save(self::$key2, 'value');
        $this->fileHandler->save(self::$key3, 0);

        $this->assertSame(9, $this->fileHandler->decrement(self::$key1, 1));
        $this->assertFalse($this->fileHandler->decrement(self::$key2, 1));
        $this->assertSame(-1, $this->fileHandler->decrement(self::$key3, 1));
    }

    public function testClean()
    {
        $this->fileHandler->save(self::$key1, 1);
        $this->fileHandler->save(self::$key2, 'value');

        $this->assertTrue($this->fileHandler->clean());

        $this->fileHandler->save(self::$key1, 1);
        $this->fileHandler->save(self::$key2, 'value');
    }

    public function testGetMetaData()
    {
        $time = time();
        $this->fileHandler->save(self::$key1, 'value');

        $this->assertFalse($this->fileHandler->getMetaData(self::$dummy));

        $actual = $this->fileHandler->getMetaData(self::$key1);
        $this->assertLessThanOrEqual(60, $actual['expire']-$time);
        $this->assertLessThanOrEqual(0, $actual['mtime']-$time);
        $this->assertSame('value', $actual['data']);
    }

    public function testIsSupported()
    {
        $this->assertTrue($this->fileHandler->isSupported());
    }

    //--------------------------------------------------------------------

    public function testFileHandler()
    {
        $fileHandler = new BaseTestFileHandler();

        $actual = $fileHandler->getFileInfoTest();

        $this->assertArrayHasKey('server_path', $actual);
        $this->assertArrayHasKey('size', $actual);
        $this->assertArrayHasKey('date', $actual);
        $this->assertArrayHasKey('readable', $actual);
        $this->assertArrayHasKey('writable', $actual);
        $this->assertArrayHasKey('executable', $actual);
        $this->assertArrayHasKey('fileperms', $actual);
    }
}

final class BaseTestFileHandler extends FileHandler
{
    private static $directory = 'FileHandler';
    private        $config;

    public function __construct()
    {
        $this->config            = new \Config\Cache();
        $this->config->storePath .= self::$directory;

        parent::__construct($this->config);
    }

    public function getFileInfoTest()
    {
        return $this->getFileInfo($this->config->storePath, [
            'name',
            'server_path',
            'size',
            'date',
            'readable',
            'writable',
            'executable',
            'fileperms',
        ]);
    }
}
