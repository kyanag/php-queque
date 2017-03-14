<?php
/**
 * Created by PhpStorm.
 * User: yk
 * Date: 2017/3/13
 * Time: 18:52
 */

namespace Kyanag\SubUnit\FileQueue\Queue;


use Kyanag\SubUnit\FileQueue\Memory\FileMemory;

class FileQueue
{
    //一个块多少个字节
    private $sizeof = 128;


    const HEAD_CHAR_NO = 0;

    const HEAD_CHAR_HAS = 1;

    /**
     * @var FileMemory
     */
    private $storage = false;


    public static function createFromFile($file){
        clearstatcache();
        if(is_file($file)){
            $memory = FileMemory::createFromFile($file);
            return static::createFormMemory($memory);
        }else{
            throw new \Exception("not is file!");
        }
    }

    public static function createFormMemory($memory){
        if($memory instanceof FileMemory){
            return new static($memory);
        }else{
            throw new \Exception("");
        }
    }

    /**
     * FileQueue constructor.
     * @param $file_ptr
     */
    private function __construct($storage)
    {
        $this->storage = $storage;
    }

    private function getStorage(){
        return $this->storage;
    }

    /**
     * 设置
     * @param $num
     */
    public function setSizeOf($num){
        $num = intval($num);
        if($num <= 0 or $num >= 1024){
            throw new \Exception("size in (0,1024)");
        }
        //第1个字节是检测 0 代表此处为空 1 代表此处已经写入数据
        $this->sizeof = $num+1;
    }

    public function push($data){
        $data = (string)$data;
        $data = self::HEAD_CHAR_HAS . $data;
        $len = strlen($data);
        if($len > $this->sizeof){
            //截取长度
            $data = substr($data, 0, $this->sizeof);
        }else if($len < $this->sizeof){
            $margin = $this->sizeof - $len;
            //全部补全为\0
            $data = $data . str_repeat("\0", $margin);
        }
        //设置为末尾，然后写入文件
        return $this->getStorage()->seek(0, SEEK_END)->secretSave($data);
    }

    public function step($step){
        $offset = $step * $this->sizeof;
        $nowIndex = $this->getStorage()->tell();
        if(($offset + $nowIndex) < 0){
            throw new \Exception("seek < 0");
        }
        $this->getStorage()->seek($offset, SEEK_CUR);
        return $this;
    }

    /**
     * 先进后出 尾部pop
     * @return null|string
     * @throws \Exception
     */
    public function pop(){
        $this->getStorage()->lock(LOCK_EX); //开启文件锁
        //设置为末尾，然后写入文件
        $this->getStorage()->seek(0, SEEK_END);
        $last = $this->getStorage()->tell();
        do{
            if($last <= 0){
                break;
            }
            $last = $this->step(-1)->getStorage()->tell();
        }while($this->singleCharAscii() === self::HEAD_CHAR_NO);

        $last = $this->getStorage()->tell();
        if($last === 0 && $this->singleCharAscii() === self::HEAD_CHAR_NO){
            //空队列
            return null;
        }
        $this->getStorage()->save(chr(self::HEAD_CHAR_NO));//设置为空，指针进一位
        $last_data = $this->getStorage()->read($this->sizeof - 1);
        $last_data = trim($last_data, "\0");
        $this->getStorage()->unlock(); //关闭文件锁
        return $last_data;
    }

    public function hPop(){
        //设置为文件开头
        $this->getStorage()->seek(0);
        $end = $this->getEndSeek();
        while($this->singleCharAscii() === 0){
            $this->step(1);
        }

    }

    public function getEndSeek(){
        $now = $this->getStorage()->tell();
        $this->getStorage()->seek(0, SEEK_END);
        $end = $this->getStorage()->tell();

        $this->getStorage()->seek($now);
        return $end;
    }

    public function singleCharAscii(){
        $str = $this->getStorage()->read(1);
        $this->getStorage()->seek(-1, SEEK_CUR);//回退
        if( $str ){
            return unpack("C", $str)[1];
        }
        return 0;//默认为0
    }

    public function __destruct()
    {
        $this->getStorage()->close(); //关闭文件
    }
}