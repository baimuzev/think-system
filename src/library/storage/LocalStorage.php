<?php


namespace BaiMuZe\Admin\library\storage;


use BaiMuZe\Admin\library\Exception;
use BaiMuZe\Admin\Admin\library\Storage;
use BaiMuZe\Admin\utility\Arr;
use BaiMuZe\Admin\utility\Date;
use BaiMuZe\Admin\utility\File;

/**
 * 本地文件驱动
 * @author 白沐泽
 */
class LocalStorage extends Storage
{
    /**
     * 初始化入口
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function initialize()
    {
        $scheme = $this->app->request->scheme();
        $this->prefix = trim(dirname($this->app->request->baseFile()), '\\/');
        $domain = $this->app->request->host();
        if (in_array($scheme, ['http', 'https'])) {
            $this->prefix = "{$scheme}://{$domain}";
        }
    }

    /**
     * 获取当前实例对象
     * @param null|string $name
     * @return static
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function instance(?string $name = null)
    {
        return parent::instance('local');
    }

    /**
     * 获取文件当前URL地址
     * @param string $name 文件名称
     * @param string $power 安全模式
     * @param null|string $attname 下载名称
     * @return string
     */
    public function url(string $name, string $power = 'public', ?string $attname = null): string
    {
        if ($power != 'public') {
            return '';
        } else {
            $path = syconfig('storage', 'path');
            return "{$this->prefix}/home/attachment/index/hash/{$this->delSuffix($name)}{$this->getSuffix($attname,$name)}";
        }
//        return $power !== 'public' ? $name : "{$this->prefix}/upload/{$this->delSuffix($name)}{$this->getSuffix($attname,$name)}";
    }

    /**
     * 检查文件是否已经存在
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @return boolean
     */
    public function has(string $name, bool $safe = false): bool
    {
        return file_exists($this->path($name, $safe));
    }

    /**
     * 获取文件存储信息
     * @param string $name 文件名称
     * @param string $power 权限
     * @param null|string $attname 下载名称
     * @return array
     */
    public function info(string $name, bool $power = false, ?string $attname = null): array
    {
        return $this->has($name, $power) ? [
            'url' => $this->url($name, $power, $attname),
            'key' => "upload/{$name}", 'file' => $this->path($name, $power),
        ] : [];
    }

    /**
     * 获取文件存储路径
     * @param string $name 文件名称
     * @param string $power 权限
     * @param $time 时间
     * @return string
     */
    public function path(string $name = '', string $power = 'public', ?string $time = ''): string
    {
        $root = $this->app->getRootPath();
        $path = $power != 'public' ? 'public/safefile' : syconfig('storage', 'path', 'public/attachment');
        $time = $time ? $time : time();
        $timePath = Date::format($time, 'Ymd') . '/' . Date::format($time, 'H');
        return strtr("{$root}{$path}/$timePath/{$this->delSuffix($name)}", '\\', '/');
    }

    /**
     * 文件上传地址
     * @return string
     */
    public function buildUpload(): string
    {
        return url('admin/api.upload')->build();
    }

    /**
     * 本地文件上传
     * @param $data
     * @author 白沐泽
     */
    public function upload($data)
    {
        $time = time();
        //组装文件名
        $name = $data['hash'] . '.' . $data['extension'];
        //组建目标文件地址
        $path = $this->path('', $data['power'], $time);
        if (!is_dir($path) && false === @mkdir($path, 0755, true)) {//创建目录并更改权限
            throw new Exception(BmzLang('directory_failed'), 500);
        }
        if (!isset($data['file'])) throw new Exception(BmzLang('file_error'), 500);
        if (!$data['file']->move($path, $name)) {
            throw new Exception(BmzLang('file_error'), 500);
        } else {
            //更新权限，所有人可更改删除
            File::setChmod($path, 0775);
            // 如果有参数encryption，以参数为主，负责以php配置为主
            $encryption = 0;
            if (isset($data['encryption'])) {
                $encryption = $data['encryption'];
            } else {
                $encryption = syconfig('storage', 'encryption', 0);
            }
            // 只有PHP和js都不加密才不会加密
            if ($encryption == 0) {
                return $this->path($name, $data['power'], $time);
            } else {
                if (false !== $path = File::encode($this->path($name, $data['power'], $time))) {
                    return $path;
                } else {
                    throw new Exception(BmzLang('encode_failure'), 500);
                }
            }
        }
    }

    /**
     * 本地文件分块上传
     * @param $data
     * @param string $hash 文件hash值
     * @param string $chunk 当前区块
     * @param string $chunks 总共的区块
     * @param string $directory 上传文件目录
     * @param string $name 上传的文件名
     * @author 白沐泽
     */
    public function uploadChunk($data)
    {
        $encryption = isset($data['encryption']) ? $data['encryption'] : 1;
        $tmpdir = runtime_path('temp_file') . '/' . $data['hash'] . '/';//临时文件
        if (!is_dir($tmpdir) && false === @mkdir($tmpdir, 0777, true)) {
            throw new Exception(BmzLang('directory_failed'), 500);
        }
        if (!File::isPathWrite($tmpdir)) {
            throw new Exception(BmzLang('file_error'), 500);
        }
        $tmpfile = $tmpdir.'file_'.$data['chunk'] . '.part';
        if (($data['chunk'] + 1 !== $data['chunks']) && file_exists($tmpfile) && (md5_file($tmpfile) == md5_file((new \SplFileInfo($data['tmp_path']))->getPathname()))) {
            return ['status' => 2];
        }
        if (!move_uploaded_file((new \SplFileInfo($data['tmp_path']))->getPathname(), $tmpfile)) {//将文件从临时文件拿到缓存
            throw new Exception(BmzLang('file_error'), 500);
        }
        if ($data['chunk'] + 1 == $data['chunks']) {

            //单一文件上传
            if ($data['chunks'] == 1 && $data['chunk'] == 0) {
                File::remove_dir($tmpdir);
                return $this->upload($data);
            } else {
                $time = time();
                //组装文件名
                $name = $data['hash'] . '.' . $data['extension'];
                //组建目标文件地址
                $path = $this->path('', $data['power'], $time);
                if (!is_dir($path) && false === @mkdir($path, 0755, true)) {//创建目录并更改权限
                    throw new Exception(BmzLang('directory_failed'), 500);
                }
                //组建目标文件地址
                $target = $path . DIRECTORY_SEPARATOR . $name;
                $target = str_replace('/\\', '/', $target);

                try {
                    $blocks = $data['blocks'];
                    $blocks = json_decode(stripslashes($blocks), true);
                    $fp = fopen($target, 'wb');
                    foreach ($blocks as $block) {
                        $part = "{$tmpdir}file_{$block['chunk']}.part";
                        $size = $block['end'] - $block['start'];
                        $this_block = false;
                        while (!$this_block) {
                            if (File::exists($part)&& $size == filesize($part)) {
                                $this_block = true;
                                $handle = fopen($part, "rb");
                                fwrite($fp, fread($handle, $size));
                                fclose($handle);
                                unset($handle);
                            } else {
                                $this_block = true;
                            }
                        }
                    }
                    fclose($fp);
                    File::remove_dir($tmpdir);
                    //更新权限，所有人可更改删除
                    File::setChmod($target, 0666);
                    $encryption = isset($data['encryption'])?$data['encryption']:0;
                    if ($encryption== 0) {
                        return $target;
                    } else {
                        if (false !== $target = File::encode($target)) {
                            return $target;
                        } else {
                            throw new Exception(BmzLang('file_error'), 500);
                        }
                    }
                } catch (\Exception $e) {
                    throw new Exception(BmzLang('file_error'), 500);
                }
            }
           return true;
        } else {
            return false;
        }
    }

    /**
     * 生成相对目录
     * @param $directory 目录
     * @author 白沐泽
     */
    public function directory($directory)
    {
        if (empty($directory)) {
            //自动生成一个相对路径目录
            $time = time();
//            $directory=public_path()
//            $directory = path().config('upload.path') .$this->type. '/' . Date::format($time, 'Ymd') . '/'. Date::format($time, 'H') . '/';
        }
    }
}