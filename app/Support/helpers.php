<?php
/**
 * Created by PhpStorm.
 * User: chenliang
 * Date: 2018/11/21
 * Time: 下午2:39
 */

function array2object($arr)
{
    $json = json_encode($arr);
    return json_decode($json, false);
}