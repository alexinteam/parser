<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

include_once dirname(__DIR__, 3) . DIRECTORY_SEPARATOR. 'User.php';

class HealthCheckController extends Controller {

    /**
     * Хелсчек
     *
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function healthCheck() {
        return response('');
    }

    /**
     * Рединессчек
     *
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function readinessCheck() {
        try {
            DB::statement('SET SESSION wait_timeout=5');
            $result = DB::select('SELECT 1');
            if(isset($result[0]) && ($result[0]->{'1'} === 1)) {
                return response('');
            }
        }catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'status' => 500], 500);
        }
        return response()->json(['error' => 'Mysql returned wrong healthcheck response', 'status' => 500], 500);
    }

}