# php-queque v0.02
php实现的基于 文件存储 的队列

# 文件格式
前10个字符为配置

    1字节    一个元素占用空间（不大于256个字节）
    2字节    头指针归0次数
    34字节为 队列头部index （单位为一个元素）
    56字节为 队列尾部index
    78字节为 当前元素数量
    9,10字节为 最大元素数量

之后的就是数据元素了

```php
#test.php
include "vendor/autoload.php";
$file = "./log.log";
\Kyanag\resetFile($file);
try{
    $queue = \Kyanag\SubUnit\FileQueue\Queue\FileQueue::createFromFile($file);
    $index = 101;
    
    //先进后出
    for($i = 0; $i<$index; $i++){
        $queue->push($i);
    }
    for($i = 0; $i<$index; $i++){
        echo $queue->pop() . "\n";
    }
    
    //先进先出
    for($i = 0; $i<$index; $i++){
        $queue->push($i);
    }
    for($i = 0; $i<$index; $i++){
        echo $queue->shift() . "\n";
    }
}catch(Exception $e){
    unset($queue);
    echo $e->getMessage() . "\n";
}
```
