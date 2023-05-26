<?php


namespace BaiMuZe\Admin\library;

use BaiMuZe\Admin\core\Controller;
use BaiMuZe\Admin\model\Account;
use BaiMuZe\Admin\model\Admin;
use BaiMuZe\Admin\model\AdminAuth;
use BaiMuZe\Admin\utility\Random;
use think\Container;
use think\facade\Cookie;
use think\facade\Session;
use think\Request;

/**
 * 后台权限管理类
 * @author 白沐泽
 * @createat 2022-12-06
 */
class AdminServer
{
    protected $class;

    /**
     * 初始化
     */
    public function __construct(Controller $class)
    {
        $this->class = $class;
    }

    public static function instance()
    {
        return Container::getInstance()->invokeClass(static::class);
    }

    /**
     * session存储信息
     * @param string $token
     * @author 白沐泽
     */
//    public function setSession($token)
//    {
//        if ($this->class->account->isLogin()) {
//            return true;
//        } else {
//            $info = $this->class->account->getUserinfo();
//            $admin = Admin::mk()->where('id', $info['uid'])->find()->toArray();
//            $info = array_merge($info, $admin);
//            unset($info['password']);
//            unset($info['salt']);
//            Session::set("admin", $info);
//            Cookie::set("token", $token);
//        }
//    }

    /**
     * 是否登录
     * @author 白沐泽
     */
    public function isLogin()
    {
        $admin = Session::get('admin');
        if (!$admin) {
            return false;
        }

    }
//
//    /**
//     * 当前请求实例
//     * @var Request
//     */
//    protected $request;
//    protected $_error = '';
//    protected $logined = false; //登录状态
//
//    /**
//     * 管理员登录
//     *
//     * @param string $username 用户名
//     * @param string $password 密码
//     * @param int $keeptime 有效时长
//     * @return  boolean
//     */
//    public function login($username, $password, $keeptime = 0)
//    {
//        $res = AppServer::$sapp->db->transaction(function () use ($username, $password, $keeptime) {
//            $admin = (new Admin())->where(['username' => $username])->find();
//            if (!$admin) {
//                $this->setError('no_account');
//                return false;
//            }
//            if ($admin['status'] == 1) {
//                $this->setError('status_account');
//                return false;
//            }
//            $account = Account::mk()->where('id', $admin->id)->find();
//            if (is_null($account)) {
//                $this->setError('no_account');
//                return false;
//            }
//            if (config('base.admin.login_failure_retry') && $account->loginfailure >= 10 && time() - $account->update_time < 86400) {
//                $this->setError('Please try again after 1 day');
//                return false;
//            }
//            if ($account->password != md5(md5($password) . $account->salt)) {
//                $account->loginfailure++;
//                $account->save();
//                $this->setError('Password is incorrect');
//                return false;
//            }
//            $account->loginfailure = 0;
//            $account->logintime = time();
//            $account->loginip = request()->ip();
//            $account->save();
//            if ($account['model'] === 'admin') {//后台缓存管理员信息
//                Session::set("admin", $admin->toArray());
//                Session::set("account", $account->toArray());
//                $this->keeplogin($keeptime);
//            }
//            return true;
//        });
//        return $res;
//    }
//
//    /**
//     * 退出登录
//     * @param $admin 管理员id
//     */
//    public function logout($admin)
//    {
//        $admin = (new Admin())->find(intval($admin));
//        if ($admin) {
//            $admin->token = '';
//            $admin->save();
//        }
//        $this->logined = false; //重置登录状态
//        Session::delete("admin");
//        Session::delete("account");
//        Cookie::delete("keeplogin");
//        return true;
//    }
//
//    /**
//     * 自动登录
//     * @return boolean
//     */
//    public function autologin()
//    {
//        $keeplogin = Cookie::get('keeplogin');
//        if (!$keeplogin) {
//            return false;
//        }
//        list($id, $keeptime, $expiretime, $key) = explode('|', $keeplogin);
//        if ($id && $keeptime && $expiretime && $key && $expiretime > time()) {
//            $admin = (new Admin())->find($id);
//            if (!$admin || !$admin->token) {
//                return false;
//            }
//            //token有变更
//            if ($key != md5(md5($id) . md5($keeptime) . md5($expiretime) . $admin->token . config('token.key'))) {
//                return false;
//            }
//            $ip = request()->ip();
//            //IP有变动
//            if ($admin->loginip != $ip) {
//                return false;
//            }
//            Session::set("admin", $admin->toArray());
//            //刷新自动登录的时效
//            $this->keeplogin($keeptime);
//            return true;
//        } else {
//            return false;
//        }
//    }
//
//    /**
//     * 刷新保持登录的Cookie
//     *
//     * @param int $keeptime
//     * @return  boolean
//     */
//    protected function keeplogin($keeptime = 0)
//    {
//        if ($keeptime) {
//            $expiretime = time() + $keeptime;
//            $key = md5(md5($this->id) . md5($keeptime) . md5($expiretime) . $this->token . config('token.key'));
//            $data = [$this->id, $keeptime, $expiretime, $key];
//            Cookie::set('keeplogin', implode('|', $data), 86400 * 7);
//            return true;
//        }
//        return false;
//    }
//
//    /**
//     * 检测是否登录
//     *
//     * @return boolean
//     */
//    public function isLogin()
//    {
//        if ($this->logined) {
//            return true;
//        }
//        $admin = Session::get('admin');
//        if (!$admin) {
//            return false;
//        }
////        if (AccountServer::instance()->init($admin['token'])) {//根据token初始化
////
////        } else {
////            return false;
////        }
////        var_dump();
////        $account = Session::get('account');
////        if (!$account) {
////            return false;
////        }
////        $my = (new Admin())->find($admin['id']);
////        $account_db = Account::mk()->find($account['id']);
////        //判断是否同一时间同一账号只能在一个地方登录
////        if (config('base.admin.login_unique')) {
////            if (!$my || $my['token'] != $admin['token']) {
////                $this->logined = false; //重置登录状态
////                Session::delete("admin");
////                Cookie::delete("keeplogin");
////                return false;
////            }
////        }
////        //判断管理员IP是否变动
////        if (config('base.admin.loginip_check')) {
////            if (!isset($account_db['loginip']) || $account_db['loginip'] != request()->ip()) {
////                $this->logout();
////                return false;
////            }
////        }
////        //判断管理员密码是否变动
////        if (!isset($account['password']) || $account['password'] != $account_db['password']) {
////            $this->logout();
////            return false;
////        }
////        $this->logined = true;
////        return true;
//    }
//
//    /**
//     * 获取当前登录
//     * @author 白沐泽
//     */
//    public function AdminInfo()
//    {
//        if ($this->logined) {
//            return true;
//        }
//        $admin = Session::get('admin');
//        if (!$admin) {
//            return false;
//        }
//        return $admin;
//    }
//
//    /**
//     * 检测是不是超级管理员
//     * @author 白沐泽
//     */
//    public function isSuper()
//    {
//        if ($this->logined) {
//            $AdminInfo = $this->AdminInfo();
//            $auth = explode(',', $AdminInfo['auth']);
//            if (in_array(1, $auth)) return true;
//            $authList = Auth::mk()->where('id', 'in', $auth)->column('node');
//            if (empty($authList)) return false;
//            $authList = explode(',', $authList[0]);
//            if (in_array('*', $authList)) return true;
//        }
//        return false;
//    }
//
//    /**
//     * 设置错误信息
//     *
//     * @param string $error 错误信息
//     * @return AdminServer
//     */
//    public function setError($error)
//    {
//        $this->_error = $error;
//        return $this;
//    }
//
//    /**
//     * 获取错误信息
//     * @return string
//     */
//    public function getError()
//    {
//        return $this->_error ? BmzLang($this->_error) : '';
//    }
}