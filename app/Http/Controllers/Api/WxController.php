<?php
/**
 * Created by PhpStorm.
 * User: chenliang
 * Date: 2018/11/22
 * Time: 下午4:14
 */

namespace App\Http\Controllers\Api;


class WxController
{

}
class WXBizDataCrypt
{
    private $appid;
    private $sessionKey;

    public static $OK = 0;
    public static $IllegalAesKey = -41001;
    public static $IllegalIv = -41002;
    public static $IllegalBuffer = -41003;
    public static $DecodeBase64Error = -41004;

    /**
     * 构造函数
     * @param $sessionKey string 用户在小程序登录后获取的会话密钥
     * @param $appid string 小程序的appid
     */
    public function __construct($appid, $secret='' , $sessionKey = '')
    {
        $this->sessionKey   = $sessionKey;
        $this->appid        = $appid;
        $this->secret       = $secret;
    }


    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function decryptData( $encryptedData, $iv, &$data )
    {
        if (strlen($this->sessionKey) != 24) {
            return self::$IllegalAesKey;
        }
        $aesKey=base64_decode($this->sessionKey);


        if (strlen($iv) != 24) {
            return self::$IllegalIv;
        }
        $aesIV=base64_decode($iv);

        $aesCipher=base64_decode($encryptedData);

        $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj=json_decode( $result );
        if( $dataObj  == NULL )
        {
            return self::$IllegalBuffer;
        }
        if( $dataObj->watermark->appid != $this->appid )
        {
            return self::$IllegalBuffer;
        }
        $data = $result;
        return self::$OK;
    }

    /**
     * 根据 code 获取 openid 和 session_key
     * @param $code string 用户code
     * @return bool|Object json
     */
    public function code2Session($code){
        $url        = "https://api.weixin.qq.com/sns/jscode2session?appid={$this->appid}&secret={$this->secret}&js_code={$code}&grant_type=authorization_code";
        $response   = file_get_contents($url);
        $res        = json_decode($response);
        if($res->openid){
            return $res;
        }
        return false;
    }

}