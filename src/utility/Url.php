<?php


namespace BaiMuZe\Admin\utility;


use BaiMuZe\Admin\library\AppServer;

class Url
{
    /**
     * 生成最短URL地址
     * @param string $url 路由地址
     * @param array $vars PATH 变量
     * @param boolean|string $suffix 后缀
     * @param boolean|string $domain 域名
     * @return string
     */
    public static function shortUrl(string $url = '', array $vars = [], $suffix = true, $domain = false): string
    {
        // 读取默认节点配置
        $app = AppServer::$sapp->config->get('route.default_app') ?: 'index';
        $ext = AppServer::$sapp->config->get('route.url_html_suffix') ?: 'php';
        $act = Str::lower(AppServer::$sapp->config->get('route.default_action') ?: 'index');
        $ctr = Str::snake(AppServer::$sapp->config->get('route.default_controller') ?: 'index');
        // 生成完整链接地址
        $pre = AppServer::$sapp->route->buildUrl('@')->suffix(false)->domain($domain)->build();
        $uri = AppServer::$sapp->route->buildUrl($url, $vars)->suffix($suffix)->domain($domain)->build();
        // 替换省略链接路径
        return preg_replace([
            "#^({$pre}){$app}/{$ctr}/{$act}(\.{$ext}|^\w|\?|$)?#i",
            "#^({$pre}[\w.]+)/{$ctr}/{$act}(\.{$ext}|^\w|\?|$)#i",
            "#^({$pre}[\w.]+)(/[\w.]+)/{$act}(\.{$ext}|^\w|\?|$)#i",
            "#/\.{$ext}$#i",
        ], ['$1$2', '$1$2', '$1$2$3', ''], $uri);
    }

    /**
     * 获取当前完整的请求地址
     * @return string
     */
    public function full()
    {
        return app('request')->domain() . app('request')->url();
    }

    /**
     * 生成一个完整的网址链接
     * @param string $path 如果不是完整的网址，则格式为 控制器/操作，如果跨域，则开头需要使用@模块/控制器/操作
     * @param bool $is_full 是否返回完整的连接以及解决跨域，
     * @return string
     */
    public function make($path = '', $is_full = 0)
    {
        if ($this->isValidUrl($path)) {
            return $path;
        }
        $is_allow = 0; //是否跨域
        //如果以./开头，则定位到当前分组根目录
        if (substr($path, 0, 2) == './') {
            $path = '@' . app('router.group') . substr($path, 1);
        }
        //如果以@开头，则证明为跨域，跨域需要完整的模块/控制器/操作
        if (substr($path, 0, 1) == '@') {
            $path = substr($path, 1);
            $is_allow = 1;
        } else {
//            if(app('router.group')!==app('router.module')){
//                $path=app('router.module').'/'.$path;
//            }
        }
        $root = $is_allow == 1 ? $this->full() : app('request')->root();
        $base = app('request')->baseUrl();
        $path = ltrim($path, '/');
        if (empty($path) && $is_allow == 1) {
            return $is_full == 1 ? $root : '/';
        }
        if (empty($url)) {
            return $is_full == 1 ? ($root) : ($base);
        } else {
//            return $is_full==1?($root.'/'.$url):($base.'/'.$url);
        }
    }

    /**
     * 判断是否为一个有效的链接地址
     * @param string $path
     * @return bool
     */
    public function isValidUrl($path)
    {
        foreach (array('#', '//', 'mailto:', 'tel:') as $val) {
            if ($val != '' && strpos($path, $val) === 0)
                return true;
        }
        return filter_var($path, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * 获取请求类型
     * @author 白沐泽
     */
    public static function visitType()
    {
        // 判断是否是微信浏览器
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return 1;
        }
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])) return 2;
        //此条摘自TPM智能切换模板引擎，适合TPM开发
        if (isset ($_SERVER['HTTP_CLIENT']) && 'PhoneClient' == $_SERVER['HTTP_CLIENT']) return 2;
        //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset ($_SERVER['HTTP_VIA']))
            //找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
        //判断手机发送的客户端标志,兼容性有待提高
        if (isset ($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array(
                'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile'
            );
            //从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return 2;
            }
        }
        //协议法，因为有可能不准确，放到最后判断
        if (isset ($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return 2;
            }

        }

        return 0;
    }
}