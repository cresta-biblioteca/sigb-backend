<?php
namespace Tests\Helper;

// Clase helper para la simulacion de php://input en tests
class TestStreamWrapper
{
    public static $data = '';
    private $position = 0;
    public $context;
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->position = 0;
        return true;
    }

    public function stream_read($count)
    {
        $ret = substr(self::$data, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }

    public function stream_eof()
    {
        return $this->position >= strlen(self::$data);
    }

    public function stream_stat()
    {
        return [];
    }

    public function stream_seek($offset, $whence = SEEK_SET)
    {
        return false;
    }
}