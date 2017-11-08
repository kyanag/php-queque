<?php
namespace  Kyanag\FileQueue;
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

function dump($file, $line, $obj){
    echo sprintf("file:%s;line:%s\n", $file, $line);
    var_dump($obj);
}

/**
 * 字节转int类型
 * @param $str
 * @return int
 */
function binaryToUInt($str){
    $len = count($str);
    if($len > 4){
        return -1;
    }else{
        $str .= str_repeat("\0", 4-$len);
        return unpack("I", $str)[1];
    }
}

function numberToAsciiArr($number){
    $arr = array();
    while($number > 256){
        $asc = $number % 256;
        $number = $number/256;
        $arr[] = $asc;
    }
    $arr[] = $number;
    return $arr;
}

function numberToBinary($number, $need_len = null){
    $str = "";
    $i = 0;
    do{
        $asc = $number % 256;
        $number = intval($number/256);
        $str .= chr($asc);
        $i++;
        if($need_len !== null && $i >= $need_len){
            break;
        }
    }while($number != 0);
    $str .= str_repeat("\0", $need_len - strlen($str));
    return $str;
}

/**
 * 清空/新建一个
 * @param $filename string 文件名
 * @param int $size int 文件大小
 */
function resetFile($filename){
    if(!is_file($filename)){
        fclose(fopen($filename, "w+"));
    }else{
        file_put_contents($filename, "");
    }
}