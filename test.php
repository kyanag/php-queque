<?php
/**
  多个线程读写队列   - 测试
  测试暂时不知道怎么用 phpunit 进行 多线程的测试
  所以，先暂时用这个代替吧
 **/
include "vendor/autoload.php";
$id = intval(@$argv[1]);
$file = "./cache/1.file";
\clearstatcache();
\Kyanag\resetFile($file);

$queue = \Kyanag\SubUnit\FileQueue\Queue\FileQueue::createFromFile($file);

if($id === 0){
    $io = array(
        0 => STDIN,
        1 => STDOUT,
    );
    proc_open("php test.php 1", $io, $pipes);
    proc_open("php test.php 2", $io, $pipes2);

    while(1){
        $time = time();
        if($queue->push((string)$time)){
            echo "main push time: {$time}\n";
        }
        sleep(1);
    }
}else if($id == 1){
    sleep(10);
    while(1){
        $a = $queue->shift();
        if($a !== null){
            echo "worker {$id} get time : {$a}\n";
        }
        sleep(2);
    }
}else{
    sleep(10);
    while(1){
        $a = $queue->pop();
        if($a !== null){
            echo "worker {$id} get time : {$a}\n";
        }
        sleep(2);
    }
}
