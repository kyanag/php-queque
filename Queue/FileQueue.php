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
    //空块
    const HEAD_CHAR_NO = 0;
    //包含模块
    const HEAD_CHAR_HAS = 1;


    /**
     * @var FileMemory
     */
    private $storage = false;

    //一个块多少个字节
    private $sizeof = 128;

    //头指针 见setHeadIndex()
    private $head_index = 0;

    //尾指针 见setBottomIndex()
    private $bottom_index = 0;

    //头指针归0次数（从end返回到0次数）
    private $reset_num = 0;

    //最大容量
    private $max_size = 100;

    //当前容量
    private $now_size = 0;

    public static function createFromFile($file){
        clearstatcache();
        if(is_file($file)){
            $memory = FileMemory::createFromFile($file);
            return static::createFormMemory($memory);
        }else{
            throw new \Exception("file not exists!");
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

        if($this->checkFile() !== 0){
            throw new \Exception("file type error;");
        }
    }

    /**
     * 入栈/入队列
     * @param $data
     * @return bool
     */
    public function push($data){
        $data = (string)$data;
        $insert_data = $data;
        if($this->now_size < $this->max_size){
            if($this->getStorage()->lock(LOCK_EX)){ //获取锁
                $this->loadConfig();//刷新配置
                //数据截断和填充
                $data_len = strlen($data);
                if($data_len > $this->sizeof){
                    $data = substr($data, 0, $this->sizeof);
                }else if($data_len < $this->sizeof){
                    $data .= str_repeat("\0", $this->sizeof - $data_len);
                }
                $this->go($this->bottom_index);//去往尾部指针
                $result = $this->getStorage()->save($data);
                if($result){
                    $this->now_size++;//容量+1
                    $this->bottom_index++;//尾部指针前进一格
                    $this->saveConfig(); //保存配置
                }
                $this->getStorage()->unlock(); //解锁

                return $result;
            }
        }
        return false;
    }

    /**
     * 出栈
     * @return null|string
     */
    public function pop(){
        if($this->now_size > 0){
            if($this->getStorage()->lock(LOCK_EX)){ //获取锁
                $this->loadConfig();//刷新配置
                $this->go($this->bottom_index - 1);//去往尾部指针

                $data = $this->getStorage()->read($this->sizeof);
                if($data){
                    $this->now_size--;//容量 -1
                    $this->bottom_index--;//尾部指针后退一格
                    $this->saveConfig(); //保存配置
                }
                $this->getStorage()->lock(LOCK_UN); //解锁
                return rtrim($data, "\0");
            }
        }
        return null;
    }


    /**
     * 出队列
     * @return null|string
     */
    public function shift(){
        if($this->now_size > 0){
            if($this->getStorage()->lock(LOCK_EX)){ //获取锁
                $this->loadConfig();

                $this->go($this->head_index);
                //刷新配置

                $data = $this->getStorage()->read($this->sizeof);
                if($data){
                    $this->now_size--;//容量 -1
                    $this->head_index++;//头部指针前进
                    $this->saveConfig(); //保存配置
                }
                $this->getStorage()->lock(LOCK_UN); //解锁
                return $data;
            }
        }
        return null;
    }



    private function getStorage(){
        return $this->storage;
    }

    /**
     * 当前地址移动 格数
     * @param $step
     * @return static
     */
    private function go($step){
        $now_index = $this->tellIndex();
        $go_index = ($step + $now_index + $this->max_size) % $this->max_size;
        $offset = $this->sizeof * $go_index + 10;
        $this->getStorage()->seek($offset);
        return $this;
    }

    private function tellIndex(){
        $now_addr = $this->getStorage()->tell() - 10;
        $now_index = $now_addr/ ($this->sizeof);
        return $now_index;
    }

    /**
     * 前10个字节为配置
     * 第一个字节为 一个元素占用空间（不大于256个字节）；
     * 第二个字节为 头指针归0次数
     *
     * 34字节为 队列头部index
     * 56字节为 队列尾部index
     * 78字节为 当前元素数量
     * 9,10字节为 最大元素数量
     */
    private function loadConfig(){
        //记住当前地址 - 无需记住当前地址
        //$now_addr = $this->getStorage()->tell();

        $this->getStorage()->seek(0, SEEK_SET);//开始前10个字节为配置
        $config = $this->getStorage()->read(10);

        //文件指针归位
        //$this->getStorage()->seek($now_addr);

        $this->setSizeOf(substr($config, 0, 1));
        $this->setResetNum(substr($config, 1, 1));
        $this->setHeadIndex(substr($config, 2, 2));
        $this->setBottomIndex(substr($config, 4, 2));
        $this->setNowSize(substr($config, 6, 2));
        $this->setMaxSize(substr($config, 8, 2));
    }

    private function saveConfig(){
        $config = "";
        $config .= \Kyanag\numberToBinary($this->sizeof, 1);
        $config .= \Kyanag\numberToBinary($this->reset_num, 1);
        $config .= \Kyanag\numberToBinary($this->head_index, 2);
        $config .= \Kyanag\numberToBinary($this->bottom_index, 2);
        $config .= \Kyanag\numberToBinary($this->now_size, 2);
        $config .= \Kyanag\numberToBinary($this->max_size, 2);

        //记住当前地址
        $now_addr = $this->getStorage()->tell();

        $this->getStorage()->seek(0);//前10个字节
        $config = $this->getStorage()->save($config);

        //文件指针归位
        $this->getStorage()->seek($now_addr);
        return $config;
    }

    /**
     * 元素最大占用空间 + 1    单位：字节
     * @param $buffer
     */
    private function setSizeOf($buffer){
        $sizeof = \Kyanag\binaryToUInt($buffer);
        $sizeof = $sizeof === 0 ? 256 : $sizeof;
        $this->sizeof = $sizeof;
    }

    /**
     * head_index 回归次数
     * @param $buffer
     */
    private function setResetNum($buffer){
        $reset_num = \Kyanag\binaryToUInt($buffer);
        $reset_num = $reset_num === 0 ? 256 : $reset_num;
        $this->reset_num = $reset_num;
    }

    /**
     * 头指针所处位置   单位：第x个元素（0开始）
     * @param $buffer
     */
    private function setHeadIndex($buffer){
        $head_index = \Kyanag\binaryToUInt($buffer);
        $this->head_index = $head_index;
    }

    /**
     * 尾部指针所处位置  单位：第x个元素（0开始）
     * @param $buffer
     */
    private function setBottomIndex($buffer){
        $bottom_index = \Kyanag\binaryToUInt($buffer);
        $this->bottom_index = $bottom_index;
    }

    /**
     * 当前队列实际容量
     * @param $buffer
     */
    private function setNowSize($buffer){
        $now_size = \Kyanag\binaryToUInt($buffer);
        $this->now_size = $now_size;
    }

    /**
     * 队列最大容量
     * @param $buffer
     */
    private function setMaxSize($buffer){
        $max_size = \Kyanag\binaryToUInt($buffer);
        $this->max_size = $max_size;
    }

    public function __destruct()
    {
        $this->getStorage()->close(); //关闭文件
    }

    private function checkFile(){
        $this->getStorage()->seek(0, SEEK_END);
        $now_addr = $this->getStorage()->tell();
        if($now_addr === 0){
            $this->initFile();
            return 0;
        }
        $this->loadConfig();
        $this->getStorage()->seek(0, SEEK_END);
        $all_len = $this->getStorage()->tell() - 10;
        $now_num = ($all_len / $this->sizeof);
        if(!is_int($now_num)){
            return 1;
        }
        $now_size = ($this->bottom_index - $this->head_index + $this->max_size) % $this->max_size;
        if($now_size !== $this->now_size){
            return 2;
        }
        return 0;
    }

    public function initFile(){
        $config = "";
        $config .= \Kyanag\numberToBinary(0, 1);
        $config .= \Kyanag\numberToBinary(0, 1);
        $config .= \Kyanag\numberToBinary(0, 2);
        $config .= \Kyanag\numberToBinary(0, 2);
        $config .= \Kyanag\numberToBinary(0, 2);
        $config .= \Kyanag\numberToBinary(100, 2);
        $this->getStorage()->seek(0);
        $this->getStorage()->save($config);
}
}