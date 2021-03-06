<?php
/**
 * Created by PhpStorm.
 * User: chenliang
 * Date: 2018/11/20
 * Time: 下午11:47
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Img;
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

    /**
     * 获取分享图
     * @param Request $req
     * @return string
     * @throws \OSS\Core\OssException
     */
    public function getImg(Request $req){

        $id     = $req->input('id',13743);   # 默认仙剑 影片ID
        $name   = $req->input('name','');    # 名称

        $info = Img::firstOrCreate(['v_id'=>$id],['name'=>$name]);

        if($info->share_img){
            outputToJson(OK, '获取成功',['img'=>$info->share_img]);
        }
        # 二维码从数据库取
        if(!$info->qrcode_img){
            $path   = "pages/index/index?sid={$id}";
            $info->qrcode_img = self::_getQrcode($path,350,false);
            $info->save();
        }
        # 豆瓣评论从数据库取
        if(!$info->douban_img){
            $info->douban_img = self::getDouBanImg($name);
            $info->save();
        }

        $data   = [
            'code'  => $this->host.$info->qrcode_img,
            'name'  => $name,
            'img'   => $info->douban_img,
        ];

        $url    = $this->host.'php/img.php?'.self::_urlencode($data);
        $info->share_img    = self::makeImg($url);
        $info->save();
        outputToJson(OK, '获取成功',['img'=>$info->share_img]);
    }

    /**
     * 获取二维码
     * @param Request $req
     */
    public function getQrcode(Request $req){
        $id     = $req->input('id',13743); #仙剑1 ID
        $path   = "pages/video-show/video-show?id={$id}";
        $qrcode = self::_getQrcode($path,350,false);
        outputToJson(OK, '获取成功', ['img'=>$this->host.$qrcode]);
    }

    public function getDouban(Request $req){
        $name = $req->input('name');
        echo self::getDouBanImg($name);
    }
    /**
     * 获取豆瓣评分图
     * @param $name
     * @param int $id
     * @return bool|string
     */
    public static function getDouBanImg($name , $id = 0){
        if(!$name){
            return '';
        }
        $name = urlencode($name);
        $url = "http://103.80.24.117:6789/main?name={$name}";
        try{
            $img = CommandController::send_get($url);
        }catch (\ErrorException $e){
            return '';
        }
        if(preg_match('/.*(\.png)$/', $img)){
            return $img;
        }
        return '';
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


    /**
     * 影评
     */
    public function getMessage(){
        $data = [
            '返回等待成功',
            '爬取4万评论',
            '服务器压力大',
            '耐心等待15秒',
            '影评生成img',
            '我是渣渣辉',
            '豆瓣8万评',
            '一朝生成图',
            '在线生成中..',
            '服务器开小差',
            '分享好友乐开怀',
            '全部评论成精华',
            '学会寻找影片',
            '依靠搜索变强大',
            '学会分享破单身',
            '异步生成',
            '不等待先看电影',
            '看会电影再拿图',
            '15秒后生成好',
            '千万由着性子来',
            '十一点钟要早睡',
            '大概需要15秒',
            '看到这条就要好',
            '嘿嘿上当了不是',
            '北京天气数第一',
            '早饭不吃傻傻滴',
            '少年不知勤学早',
            '爱爱爱爱备忘录',
            '听君一席话',
            '少读十年书',
            '返回他也生成',
            '一会再来点就行',
        ];
        outputToJson(OK,'success',$data);
    }
}
