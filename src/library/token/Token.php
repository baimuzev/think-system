<?php


namespace BaiMuZe\Admin\library\token;


use think\facade\Config;
use think\facade\Log;

/**
 * 用户token操作
 * @author baimuze
 */
class Token
{
    /**
     * @var array Token的实例
     */
    public static $instance = [];

    /**
     * @var object 操作句柄
     */
    public static $handler;

    /**
     * 连接Token驱动
     * @access public
     * @param array $options 配置数组
     * @param bool|string $name Token连接标识 true 强制重新连接
     */
    public static function content($options, $name = false)
    {
        $type = !empty($options['type']) ? $options['type'] : 'File';
        if (false === $name) {
            $name = md5(serialize($options));
        }
        if (true === $name || !isset(self::$instance[$name])) {
            $class = false === strpos($type, '\\') ?
                '\\BaiMuZe\\library\\token\\dirver\\' . ucwords($type) :
                $type;
            //登记初始化信息
            Log::record('[ TOKEN ] INIT ' . $type, 'info');
            if (true === $name) {
                return new $class($options);
            }
            self::$instance[$name] = new $class($options);
        }
        return self::$instance[$name];
    }

    /**
     * 自动初始化token
     * @param array $options 配置数组
     * @author baimuze
     */
    public static function init(array $options = [])
    {
        if (is_null(self::$handler)) {
            if (empty($options) && 'complex' == Config::get('base.token.type')) {
                $default = Config::get('base.token.default');
                // 获取默认Token配置，并连接
                $options = Config::get('base.token.' . $default['type']) ?: $default;
            } elseif (empty($options)) {
                $options = Config::get('base.token');
            }
            self::$handler = self::content($options);
        }
        return self::$handler;
    }

    /**
     * 判断Token是否可用(check别名)
     * @access public
     * @param string $token Token标识
     * @param int $user_id 会员ID
     * @return bool
     */
    public static function has($token, $user_id)
    {
        return self::check($token, $user_id);
    }

    /**
     * 判断Token是否可用
     * @param string $token Token标识
     * @param int $user_id 会员ID
     * @return bool
     */
    public static function check($token, $user_id)
    {
        return self::init()->check($token, $user_id);
    }

    /**
     * 读取Token
     * @access public
     * @param string $token Token标识
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function get($token, $default = false)
    {
        return self::init()->get($token) ?: $default;
    }

    /**
     * 写入Token
     * @access public
     * @param string $token Token标识
     * @param mixed $user_id 会员ID
     * @param int|null $expire 有效时间 0为永久
     * @param string $model 登录类型
     * @return boolean
     */
    public static function set($token, $user_id, $expire = null, $model = 'admin')
    {
        return self::init()->set($token, $user_id, $expire, $model);
    }

    /**
     * 删除Token(delete别名)
     * @access public
     * @param string $token Token标识
     * @return boolean
     */
    public static function rm($token)
    {
        return self::delete($token);
    }

    /**
     * 删除Token
     * @param string $token 标签名
     * @return bool
     */
    public static function delete($token)
    {
        return self::init()->delete($token);
    }

    /**
     * 清除Token
     * @access public
     * @param int user_id 会员ID
     * @return boolean
     */
    public static function clear($user_id = null)
    {
        return self::init()->clear($user_id);
    }

    /**
     * 获取加密的token
     * @param $token 未加密的token
     * @author 白沐泽
     */
    public static function getEncryptedToken($token)
    {
        return self::EncryptedToken($token);
    }

    /**
     * 获取加密后的Token
     * @param string $token Token标识
     * @return string
     */
    public static function EncryptedToken($token)
    {
        $config = Config::get('base.token');
        return hash_hmac($config['hashalgo'], $token, $config['key']);
    }

}