<?php
/**
 * Created by PhpStorm.
 * User: chenliang
 * Date: 2018/11/22
 * Time: 下午4:30
 */

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;


class LoginController
{
    public function init(Request $req){
        $code = $req->input('code');
        if(!$code){
            outputToJson(ERROR,'预授权不存在');
        }
        $wx     = array2object(config('local.weixin'));
        $wxObj  = new WXBizDataCrypt($wx->appid, $wx->secret);
        $res    = $wxObj->code2Session($code);
        $openid     = $res->openid;
        $sessionKey = $res->session_key;

    }
}