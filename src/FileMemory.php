<?php
/**
 * Created by PhpStorm.
 * User: yk
 * Date: 2017/11/8
 * Time: 11:19
 */

namespace Kyanag\FileQueue;


class FileMemory
{
    private $file = false;

    const UN_LOCK = LOCK_UN;

    public static function createFromFile($file){
        clearstatcache();
        if(($file)){
            $fhandle = fopen($file, "rb+");
            return new self($fhandle);
        }else{
            throw new \Exception("file not found");
        }
    }

    /**
     * FileQueue constructor.
     * !!!! -- important -- !!!! fopen(FILE_NAME, "r+") : must 'r+'
     * @param $file_ptr resource
     */
    private function __construct($file_ptr)
    {
        if(is_resource($file_ptr)){
            $this->file = $file_ptr;
        }else{
            throw new \TypeError("need resource!");
        }
    }

    /**
     * @param $seek int 位置
     * @param $length int 内容长度
     */
    public function read($length){
        $data = fread($this->file, $length);
        return $data;
    }

    public function save($data){
        $res = fwrite($this->file, $data);
        fflush($this->file);
        return ($res !== false);
    }

    public function seek($offset, $wherece = SEEK_SET){
        $res = fseek($this->file, $offset, $wherece);
        ($res === 0);
        return $this;
    }

    public function lock($lock_type, $block = null){
        return flock($this->file, $lock_type, $block);
    }

    public function unlock(){
        return $this->lock(self::UN_LOCK);
    }

    public function tell(){
        return ftell($this->file);
    }

    public function secretSave($data, $offset = null){
        if($offset !== null){
            $this->seek($offset);
        }
        $this->lock(LOCK_EX); //阻塞锁
        $write_res = $this->save($data); //写入
        $this->unlock(); //解锁
        return $write_res;
    }

    public function close(){
        fclose($this->file);
    }
}