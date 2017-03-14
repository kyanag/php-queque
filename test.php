<?php
/**
 * Created by PhpStorm.
 * User: yk
 * Date: 2017/3/14
 * Time: 23:55
 */
include "./vendor/autoload.php";

$file = "./Cache/memory";

//重置文件
\Kyanag\SubUnit\FileQueue\Helper\resetFile($file);

$queue = Kyanag\SubUnit\FileQueue\Queue\FileQueue::createFromFile($file);

$num = 10;

echo $queue->pop() . "\n";

for($i = 0; $i<$num; $i++){
    $queue->push($i);
}

while($i = $queue->pop()){
    echo $i ."\n";
}
