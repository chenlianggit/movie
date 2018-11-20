<?php
/**
 * Created by PhpStorm.
 * User: chenliang
 * Date: 2018/11/20
 * Time: 下午11:47
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;

class ShareController extends Controller
{
    public function index(){
        $http_type  = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $host       = $http_type . $_SERVER['HTTP_HOST'];
        $url        = $host.'/img.php';
        $img        = self::makeImg($url);
        return json_encode(['code'=>200,'msg'=>'获取成功','data'=>['img'=>$host.$img]]);
    }

    /**
     *
     * @param $weburl   string  url地址
     * @return string
     */
    static function makeImg($weburl){
        $phantomJs   = @exec("pwd").'/phantomjs/bin/phantomjs ';
        $saveurl = "/img/".time().rand(1000,9999).'.png';
        $command = $phantomJs.' '." Public/js/snap.js  '".$weburl."' ".$saveurl;
        @exec($command);
//        return self::toUpload($saveurl);
        return $saveurl;
    }
}