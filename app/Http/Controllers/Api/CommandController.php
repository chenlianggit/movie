<?php
/**
 * Created by PhpStorm.
 * User: chenliang
 * Date: 2018/11/21
 * Time: 下午5:23
 */

namespace App\Http\Controllers\Api;


class CommandController
{
    public static function send_post( $url, $post_data ) {
        $options = array(
            'http' => array(
                'method'  => 'POST',
                'header'  => 'Content-type:application/json',
                //header 需要设置为 JSON
                'content' => json_encode($post_data),
                'timeout' => 60
                //超时时间
            )
        );
        $context = stream_context_create( $options );
        $result = file_get_contents( $url, false, $context );

        return $result;
    }

    public static function send_get( $url) {
        $options = array(
            'http' => array(
                'method'  => 'GET',
                'header'  => 'application/x-www-form-urlencoded; charset=UTF-8',
                'timeout' => 60
                //超时时间
            )
        );
        $context = stream_context_create( $options );
        $result = file_get_contents( $url, false, $context );

        return $result;
    }
}