<?php


namespace BaiMuZe\Admin\command;


use BaiMuZe\Admin\library\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Log;

/**
 * 自定义一键curd
 * @author 白沐泽
 */
class Curd extends Command
{
    protected $appPath;
    // view 默认的三个模板
    protected $views = ['index', 'form'];

//    public function __construct()
//    {
//        parent::__construct();
//        $this->appPath = env('app_path');
//    }

    protected function configure()
    {
        $this->setName('bmz:curd')
            ->addArgument('parameter', Argument::OPTIONAL, "parameter name")
            ->addOption('module', null, Option::VALUE_REQUIRED, 'module name')
            ->setDescription('Create curd option parameter model --module?');
    }

    protected function execute(Input $input, Output $output)
    {
        // 首先获取默认模块
        $moduleName = config('app.default_module');
        $parameterName = trim($input->getArgument('parameter'));
        if (!$parameterName) {
            $output->writeln('parameter Name Must Set');
            exit;
        }

        if ($input->hasOption('module')) {
            $moduleName = $input->getOption('module');
        }

        $this->makeController($parameterName, $moduleName);
        $this->makeModel($parameterName, $moduleName);
        $this->makeView($parameterName, $moduleName);
        $this->makeValidate($parameterName, $moduleName);

        $output->writeln($parameterName . ' parameter create success');
        $output->writeln($parameterName . ' model create success');
        $output->writeln($parameterName . ' view create success');
        $output->writeln($parameterName . ' validate create success');
    }

    // 创建控制器文件
    protected function makeController($controllerName, $moduleName)
    {
        $controllerStub = $this->appPath . 'command' . DIRECTORY_SEPARATOR . 'curd' . DIRECTORY_SEPARATOR . 'Controller.stub';
        $controllerStub = str_replace(['$controller', '$module'], [ucfirst($controllerName), strtolower($moduleName)], file_get_contents($controllerStub));
        $controllerPath = $this->appPath . $moduleName . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR;
        if (!is_dir($controllerPath)) {
            mkdir($controllerPath, 0777, true);
        }
        return file_put_contents($controllerPath . ucfirst($controllerName) . '.php', $controllerStub);
    }

    // 创建模型文件
    public function makeModel($modelName, $moduleName)
    {
        $modelStub = $this->appPath . 'command' . DIRECTORY_SEPARATOR . 'curd' . DIRECTORY_SEPARATOR . 'Model.stub';
        $modelPath = $this->appPath . $moduleName . DIRECTORY_SEPARATOR . 'model';
        if (!is_dir($modelPath)) {
            mkdir($modelPath, 0777, true);
        }
        $modelStub = str_replace(['$model', '$module'], [ucfirst($modelName), strtolower($moduleName)], file_get_contents($modelStub));
        return file_put_contents($modelPath . DIRECTORY_SEPARATOR . ucfirst($modelName) . '.php', $modelStub);
    }

    // 创建验证器文件
    public function makeValidate($validateName, $moduleName)
    {
        $modelStub = $this->appPath . 'command' . DIRECTORY_SEPARATOR . 'curd' . DIRECTORY_SEPARATOR . 'Validate.stub';
        $modelPath = $this->appPath . $moduleName . DIRECTORY_SEPARATOR . 'validate';
        if (!is_dir($modelPath)) {
            mkdir($modelPath, 0777, true);
        }
        $modelStub = str_replace(['$validate', '$module'], [ucfirst($validateName), strtolower($moduleName)], file_get_contents($modelStub));
        return file_put_contents($modelPath . DIRECTORY_SEPARATOR . ucfirst($validateName) . '.php', $modelStub);
    }

    // 创建模板
    public function makeView($parameterName, $moduleName)
    {
        // 创建视图路径
        $viewPath = (config('template.view_path') ? config('template.view_path') . $moduleName . DIRECTORY_SEPARATOR : env('app_path') . $moduleName . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR) . strtolower($parameterName);
        if (!is_dir($viewPath)) {
            mkdir($viewPath, 0777, true);
        }

        foreach ($this->views as $view) {
            // 视图模板源文件
            $viewStub = $this->appPath . 'command' . DIRECTORY_SEPARATOR . 'curd' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $view . '.stub';

            // 创建文件
            file_put_contents($viewPath . DIRECTORY_SEPARATOR . $view . '.html', file_get_contents($viewStub));
        }

    }
}