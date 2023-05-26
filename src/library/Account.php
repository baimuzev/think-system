<?php


namespace BaiMuZe\Admin\library;

use app\api\model\Member;
use BaiMuZe\Admin\core\VirtualModel;
use BaiMuZe\Admin\library\huanxin\Easemob;
use BaiMuZe\Admin\library\token\Token;
use BaiMuZe\Admin\model\Account as AccountModel;
use BaiMuZe\Admin\model\Admin;
use BaiMuZe\Admin\utility\Date;
use BaiMuZe\Admin\utility\Random;
use BaiMuZe\Admin\utility\Validator;
use think\facade\Cookie;
use think\facade\Db;

/**
 * 中间表服务
 * @author 白沐泽
 */
class Account
{
    protected static $instance = null;
    protected $_error = '';
    protected $_logined = false;
    protected $_token = null;
    protected $_user;
    //Token默认有效时长
    protected $keeptime = 2592000;
    protected $id = 0;

    /**
     *
     * @param array $options 参数
     * @return Account
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }

        return self::$instance;
    }

    /**
     * 创建账户，不验证任何信息
     * @param unknown $username
     * @param unknown $password
     * @param string $mobile
     * @param string $model
     * @param array $extend 相关表的拓展信息
     * @param $register 是否注册环信
     * @author 白沐泽
     * @datetime 2020年8月1日
     * @lastupdate 2020年8月1日
     */
    public function register($username, $password, $mobile = '', $model = 'admin', $extend = [], $register = false)
    {
        // 检测用户名、手机号是否存在
        if (AccountModel::getByUserName($username)) {
            $this->setError('Username already exist');
            return false;
        }
        if ($mobile && AccountModel::getByMobile($mobile)) {
            $this->setError('Mobile already exist');
            return false;
        }
        $ip = request()->ip();
        $time = time();
        $data = [
            'user_name' => $username,
            'password' => $password,
            'mobile' => $mobile,
            'model' => $model,
            'encode_id' => Random::uniqidNumber(10, 'HY'),
        ];
        $params = array_merge($data, [
            'salt' => Random::alnum(),
            'logintime' => $time,
            'loginip' => $ip,
            'status' => 0
        ]);
        $params['password'] = $this->getEncryptPassword($password, $params['salt']);
        $params['equipment'] = isset($extend['equipment']) ? $extend['equipment'] : '';
        //开启事务
        Db::startTrans();
        try {
            $accout_id = AccountModel::mk()->insertGetId($params);
            if (isset($extend['equipment'])) unset($extend['equipment']);
            if ($model === 'admin') {
                $extend['id'] = $accout_id;
                Admin::mk()->save($extend);
            } else if ($model === 'member') {
                Member::mk()->save($extend);
            } else {
                //创建模型
                VirtualModel::mk($model)->save($extend);
            }
            if ($register) {
                $huanxin = new  Easemob();
                $create_result = $huanxin->createUser($data['encode_id'], $params['password'], $data['username']);
                if (!$create_result) {
                    $this->setError('环信注册失败');
                    Db::rollback();
                    return false;
                }
                if (isset($create_result['error'])) {
                    if ($create_result['error'] != 'duplicate_unique_property_exists') {
                        $this->setError('环信注册失败');
                        Db::rollback();
                        return false;
                    } else {
                        //重置环信登录密码与昵称
                        $huanxin->resetPassword($data['encodeID'], $params['password']);
                        $huanxin->editNickname($data['encodeID'], $data['username']);
                    }
                }
            }
            Db::commit();
            return true;
        } catch (\think\Exception $e) {
            Db::rollback();
            $this->setError($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * 根据Token初始化
     *
     * @param string $token Token
     * @return boolean
     */
    public function init($token)
    {
        if ($this->_logined) {
            return true;
        }
        if ($this->_error) {
            return false;
        }
        $data = Token::get($token);
        if (!$data) {
            return false;
        }
        $user_id = intval($data['uid']);
        if ($user_id > 0) {
            $user = AccountModel::mk()->find($user_id);
            if (!$user) {
                $this->setError('Account not exist');
                return false;
            }
            if ($user['status'] != 0) {
                $this->setError('Account is locked');
                return false;
            }
            $this->_user = $user;
            $this->_logined = true;
            $this->_token = $token;
            $this->id = $user_id;
            return true;
        } else {
            $this->setError('You are not logged in');
            return false;
        }
    }

    /**
     * 登录
     * @param string $account 账号,用户名、邮箱、手机号
     * @param string $password 密码
     * @author 白沐泽
     */
    public function login($account, $password)
    {
        $filed = Validator::instance()->validateMobile($account) ? 'mobile' : 'user_name';
        $user = AccountModel::mk()->where($filed, $account)->find();
        if (!$user) {
            $this->setError('Account is incorrect');
            return false;
        }
        if ($user->status != 0) {
            $this->setError('Account is locked');
            return false;
        }
        if ($user->password != $this->getEncryptPassword($password, $user->salt)) {
            $this->setError('Password is incorrect');
            $user->loginfailure++;
            $user->save();
            return false;
        }
        //失败次数验证
        if (config('base.admin.login_failure_retry') && $user->loginfailure >= 10 && time() - $user->update_time < 86400) {
            $this->setError('Please try again after 1 day');
            return false;
        }
        //直接登录会员
        return $this->direct($user->id);
    }

    /**
     * 直接登录账号
     * @param int $user_id
     * @return boolean
     */
    public function direct($user_id)
    {
        $user = AccountModel::mk()->find($user_id);
        if ($user) {
            Db::startTrans();
            try {
                $ip = request()->ip();
                $time = time();
                //判断连续登录和最大连续登录
                if ($user->logintime < Date::unixtime('day')) {
                    $user->successions = $user->logintime < Date::unixtime('day', -1) ? 1 : $user->successions + 1;
                    $user->maxsuccessions = max($user->successions, $user->maxsuccessions);
                }
                $user->prevtime = $user->logintime;
                //记录本次登录的IP和时间
                $user->loginip = $ip;
                $user->logintime = $time;
                //重置登录失败次数
                $user->loginfailure = 0;
                $user->save();
                $this->_user = $user;
                $this->_token = Random::uuid();
                Token::set($this->_token, $user->id, $this->keeptime, $user->model);
                $this->_logined = true;
                Db::commit();
                return $this->_token;
            } catch (Exception $e) {
                Db::rollback();
                $this->setError($e->getMessage());
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 判断是否登录
     * @return boolean
     */
    public function isLogin()
    {
        if ($this->_logined) {
            return true;
        }
        return false;
    }

    /**
     * 获取当前Token
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * 获取会员基本信息
     */
    public function getUserinfo()
    {
        $data = $this->_user->toArray();
        if ($this->_user['model'] === 'admin') {
            $info = Admin::mk()->find($this->_user['id']);
        } else if ($this->_user['model'] === 'member') {
            $info = Member::mk()->find($this->_user['id']);
        } else {
            //创建模型
            $info = VirtualModel::mk($this->_user['model'])->find($this->_user['id']);
        }
        if (is_null($info)) return array();
        $data = array_merge($data, $info->toArray());
        return $data;
    }

    /**
     * 账号退出登录
     * @author 白沐泽
     */
    public function logout(): bool
    {
        if ($this->id && $this->_token && $this->_logined) {
            Token::delete($this->_token);
            $this->_logined = false;
            return true;
        }
        return false;
    }

    /**
     * 自动登录
     * @return boolean
     */
    public function autologin()
    {
        $keeplogin = Cookie::get('keeplogin');
        if (!$keeplogin) {
            return false;
        }
        list($id, $keeptime, $expiretime, $key) = explode('|', $keeplogin);
        if ($id && $keeptime && $expiretime && $key && $expiretime > time()) {
//            $account = Account::mk()->find($id);
//            if (!$account || $this->_token) return false;
//            $admin = Admin::get($id);
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
        } else {
            return false;
        }
    }

    /**
     * 获取密码加密后的字符串
     * @param string $password 密码
     * @param string $salt 密码盐
     * @return string
     */
    public function getEncryptPassword($password, $salt = '')
    {
        return md5(md5($password) . $salt);
    }

    /**
     * 设置会话有效时间
     * @param int $keeptime 默认为永久
     */
    public function keeptime($keeptime = 0)
    {
        $this->keeptime = $keeptime;
    }

    /**
     * 设置错误信息
     *
     * @param string $error 错误信息
     * @return Auth
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->_error ? BmzLang($this->_error) : '';
    }
}