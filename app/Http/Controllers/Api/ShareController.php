<?php
/**
 * Created by PhpStorm.
 * User: chenliang
 * Date: 2018/11/20
 * Time: 下午11:47
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use OSS\OssClient;

class ShareController extends Controller
{
    public function info(){
	phpinfo();
   }

    public function index(){
        $http_type  = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $host       = $http_type . $_SERVER['HTTP_HOST'];
        $url        = $host.'/php/img.php';
        $img        = self::makeImg($url);
        return json_encode(['code'=>200,'msg'=>'获取成功','data'=>['img'=>$host.$img]]);
    }

    /**
     *
     * @param $weburl   string  url地址
     * @return string
     */
    static function makeImg($weburl){
	$path = @exec('pwd');
        $phantomJs   = $path.'/phantomjs/bin/phantomjs ';
        $saveurl = "/img/".time().rand(1000,9999).'.png';
        $command = $phantomJs.' '." {$path}/js/snap.js  '".$weburl."' ".$path.$saveurl;
        @exec($command);
        return self::toUpload($path.$saveurl);
//        return $saveurl;
    }

    /**
     * 执行上传oss
     * @param $file_path
     * @return bool|string
     * @throws \OSS\Core\OssException
     */
    static function toUpload($file_path){
        $file_name = time().'_'.rand(10000,99999).'.png';
        # 读取配置
        $oss    = array2object(config('local.oss'));
        # 创建oss链接
        $ossClient = new OssClient($oss->accessKeyId, $oss->accessKeySecret, $oss->endpoint);
        # 开始上传 bucket、 oss路径+名称 、本地临时路径
        $result = $ossClient->uploadFile($oss->bucket, 'movie/'.$file_name,$file_path );
        if(!isset($result['oss-request-url'])){
            return false;
        }
        unlink($file_path);
        return $oss->url.'movie/'.$file_name;
    }
}
