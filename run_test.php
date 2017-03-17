<?php
/**
 * Created by PhpStorm.
 * User: yk
 * Date: 2017/3/17
 * Time: 23:18
 */
$phpunit_path = 'F:\CodeRoot\PHP\file-queue\vendor\phpunit\phpunit\phpunit';

$file_name = $argv[1];
if(!is_file($file_name)){
    throw new Exception("file not exists!");
}
$test_file = $file_name;
$cmd = "php {$phpunit_path} $test_file";
$stdio = array(
    STDIN,
    STDOUT,
    STDERR,
);
proc_close(proc_open($cmd, $stdio, $pipes));