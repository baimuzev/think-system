<?php


namespace BaiMuZe\Admin\library;

/**
 * 系统模块管理
 * Class ModuleService
 * @package BaiMuZe\Admin\library
 */
class ModuleService extends BaseServer
{
    /**
     * 获取版本号信息
     * @return string
     */
    public static function getVersion(): string
    {
        return trim(AppServer::VERSION, 'v');
    }

    /**
     * 获取应用列表
     * @param array $data
     * @return array
     */
    public static function getModules(array $data = []): array
    {
        $path = AppServer::$sapp->getBasePath();
        foreach (scandir($path) as $item) if ($item[0] !== '.') {
            if (is_dir(realpath($path . $item))) $data[] = $item;
        }
        return $data;
    }

    /**
     * 获取本地组件
     * @param string $package 指定包名
     * @param boolean $force 强制刷新
     * @return array|string|null
     */
    public static function getLibrarys(string $package = '', bool $force = false)
    {
        static $plugs;
        if (empty($plugs) || $force) {
            $plugs = include syspath('vendor/versions.php');
        }
        return empty($package) ? $plugs : ($plugs[$package] ?? null);
    }
}