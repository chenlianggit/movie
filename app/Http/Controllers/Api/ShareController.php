<?php
/**
 * Created by PhpStorm.
 * User: chenliang
 * Date: 2018/11/20
 * Time: 下午11:47
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\WxAccessToken;
use OSS\OssClient;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    public $host        = '';
    public $http_type   = '';

    public function __construct()
    {
        $this->http_type    = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $this->host         = $this->http_type . $_SERVER['HTTP_HOST'].'/';

    }

    public function info(){
        phpinfo();
    }

    public function getImg(Request $req){

        $id     = $req->input('id',13743);   # 默认仙剑 影片ID
        $name   = $req->input('name','');    # 名称
        $des    = $req->input('des','');     # 简介
        $actor  = $req->input('actor','');   # 演员
        $area  = $req->input('area','');     # 地区
        $lang  = $req->input('lang','');     # 语言
        $year  = $req->input('year','');     # 年份

        $path   = "pages/index/index?sid={$id}";
        $qrcode = self::_getQrcode($path,350,false);
        $data   = [
            'code'  => $this->host.$qrcode,
            'name'  => $name,
            'des'   => $des,
            'actor' => $actor,
            'area'  => $area,
            'lang'  => $area,
            'year'  => $year,
            'photo' => $req->input('photo',''),

        ];

        $url        = $this->host.'php/img.php?'.self::_urlencode($data);
        $img        = self::makeImg($url);
        return json_encode(['code'=>200,'msg'=>'获取成功','data'=>['img'=>$img]]);
    }

    public function getQrcode(Request $req){
        $id     = $req->input('id',13743); #仙剑1 ID
        $path   = "pages/video-show/video-show?id={$id}";
        $qrcode = self::_getQrcode($path,350,false);
        outputToJson(OK, '获取成功', ['img'=>$this->host.$qrcode]);
    }


    /**
     * url地址
     * @param $weburl string url地址
     * @return bool|string
     * @throws \OSS\Core\OssException
     */
    static function makeImg($weburl){
	    $path       = @exec('pwd');
        $phantomJs  = $path.'/phantomjs/bin/phantomjs ';
        $saveurl    = "/img/".time().rand(1000,9999).'.png';
        $command    = $phantomJs.' '." {$path}/js/snap.js  '".$weburl."' ".$path.$saveurl;
        @exec($command);
        return self::toUpload($path.$saveurl);
    }

    /**
     * 执行上传oss
     * @param $file_path
     * @return bool|string
     * @throws \OSS\Core\OssException
     */
    public static function toUpload($file_path){
        $file_name = 'movie/'.time().'_'.rand(10000,99999).'.png';
        # 读取配置
        $oss        = array2object(config('local.oss'));
        # 创建oss链接
        $ossClient  = new OssClient($oss->accessKeyId, $oss->accessKeySecret, $oss->endpoint);
        # 开始上传 (bucket、 oss路径+名称 、本地临时路径)
        $result     = $ossClient->uploadFile($oss->bucket, $file_name,$file_path );
        if(!isset($result['oss-request-url'])){
            return false;
        }
        unlink($file_path);
        return $oss->url.$file_name;
    }

    # 获取最新的access_token
    public static function _getNewAccessToken()
    {
        $res    = WxAccessToken::find(1);
        # 检查是否过期 2个小时
        $bool   = (int)( (time() - (int)$res->update_time) > 6500);
        if (!$res || $bool) {
            $res->access_token  = self::_getAccessToken();
            $res->update_time   = (int)time();
            $res->save();
        }
        return $res->access_token;
    }

    # 从微信获取access_token
    public static function _getAccessToken()
    {
        $weixin = array2object(config('local.weixin'));
        $url    = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $weixin->appid . '&secret=' . $weixin->secret;
        $html   = file_get_contents($url);
        $output = json_decode($html, true);
        return $output['access_token'];
    }


    /**
     * 公共生成分享图
     * @param $path
     * @param int $width
     * @param bool $is_hyaline
     * @return bool|string
     */
    public  static function _getQrcode($path, $width=355, $is_hyaline=true){
        $access_token = self::_getNewAccessToken();
        if(!$access_token){
            return false;
        }
        $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$access_token;
        $data = [
//            'path'       => 'pages/index/index?a=123',  // 小程序路径 不能为空，最大长度 128 字节
            'path'       => $path,  // 小程序路径 不能为空，最大长度 128 字节
            'width'      => $width,  // 二维码的宽度
            'auto_color' => false,  // 自动配置线条颜色，如果颜色依然是黑色，则说明不建议配置主色调
            'line_color' => ['r' => 0,'g' => 0,'b' => 0,], //auth_color 为 false 时生效，使用 rgb 设置颜色 例如 {"r":"xxx","g":"xxx","b":"xxx"},十进制表示
            'is_hyaline' => $is_hyaline,  // 是否透明
        ];
        $res = CommandController::send_post($url,$data);
        $imageName = "img/".time().rand(1000,9999).'.png';
        $r = file_put_contents( $imageName, $res);//返回的是字节数
        if (!$r) {
            return false;
        }
//        $codeImg = self::toUpload($imageName);
        $codeImg = $imageName;
        return $codeImg;
    }


    /**
     * 数组转URL格式
     * @param $data array
     * @return string
     */
    public static function _urlencode($data){
        $dataStr = '';
        foreach($data as $key=>$val){
            $dataStr .= '&'.$key.'='.urlencode($val);
        }
        $dataStr    = ltrim($dataStr,'&');
        return $dataStr;
    }
}
