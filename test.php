<?php
/**
 * Created by PhpStorm.
 * User: yk
 * Date: 2017/3/14
 * Time: 23:55
 */
include "vendor/autoload.php";
$file = "./log.log";
\Kyanag\resetFile($file);
try{
    $queue = \Kyanag\SubUnit\FileQueue\Queue\FileQueue::createFromFile($file);
    $index = 101;
    for($i = 0; $i<$index; $i++){
        $queue->push($i);
    }
    for($i = 0; $i<$index; $i++){
        echo $queue->pop() . "\n";
    }
}catch(Exception $e){
    unset($queue);
    echo $e->getMessage() . "\n";
}
