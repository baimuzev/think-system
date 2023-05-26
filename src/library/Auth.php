<?php


namespace BaiMuZe\Admin\library;

use app\admin\model\AbilityModel;
use BaiMuZe\Admin\core\Controller;
use BaiMuZe\Admin\model\AdminAuth;
use BaiMuZe\Admin\utility\Html;
use BaiMuZe\Admin\utility\Str;
use BaiMuZe\Admin\utility\Url;
use BaiMuZe\Admin\utility\Validator;
use think\Container;
use think\exception\HttpResponseException;
use think\facade\Log;

/**
 *  权限服务层
 * @author 白沐泽
 */
class Auth
{
    protected $app;//当前app
    protected $ability;//功能库
    protected $url;//当前访问的url
    protected $root;//当前根目录
    protected $controller;//当前访问控制器
    protected $action;//当前访问方法
    protected $class;//当前控制器
    protected static $user;//用户

    public function __construct(Controller $class)
    {
        $this->app = AppServer::$sapp;
        $this->url = $this->app->request->url();
        $this->root = $this->app->request->root();
        $this->controller = $this->app->request->controller();
        $this->action = $this->app->request->action();
        $this->class = $class;
        if ($class->account->isLogin()) {
            self::$user = $class->account->getUserinfo();
        }
    }

    public static function instance()
    {
        return Container::getInstance()->invokeClass(static::class);
    }

    /**
     * 构建功能库
     * 已废弃到控制器
     * @author 白沐泽
     */
    public function InitAbility()
    {
        $list = NodeServer::getMethods(true);
        $action = $this->class->request->post('action');
        if ($action == 'create') {
            $module = $this->class->request->post('module');
            $controller = $this->class->request->post('controller');
            $parent_id = $this->class->request->post('parent_id');
            $end = $this->class->request->post('end', 1);
            foreach ($list[$module] as $key => $val) {//第二层
                $data = array();
                $data['title'] = $val['title'];
                $data['url'] = '#';
                $data['pid'] = $parent_id;
                $data['path'] = $key;
                $data['created_at'] = time();
                $twoHave = \BaiMuZe\model\Ability::mk()->where(['path' => $data['path']])->find();
                if ($twoHave) {
                    $twoHave->save($data);
                    $two = $twoHave->id;
                } else {
                    $two = AbilityModel::mk()->insertGetId($data);
                }
                if (isset($val['children'])) {//第三层
                    foreach ($val['children'] as $kt => $vt) {
                        $data = array();
                        $data['title'] = $vt['title'];
                        $data['url'] = $kt;
                        $data['node'] = $kt;
                        if ($vt['isauth'] || $vt['islogin']) $data['power'] = 'protected'; else $data['power'] = 'public';
                        if ($vt['isauth']) $data['is_auth'] = 1; else $data['is_auth'] = 0;
                        if ($vt['islogin']) $data['is_login'] = 1; else $data['is_login'] = 0;
                        if ($vt['ispassword']) $data['is_password'] = 1; else $data['is_password'] = 0;
                        $data['created_at'] = time();
                        $data['pid'] = $two;
                        $data['path'] = $kt;
                        $threeHave = AbilityModel::mk()->where(['path' => $data['path']])->find();
                        if ($threeHave) {
                            $data['id'] = $threeHave['id'];
                            $threeHave->save($data);
                        } else {
                            AbilityModel::mk()->insertGetId($data);
                        }
                    }
                }
            }
            if ($end == 1) {
                $time = cache('module_recreate');
                if ($time < time() || $time < (time() + 1)) {//间隔
                    AbilityModel::mk()->whereTime('created_at', '<', $time)->delete();
                }
            }
            $this->class->success('构建完成');
        } else {
            cache('SystemAuthNode', NULL);
            $result = array();
            foreach ($list as $key => $val) {//第一层
                $title = config('base.dir.' . $key);
                $data['title'] = $title;
                $data['url'] = '#';
                $data['created_at'] = time();
                $data['path'] = $key;
                $oneHave = AbilityModel::mk()->where(['path' => $data['path']])->find();
                if ($oneHave) {
                    $oneHave->save($data);
                    $one = $oneHave->id;
                } else {
                    $one = AbilityModel::mk()->insertGetId($data);
                }
                $result[] = [
                    'parent_id' => $one,
                    'module' => $key
                ];
            }
            cache('module_recreate', time());
            $this->class->success('开始初始化功能库', $result);
        }
    }

    /**
     *  初始化权限服务
     * @author 白沐泽
     */
    public function initialize()
    {
        $root = $this->root;
        $root = str_replace('/', '', $this->root);
        $path = Str::lower($root . '/' . $this->controller . '/' . $this->action);
        $this->CheckAuth($path);
    }

    /**
     * 检查指定功能授权
     * @param $ability
     * @param $type 1根据id,2根据url(node)
     * @author 白沐泽
     */
    public static function check($ability, $type = 1)
    {
        if ($type == 2) {
            if (count(explode('/', $ability)) <= 2) $ability = $ability . '/index';
            $ability = AbilityModel::mk()->where('path', $ability)->value('id');
        }
        $user = self::$user;
        if ($user) {
            $auth = explode(',', $user['auth']);
            if (in_array(1, $auth)) return true;
            $authList = AdminAuth::mk()->where('id', 'in', $auth)->column('node');
            if (empty($authList)) return false;
            if (in_array($ability, $authList)) return true;
        }
        return false;
    }

    /**
     * 校验权限
     * @param string $root
     * @param bool $login //强制登录
     * @author 白沐泽
     */
    private function CheckAuth(string $path = '', bool $login = false)
    {
        $account = self::$user;
        $ability = AbilityModel::mk()->where([
            'path' => $path
        ])->find();
        if ($ability) {
            if ($ability->power != 'public') {
                if (!self::check($ability->id)) $this->throwMessage('权限校验失败');
            }
            if ($ability->is_password) {
                if (syconfig('system', 'password', 1)) {
                    $password = $this->app->request->param('_password');
                    if (!$password || !Validator::checkPassword($password, $account['password'], $account['salt'])) $this->throwMessage('密码错误', 5);
                }
                if (!self::check($ability->id)) $this->throwMessage('权限校验失败');
            }
        } else {
            $this->class->redirect(shortUrl('home/index/index'));
        }
    }

    /**
     * 检验异常抛出
     * @param string $message 消息
     * @param string $url
     * @param int $code
     * @author 白沐泽
     */
    private function throwMessage($message, $code = 6)
    {
        if (Url::visitType() !== 2) {
            if (!self::$user) {
                $this->class->fetch(config('app.dispatch'), ['message' => $message, 'url' => 'home/index/index', 'wait' => 5]);
            }
        }
        $this->class->error($message, array(), $code);
    }
}