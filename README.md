# php-queque
php实现的基于 文件存储 的队列（目标）
目前实现了数据结构-栈 push/pop

#计划 
换一种实现方式，目前是在php代码中指定一个块的大小 
...
#test.php
$file = "./Cache/memory";

//重置文件
\Kyanag\SubUnit\FileQueue\Helper\resetFile($file);

$queue = Kyanag\SubUnit\FileQueue\Queue\FileQueue::createFromFile($file);

//设置元素大小 单位 字(8bit)，
$queue->setSizeOf(10);
$num = 10;

echo $queue->pop() . "\n";

for($i = 0; $i<$num; $i++){
    $queue->push($i);
}

while($i = $queue->pop()){
    echo $i ."\n";
}
...
