<?php


namespace BaiMuZe\Admin\library;

use BaiMuZe\Admin\library\helper\Helper;
use BaiMuZe\Admin\utility\Arr;
use BaiMuZe\Admin\utility\Date;
use BaiMuZe\Admin\utility\Str;
use think\Container;
use think\facade\Db;

/**
 * 报表设计、导出管理
 * @encoding UTF-8
 * @author 白沐泽
 */
class ReportServer
{
    /**
     * 报表设计文件
     */
    private $file;
    private $label;
    private $table;
    private $json;
    private $name;
    private $filed;

    /**
     *  初始化服务
     * @param $label
     * @param $table
     * @param $name
     * @param $filed
     * @author 白沐泽
     */
    public function __construct(string $label = '', string $table = '', $name = '', array $filed = array())
    {
        if (!is_dir(runtime_path('report'))) {
            mkdir(runtime_path('report'), 0777);
            mkdir(runtime_path('report/excel'), 0777);
        }
        $this->label = $label;
        $this->table = explode('|', $table);
        $this->file = runtime_path('report') . '/' . $label . '.config';
        $this->json = runtime_path('report') . '/' . $label . '.json';
        $this->name = $name;
        $this->filed = $filed;
    }

    /**
     * @param string $vars 配置
     */
    public static function instance($vars)
    {
        return Container::getInstance()->invokeClass(self::class, $vars);
    }

    /**
     *  数据表初始化
     * @param $type 1初始化，2保存
     * @author 白沐泽
     */
    public function initialization(int $type = 1)
    {
        if ($type === 1) {
            if (file_exists($this->file)) {
                $table = require($this->file);
            } else {
                $columns = [];
                $column = Helper::selectField($this->label);
                if ($column) {
                    foreach ($column as $key => $val) {
                        $name = empty($val['Comment']) ? $val['Field'] : $val['Comment'];
                        $columns[$val['Field']] = [
                            'name' => $name,
                            'label' => $val['Field'],
                            'relevant' => '',
                            'output' => ''
                        ];
                    }
                }
                $table['columns'] = $columns;
                $table['name'] = $this->label;
                $table['label'] = $this->label;
            }
            return $table;
        } else {
            if ($this->filed) {
                $data['name'] = $this->name;
                $data['label'] = $this->label;
                $data['columns'] = $this->filed;
                $content = "<?php\r\n";
//                $content .= "if (!defined('IN_BOTON')) {\r\n";
//                $content .= "    die('Hacking attempt');\r\n";
//                $content .= "}\r\n";
                $content .= "return " . var_export($data, true) . ";\r\n";
                file_put_contents($this->file, $content);
                return true;
            }
        }
        return false;
    }

    /**
     *  报表设计
     * @param $type 1初始化，2保存
     * @author 白沐泽
     */
    public function design(int $type = 1)
    {
        if ($type === 1) {
            $jsons = [];
            if (file_exists($this->json)) {
                $json = file_get_contents($this->json);
                $json = json_decode($json, true);
                foreach ($json as $val) {
                    foreach ($val as $v2) {
                        $key = $v2['table'] . '-' . $v2['label'];
                        $jsons[$key] = [
                            'table' => $v2['table'],
                            'label' => $v2['label'],
                            'name' => $v2['name']
                        ];
                    }

                }
            }
            $tables = [];
            foreach ($this->table as $key => $val) {
                if (file_exists($this->file)) {
                    $config = require($this->file);
                    foreach ($config['columns'] as &$v1) {
                        if (!empty($v1)) {
                            $v1['selected'] = isset($jsons[$val . '-' . $v1['label']]);
                        }
                    }
                    $config['init'] = true;
                    $tables[$key] = $config;

                } else {
                    $columns = [];
                    $column = Helper::selectField($val);
                    if ($column) {
                        foreach ($column as $k => $v) {
                            $name = empty($v['Comment']) ? $v['Field'] : $v['Comment'];
                            $columns[$v['Field']] = [
                                'name' => $name,
                                'label' => $v['Field'],
                                'relevant' => '',
                                'output' => '',
                                'selected' => isset($jsons[$val . '-' . $v['Field']])
                            ];
                        }
                    }
                    $tables[$key] = [
                        'name' => $val,
                        'label' => $val,
                        'columns' => $columns,
                        'init' => false
                    ];
                }
            }
            return ['jsons' => $jsons, 'tables' => $tables];
        } else {
            $label = $this->label;
            $filed = $this->filed;
            $names = $this->name;
            $jsons = [];
            foreach ($filed as $key => $val) {
                $vals = explode('|', $val);
                if (Arr::has($names, $key)) {
                    $name = Arr::get($names, $key);
                } else {
                    $name = $vals[2];
                }
                $key = $vals[0];
                $jsons[$key][] = [
                    'table' => $vals[0],
                    'label' => $vals[1],
                    'name' => $name,
                    'value' => $val
                ];
            }
            file_put_contents($this->json, json_encode($jsons));
            return true;
        }
    }

    /**
     * 数据导出
     * @author 白沐泽
     */
    public function report($page, $tname)
    {
        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G
        set_time_limit(0);
        if (!file_exists($this->json)) {
            throw new Exception('请先配置报表信息');
        }
        if (file_exists(app_path() . '/common/report.php')) {
            require app_path() . '/common/report.php';
        }
        $config = file_get_contents($this->json);
        $config = json_decode($config, true);
        $map = call_user_func("get{$this->label}Where");
        if ($page > 0) {
            $list = Db::table($this->table)->where($map['where'])
                ->order($map['order'])
                ->page($page)->select();
        } else {
            $list = Db::table($this->table)->where($map['where'])
                ->order($map['order'])
                ->select(); // 获取当前主表信息
        }
        $data = [];
        foreach ($list as $key => $val) {
            $data[$key] = $this->getFiledValue($config, $val);
        }
        if (empty($data)) {
            throw new Exception('没有可导出的数据信息');
        }
        $title = [];
        foreach ($data[0] as $v) {
            $title[] = $v['name'];
        }

        $arr = [];
        foreach ($data as $val) {
            $p = [];
            foreach ($val as $v) {
                $p[] = $v['value'];
            }
            $arr[] = $p;
        }

        return write_xls($arr, $title, $tname);
    }

    /**
     * 解析数据
     * @param unknown $config
     * @param unknown $data
     * @return multitype:multitype:unknown Ambigous <string, multitype:, \Boton\Utility\mixed> multitype:unknown Ambigous <multitype:, string, \Boton\Utility\mixed>
     * multitype:multitype:unknown Ambigous <string, multitype:, \Boton\Utility\mixed> multitype:unknown Ambigous <multitype:, string, \Boton\Utility\mixed>
     * @author Twinkly
     * @datetime 2020年8月1日
     * @lastupdate 2020年8月1日
     */
    public function getFiledValue($config, $data)
    {
        $values = [];
        foreach ($config as $key => $val) {
            if ($key == $this->table) {
                $file = runtime_path('report') . '/' . $key . '.config';
                $setting = require($file);
                $columns = $setting['columns'];
                foreach ($val as $k => $v) {
                    if (isset($columns[$v['label']])) {
                        $column = $columns[$v['label']];
                        $values[] = [
                            'name' => $v['name'],
                            'value' => $this->getValue($v['label'], $column['output'], $column['relevant'], $data)
                        ];
                    }
                }
            } else {
                $file = runtime_path('report') . '/' . $key . '.config';
                $setting = require($file);
                $columns = $setting['columns'];
                foreach ($val as $k => $v) {
                    if (isset($columns[$v['label']])) {
                        $column = $columns[$v['label']];
                        $value = '';
                        $values[] = [
                            'name' => $v['name'],
                            'value' => $this->getValue($v['label'], $column['output'], $column['relevant'], $data)
                        ];
                    }
                }
            }
        }
        return $values;
    }

    /**
     * 解析类型数据
     * @param unknown $label
     * @param unknown $output
     * @param unknown $relevant
     * @param unknown $data
     * @return string|\Boton\Utility\mixed string|\Boton\Utility\mixed
     * @author Twinkly
     * @datetime 2020年8月1日
     * @lastupdate 2020年8月1日
     */
    public function getValue($label, $output, $relevant, $data)
    {
        if ($output == 'date') {
            if (Arr::has($data, $label)) {
                return Date::format(Arr::get($data, $label), $relevant);
            } else {
                return '';
            }

        } elseif ($output == 'currency') {
            if (Arr::has($data, $label)) {
                return Str::floating(Arr::get($data, $label), $relevant);
            } else {
                return '';
            }

        } elseif ($output == 'number') {
            if (Arr::has($data, $label)) {
                return "" . Arr::get($data, $label);
            } else {
                return '';
            }

        } elseif ($output == 'config') {
            if (Arr::has($data, $label)) {
                $val = Arr::get($data, $label);
                return config($val, $relevant);
            } else {
                return '';
            }

        } elseif ($output == 'dictionary') {

            if (Arr::has($data, $label)) {
                $val = Arr::get($data, $label);
                return datavalue($relevant, $val);
            } else {
                return '';
            }

        } elseif ($output == 'function') {
            if (Arr::has($data, $label)) {
                $val = Arr::get($data, $label);
                return call_user_func($relevant, $val);
            } else {
                return '';
            }

        } else {
            if (Arr::has($data, $label)) {
                return Arr::get($data, $label);
            } else {
                return '';
            }
        }
    }
}