<?php
/**
 * Created by PhpStorm.
 * User: yk
 * Date: 2017/3/17
 * Time: 20:43
 */

namespace Kyanag\SubUnit\FileQueue\Tests;


use Kyanag\SubUnit\FileQueue\Queue\FileQueue;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    /**
     * @param $name
     * @return FileQueue
     * @throws \Exception
     */
    public function testCreateQueue(){
        $file = "./Cache/log.log";
        \Kyanag\resetFile($file);
        $queue = FileQueue::createFromFile($file);
        $this->assertInstanceOf('Kyanag\SubUnit\FileQueue\Queue\FileQueue', $queue);
        return $queue;
    }

    /**
     * @param $queue FileQueue
     *
     * @depends testCreateQueue
     */
    public function testPush($queue){
        //push
        $push_value = "foo";
        $queue->push($push_value);
        $this->assertEquals(1, $queue->getLen());

        //pop
        $str = $queue->pop();
        $this->assertEquals($push_value, $str);
        $this->assertEquals(0, $queue->getLen());

        $arr = [];
        $max_size = $queue->getMaxSize();

        //填满，bottom 指针到底部
        for($i = 0;$i<$max_size ;$i++ ){
            $arr[] = $i;
            $queue->push((string)$i);
        }

        //头部指针也到底部
        for($i = 0;$i< $max_size; $i++){
            $data = $queue->shift();
            $this->assertEquals($data, $arr[$i]);
        }

        //底部指针回归
        for($i = 0;$i< 10; $i++){
            $arr[] = $i;

            $res = $data = $queue->push((string)$i);
            $this->assertEquals(true, $res);
        }

        //头部指针回归
        $data = $queue->shift();
        $this->assertEquals($arr[0], $data);
    }
}