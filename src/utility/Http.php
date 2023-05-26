<?php


namespace BaiMuZe\Admin\utility;

/**
 * Http 请求类
 */
class Http
{
    /**
     * 以 GET 模拟网络请求
     * @param string $location HTTP请求地址
     * @param array|string $data GET请求参数
     * @param array $options CURL请求参数
     * @return boolean|string
     */
    public static function get(string $location, $data = [], array $options = [])
    {
        $options['query'] = $data;
        return static::request('get', $location, $options);
    }

    /**
     * 以 POST 模拟网络请求
     * @param string $location HTTP请求地址
     * @param array|string $data POST请求数据
     * @param array $options CURL请求参数
     * @return boolean|string
     */
    public static function post(string $location, $data = [], array $options = [])
    {
        $options['data'] = $data;
        return static::request('post', $location, $options);
    }

    /**
     * 以 FormData 模拟网络请求
     * @param string $url 模拟请求地址
     * @param array $data 模拟请求参数数据
     * @param array $file 提交文件 [field,name,content]
     * @param array $header 请求头部信息，默认带 Content-type
     * @param string $method 模拟请求的方式 [GET,POST,PUT]
     * @param boolean $returnHeader 是否返回头部信息
     * @return boolean|string
     */
    public static function submit(string $url, array $data = [], array $file = [], array $header = [], string $method = 'POST', bool $returnHeader = true)
    {
        [$line, $boundary] = [[], Random::uniqidNumber(18)];
        foreach ($data as $key => $value) {
            $line[] = "--{$boundary}";
            $line[] = "Content-Disposition: form-data; name=\"{$key}\"";
            $line[] = "";
            $line[] = $value;
        }
        if (is_array($file) && isset($file['field']) && isset($file['name'])) {
            $line[] = "--{$boundary}";
            $line[] = "Content-Disposition: form-data; name=\"{$file['field']}\"; filename=\"{$file['name']}\"";
            $line[] = "";
            $line[] = $file['content'];
        }
        $line[] = "--{$boundary}--";
        $header[] = "Content-type:multipart/form-data;boundary={$boundary}";
        return static::request($method, $url, ['data' => join("\r\n", $line), 'returnHeader' => $returnHeader, 'headers' => $header]);
    }

    /**
     * 以 CURL 模拟网络请求
     * @param string $method 模拟请求方式
     * @param string $location 模拟请求地址
     * @param array $options 请求参数[headers,query,data,cookie,cookie_file,timeout,returnHeader]
     * @return boolean|string
     */
    public static function request(string $method, string $location, array $options = [])
    {
        // GET 参数设置
        if (!empty($options['query'])) {
            $location .= strpos($location, '?') !== false ? '&' : '?';
            if (is_array($options['query'])) {
                $location .= http_build_query($options['query']);
            } elseif (is_string($options['query'])) {
                $location .= $options['query'];
            }
        }
        $curl = curl_init();
        // Agent 代理设置
        curl_setopt($curl, CURLOPT_USERAGENT, static::getUserAgent());
        // Cookie 信息设置
        if (!empty($options['cookie'])) {
            curl_setopt($curl, CURLOPT_COOKIE, $options['cookie']);
        }
        // Header 头信息设置
        if (!empty($options['headers'])) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $options['headers']);
        }
        if (!empty($options['cookie_file'])) {
            curl_setopt($curl, CURLOPT_COOKIEJAR, $options['cookie_file']);
            curl_setopt($curl, CURLOPT_COOKIEFILE, $options['cookie_file']);
        }
        // 设置请求方式
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        if (strtolower($method) === 'head') {
            curl_setopt($curl, CURLOPT_NOBODY, 1);
        } elseif (isset($options['data'])) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $options['data']);
        }
        // 请求超时设置
        if (isset($options['timeout']) && is_numeric($options['timeout'])) {
            curl_setopt($curl, CURLOPT_TIMEOUT, $options['timeout']);
        } else {
            curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        }
        // 是否返回前部内容
        if (empty($options['returnHeader'])) {
            curl_setopt($curl, CURLOPT_HEADER, false);
        } else {
            curl_setopt($curl, CURLOPT_HEADER, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        }
        curl_setopt($curl, CURLOPT_URL, $location);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $content = curl_exec($curl);
        curl_close($curl);
        return $content;
    }

    /**
     * 获取浏览器代理信息
     * @return string
     */
    private static function getUserAgent(): string
    {
        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            return $_SERVER['HTTP_USER_AGENT'];
        }
        $agents = [
            "Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
            "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.57 Safari/536.11",
            "Mozilla/5.0 (Windows NT 10.0; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0",
            "Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; .NET4.0C; .NET4.0E; .NET CLR 2.0.50727; .NET CLR 3.0.30729; .NET CLR 3.5.30729; InfoPath.3; rv:11.0) like Gecko",
            "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50",
            "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)",
            "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_0) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11",
        ];
        return $agents[array_rand($agents)];
    }

    /**
     * 异步发送一个请求
     * @param string $url 请求的链接
     * @param mixed $params 请求的参数
     * @param string $method 请求的方法
     * @return boolean TRUE
     */
    public static function sendAsyncRequest($url, $params = [], $method = 'POST')
    {
        $method = strtoupper($method);
        $method = $method == 'POST' ? 'POST' : 'GET';
        //构造传递的参数
        if (is_array($params)) {
            $post_params = [];
            foreach ($params as $k => &$v) {
                if (is_array($v)) {
                    $v = implode(',', $v);
                }
                $post_params[] = $k . '=' . urlencode($v);
            }
            $post_string = implode('&', $post_params);
        } else {
            $post_string = $params;
        }
        $parts = parse_url($url);
        //构造查询的参数
        if ($method == 'GET' && $post_string) {
            $parts['query'] = isset($parts['query']) ? $parts['query'] . '&' . $post_string : $post_string;
            $post_string = '';
        }
        $parts['query'] = isset($parts['query']) && $parts['query'] ? '?' . $parts['query'] : '';
        //发送socket请求,获得连接句柄
        $fp = fsockopen($parts['host'], isset($parts['port']) ? $parts['port'] : 80, $errno, $errstr, 3);
        if (!$fp) {
            return false;
        }
        //设置超时时间
        stream_set_timeout($fp, 3);
        $out = "{$method} {$parts['path']}{$parts['query']} HTTP/1.1\r\n";
        $out .= "Host: {$parts['host']}\r\n";
        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out .= "Content-Length: " . strlen($post_string) . "\r\n";
        $out .= "Connection: Close\r\n\r\n";
        if ($post_string !== '') {
            $out .= $post_string;
        }
        fwrite($fp, $out);
        //不用关心服务器返回结果
        //echo fread($fp, 1024);
        fclose($fp);
        return true;
    }

    /**
     * 发送文件到客户端
     * @param string $file
     * @param bool $delaftersend
     * @param bool $exitaftersend
     */
    public static function sendToBrowser($file, $delaftersend = true, $exitaftersend = true)
    {
        if (file_exists($file) && is_readable($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment;filename = ' . basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check = 0, pre-check = 0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);
            if ($delaftersend) {
                unlink($file);
            }
            if ($exitaftersend) {
                exit;
            }
        }
    }
}