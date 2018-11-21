<?php
/**
 * Created by PhpStorm.
 * User: chenliang
 * Date: 2018/11/21
 * Time: 下午4:33
 */

namespace App\Listeners;

use Illuminate\Database\Events\QueryExecuted;

class SqlListener {
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    /**
     * Handle the event.
     *
     * @param  =QueryExecuted  $event
     * @return void
     */
    public function handle(QueryExecuted $event) {

        $sql = str_replace("?", "'%s'", $event->sql);

        $log = vsprintf($sql, $event->bindings);

        $log = '[' . date('Y-m-d H:i:s') . '] ' . $log . "\r\n";
        $filepath = storage_path('sql.log');
        file_put_contents($filepath, $log, FILE_APPEND);

        // 这里也可以直接用Log::info() 里的函数，只是这样会和其他调试信息掺在一起。
        // 如果要用Log里的函数，别忘记了引入Log类。
    }

}
