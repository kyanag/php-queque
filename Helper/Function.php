<?php
namespace  Kyanag\SubUnit\FileQueue\Helper;
/**
 * Created by PhpStorm.
 * User: yk
 * Date: 2017/3/13
 * Time: 19:23
 */

function strToBin($str){
    $arr = array();
    $str_ascii_code_s = unpack("C*", $str);
    foreach($str_ascii_code_s as $ascii_code){
        $arr[] = base_convert($ascii_code, 16, 2);
    }
    return $arr;
}

function resetFile($filename){
    if(!is_file($filename)){
        fclose(fopen($filename, "w+"));
    }else{
        file_put_contents($filename, "");
    }
}