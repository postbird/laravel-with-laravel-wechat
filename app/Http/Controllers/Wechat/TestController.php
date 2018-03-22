<?php

namespace App\Http\Controllers\Wechat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TestController extends Controller
{
    //
    // token认证及信息传输
    public function index(){
        Log::info('request arrived.');
        $app = app('wechat.official_account');
        return $app->server->serve();
    }
    // 获取jssdk配置
    public function getJSSDKConfig(Request $request){
        // dump(explode(',',$request->get('apis')));
        $arr = explode(',',$request->get('apis'));
        $debug = $request->get('debug') ==='true' ? true : false;
        $json = $request->get('json') ==='true' ? true : false;
        $url =$request->get('url');
//        dump($request->get('url'));
        // check
        if(!$url){
            return response()->json(['status'=>false,'msg'=>'params error','data'=>'']);
        }
        $app = app('wechat.official_account');
        $app->jssdk->setUrl($url);
        $config = $app->jssdk->buildConfig($arr,$debug,$json,$url);
        return response($config);
    }
}
