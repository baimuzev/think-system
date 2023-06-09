<?php


namespace BaiMuZe\Admin\utility;


use think\App;
use think\exception\HttpException;
use think\Request;
use think\Response;
use Closure;
class Multiple
{
    /**
     * 应用实例
     * @var App
     */
    protected $app;

    /**
     * 应用名称
     * @var string
     */
    protected $name;

    /**
     * 应用路径
     * @var string
     */
    protected $path;

    /**
     * App constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->name = $this->app->http->getName();
        $this->path = $this->app->http->getPath();
    }

    /**
     * 多应用解析
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->parseMultiApp()) return $next($request);
        return $this->app->middleware->pipeline('app')->send($request)->then(function ($request) use ($next) {
            return $next($request);
        });
    }

    /**
     * 解析多应用
     * @return bool
     */
    private function parseMultiApp(): bool
    {
        $defaultApp = $this->app->config->get('route.default_app') ?: 'index';
        [$script, $pathinfo] = [$this->scriptName(), $this->app->request->pathinfo()];
        if ($this->name || ($script && !in_array($script, ['index', 'router', 'think']))) {
            $this->app->request->setPathinfo(preg_replace("#^{$script}\.php(/|\.|$)#i", '', $pathinfo) ?: '/');
            return $this->setMultiApp($this->name ?: $script, true);
        } else {
            // 域名绑定处理
            $domains = $this->app->config->get('app.domain_bind', []);
            if (!empty($domains)) foreach ([$this->app->request->host(true), $this->app->request->subDomain(), '*'] as $key) {
                if (isset($domains[$key])) return $this->setMultiApp($domains[$key], true);
            }
            $name = current(explode('/', $pathinfo));
            if (strpos($name, '.')) $name = strstr($name, '.', true);
            // 应用绑定处理
            $map = $this->app->config->get('app.app_map', []);
            if (isset($map[$name])) {
                $appName = $map[$name] instanceof Closure ? (call_user_func_array($map[$name], [$this->app]) ?: $name) : $map[$name];
            } elseif ($name && (in_array($name, $map) || in_array($name, $this->app->config->get('app.deny_app_list', [])))) {
                throw new HttpException(404, 'app not exists:' . $name);
            } elseif ($name && isset($map['*'])) {
                $appName = $map['*'];
            } else {
                $appName = $name ?: $defaultApp;
                if (!is_dir($this->path ?: $this->app->getBasePath() . $appName)) {
                    return $this->app->config->get('app.app_express', false) && $this->setMultiApp($defaultApp, false);
                }
            }
            if ($name) {
                $this->app->request->setRoot('/' . $name);
                $this->app->request->setPathinfo(strpos($pathinfo, '/') ? ltrim(strstr($pathinfo, '/'), '/') : '');
            }
        }
        return $this->setMultiApp($appName ?? $defaultApp, $this->app->http->isBind());
    }

    /**
     * 设置应用参数
     * @param string $appName 应用名称
     * @param boolean $appBind 应用绑定
     * @return boolean
     */
    private function setMultiApp(string $appName, bool $appBind): bool
    {
        if (is_dir($appPath = $this->path ?: $this->app->getBasePath() . $appName . DIRECTORY_SEPARATOR)) {
            $this->app->setNamespace(($this->app->config->get('app.app_namespace') ?: 'app') . "\\{$appName}")->setAppPath($appPath);
            $this->app->http->setBind($appBind)->name($appName)->path($appPath)->setRoutePath($appPath . 'route' . DIRECTORY_SEPARATOR);
            $this->loadMultiApp($appPath);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 加载应用文件
     * @param string $appPath 应用路径
     * @codeCoverageIgnore
     * @return void
     */
    private function loadMultiApp(string $appPath): void
    {
        [$ext, $fmaps] = [$this->app->getConfigExt(), []];
        if (is_file($file = $appPath . 'common' . $ext)) include_once $file;
        foreach (glob($appPath . 'config' . DIRECTORY_SEPARATOR . '*' . $ext) as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $this->app->config->load($file, $fmaps[] = $name);
        }
        if (in_array('route', $fmaps) && method_exists($this->app->route, 'reload')) {
            $this->app->route->reload();
        }
        if (is_file($file = $appPath . 'event' . $ext)) {
            $this->app->loadEvent(include $file);
        }
        if (is_file($file = $appPath . 'middleware' . $ext)) {
            $this->app->middleware->import(include $file);
        }
        if (is_file($file = $appPath . 'provider' . $ext)) {
            $this->app->bind(include $file);
        }
        $this->app->lang->switchLangSet($this->app->lang->getLangSet());;
    }

    /**
     * 获取当前运行入口名称
     * @codeCoverageIgnore
     * @return string
     */
    private function scriptName(): string
    {
        $file = $_SERVER['SCRIPT_FILENAME'] ?? ($_SERVER['argv'][0] ?? '');
        return empty($file) ? '' : pathinfo($file, PATHINFO_FILENAME);
    }
}