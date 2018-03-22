## 一、需求

因为很多自己开发的 H5 是在微信中进行传播的，因此使用 JSSDK 是必不可少的环节。

而 JSSDK 虽然只是个 js ，可是加密必须得在服务端进行。

因此面临一个很尴尬的问题：

> 每次写一个 H5，就必须写一个服务端进行加密请求验证。

为了解决这个问题，准备用一个域名所谓 H5 的基本容器，通过文件夹存放 H5 网页，并且在这个域名中，开发一个基本的 加密服务端框架，这样子所有在这个域名中的 H5 都能够正常的使用  jssdk 进行分享和传播。

## 二、基本思想

以 `http://xxx.ptbird.cn` 举例.（**这个域名需要在微信公众号上进行配置同时需要将其作为 js 安全域名，同时需要将服务器IP设置为白名单**）

使用 laravel 5.6 并且使用 [**laravel-wechat**](https://github.com/overtrue/laravel-wechat)

### 1、配置微信 token 验证的 url

使用的 url 是 `http://xxx.ptbird.cn/wechat`

### 2、配置一个基础 url 用于获取 jssdk 的 config 内容

使用的 url 是 `http://xxx.ptbird.cn/jssdkconfig`

### 3、H5 均直接写静态页面，并不使用 laravel 的 blade 模板去写H5

### 4、服务器 `documentRoot` 配置为 `public`

这是 laravel 的基本配置

### 5、H5 静态页面存放在 `public/h5/` 文件夹下

因此在访问 H5 的时候，需要访问域名 `http://xxx.ptbird.cn/h5/xxxx/`

因为 H5 主要是分享，而不是域名访问，因此这个域名没什么问题。

### 6、通过 ajax 获取 config 配置

在需要调用分享的页面上，进行 config 配置的时候，首先通过 ajax 将基本参数传送给服务端url（`http://xxx.ptbird.cn/jssdkconfig`）

一些需要的参数可能如下：（可以参照 EasyWechat 的文档）

- [https://www.easywechat.com/docs/master/zh-CN/basic-services/jssdk](https://www.easywechat.com/docs/master/zh-CN/basic-services/jssdk)

其中建议 `$app->jssdk->setUrl($url) 设置当前URL` 一定是自己前端的页面的 url

### 7、将获取到的 config 直接放在 js 中

## 三、实现

### 1、安装并配置 `laravel-wechat`

#### 安装

```bash
composer require "overtrue/laravel-wechat:~4.0"
```

### 配置：

如果是旧版本的，请参考文档的配置。 5.5 之后版本不需要手动注册 serviveProvider

创建配置文件：

```bash
php artisan vendor:publish --provider="Overtrue\LaravelWeChat\ServiceProvider"
```

<red> 完成之后，会在 config 中出现一个 `wechat.php` ，一些配置文件直接在这里面配置就行了</red>

### 2、实现服务器 token 验证逻辑

要实现服务器 token 验证，除了填写微信公众号的配置之外，还需要配置 `config/wechat.php`。

之后是需要对 `http://xxx.ptbird.cn/wechat` 进行编码完成验证。（这里不涉及消息，消息也在这里面转发或处理即可）。


路由我是直接配置如下：

```php
// 微信方面的路由
Route::namespace("Wechat")->group(function(){
    Route::any('/wechat','WechatController@index');
    Route::any('/jssdkconfig','WechatController@getJSSDKConfig');
});
```

因此你可以看到，需要一个 `Wechat/WechatController.php` 

```bash
php artisan make:controller Wechat/WechatController
```

其中 `index` 函数如下：

非常简单，这个完成之后，就能够实现微信公众号的 token 认证 （<red>注意，需要对 config/wechat 配置和公众号的相关参数匹配正确才行 </red>）

```php
// token认证及信息传输
    public function index(){
        Log::info('request arrived.');
        $app = app('wechat.official_account');
        return $app->server->serve();
    }
```
## 3、实现 jssdkConfig 信息获取的逻辑

`http://xxx.ptbird.cn/jssdkconfig` 这个路由对应的是 `getJSSDKConfig` 方法

```php
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
```

为了方便起见，我直接使用了 GET 的请求方式。

同时主要的参数有下面几个：

- apis：    <red>需要使用的接口列表 传入格式为 `"onMenuShareTimeline,onMenuShareAppMessage"` 这样的字符串，方便分割成数组</red>
- debug：传入的是字符的 true 或者 false
- json：传入的是字符的 true 或者 false （表示生成的数据格式，可以参考 easyWechat 的文档）
- url：需要加密的 url （传入的时候必须将 `&` 进行替换，否则会造成 config 失败 ）

## 4. js 获取 config 数据

在 H5 中直接使用 config 的时候，可以直接在 js 中通过 ajax 请求 url 获取config内容。

比如下面我使用的例子：

```javascript
var shareLinkUlr = location.href.split("#")[0]; // 获取当前的url 去掉 # 之后的部分
shareLinkUlr = shareLinkUlr.replace(/\&/g, '%26'); // 将 & 替换成 %26 
var shareImgUrl = 'http://xxx.ptbird.cn/h5/demo/img/xs.jpg'; // 分享的图片地址
// 获取 config 的内容
function getjssdkconfig(apis,debug,json,link){
    var xhr = new XMLHttpRequest();
    var url = 'http://xxx.ptbird.cn/jssdkconfig'; // 这个就是之前配置的路由
    var data = "apis="+apis+"&debug="+debug+"&json="+json+"&url="+link; // 拼接 get 参数
    xhr.open('GET',url+"?"+data);
    xhr.onreadystatechange = function(){
        if(xhr.readyState===4 && (xhr.status >=200 && xhr.status <=300)){
            // 获取 config 之后，进行一些操作
            // 需要进行 JSON.parse 获取对象
            configJsSDKAndDoSomething(JSON.parse(xhr.responseText));
        }
    };
    xhr.send();
}
// 获取config 之后进行的操作
// 因为是使用 ajax 进行config内容，这个方法是在上面运行的
function configJsSDKAndDoSomething(config){
    wx.config(config);
    wx.ready(function() {
        // 其他的一些操作
    });
    wx.error(function(error){
        console.log(error);
    });
    wx.onMenuShareTimeline({
        title: '', // 分享标题
        link: shareLinkUlr, // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
        imgUrl:shareImgUrl, // 分享图标
        success: function () {
            alert('分享成功');
        }
    });
    wx.onMenuShareAppMessage({
        title: '', // 分享标题
        desc: '', // 分享描述
        link: shareLinkUlr, // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
        imgUrl: shareImgUrl, // 分享图标
        type: 'link', // 分享类型,music、video或link，不填默认为link
        success: function () {
            
        },
        cancel: function () {
            
        }
    });
}
// 页面加载完之后进行操作
$(document).ready(function(){
    // 注意这里的参数
    // apis 使用的参数是 字符串的拼接 这个是和 php 的方法中的处理相对应的
    getjssdkconfig("onMenuShareTimeline,onMenuShareAppMessage",false,false,shareLinkUlr);
});
```

## 四、Github

之所以使用 laravel 一方面是我现在啥都用 laravel 去做， 另一方面也是为了之后做其他的微信公众号开发提供方便。

如果只是为 H5 分享做一个基础平台，则可以直接使用 EasyWechat

**github 地址：**

- [https://github.com/postbird/laravel-with-laravel-wechat](https://github.com/postbird/laravel-with-laravel-wechat)


## 五、关键的几个点：

### 1、JSON.parse()

在拿到返回的 json 数据之后，需要 `JSON.parse()`，否则是不正确的 js 对象，报 `config:fail` 错误

### 2、分享出去的链接点击之后配置失败

这个主要是因为分享出去之后，由于微信加了几个参数，导致存在问题。

网上存在解决方案就是 将 `&` 进行替换：

```javascript
shareLinkUlr = shareLinkUlr.replace(/\&/g, '%26'); // 将 & 替换成 %26 
```
















