<?php

use BaiMuZe\library\AppServer;

if (!function_exists('BmzLang')) {

    /**
     * 获取语言变量值
     * @param string $name 语言变量名
     * @param array $vars 动态变量值
     * @param string $lang 语言
     * @return mixed
     */
    function BmzLang($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || !$name) {
            return $name;
        }
        if (!is_array($vars)) {
            $vars = func_get_args();
            array_shift($vars);
            $lang = '';
        }
        return \think\facade\Lang::get($name, $vars, $lang);
    }
}
if (!function_exists('strLower')) {
    /**
     * 字符串转小写
     *
     * @param string $value
     * @return string
     */
    function strLower($value)
    {
        return \BaiMuZe\utility\Str::lower($value);
    }
}
if (!function_exists('ability')) {
    /**
     * 访问功能检查
     * @param null|string|int $ability
     * @return boolean
     * @throws ReflectionException
     */
    function ability($ability)
    {
        return \BaiMuZe\library\Auth::check(str_replace('.html', '', substr(strLower($ability), 1)), 2);
    }
}
if (!function_exists('arrayHas')) {
    /**
     * 检测一个数组是否包含指定键值
     *
     * @param array $array
     * @param string $key
     * @return bool
     */
    function arrayHas($array, $key)
    {
        return \BaiMuZe\utility\Arr::has($array, $key);
    }
}
if (!function_exists('token')) {
    /**
     * 生成表单令牌
     * @param string $name 令牌名称
     * @param mixed $type 令牌生成方法
     * @return string
     */
    function token($name = '__token__', $type = 'md5')
    {
        $token = AppServer::$sapp->request->buildToken($name, $type);
        return '<input type="hidden" name="' . $name . '" value="' . $token . '" />';
    }
}
if (!function_exists('xss_safe')) {
    /**
     * 文本内容XSS过滤
     * @param string $text
     * @return string
     */
    function xss_safe(string $text): string
    {
        // 将所有 onxxx= 中的字母 o 替换为符号 ο，注意它不是字母
        $rules = ['#<script.*?<\/script>#is' => '', '#(\s)on(\w+=\S)#i' => '$1οn$2'];
        return preg_replace(array_keys($rules), array_values($rules), trim($text));
    }
}
if (!function_exists('shortUrl')) {
    /**
     * 生成最短URL地址
     * @param string $url 路由地址
     * @param array $vars PATH 变量
     * @param boolean|string $suffix 后缀
     * @param boolean|string $domain 域名
     * @return string
     */
    function shortUrl(string $url = '', array $vars = [], $suffix = true, $domain = false)
    {
        return \BaiMuZe\utility\Url::shortUrl($url, $vars, $suffix, $domain);
    }
}
if (!function_exists('syspath')) {
    /**
     * 获取文件绝对路径
     * @param string $name 文件路径
     * @param ?string $root 程序根路径
     * @return string
     */
    function syspath(string $name = '', ?string $root = null): string
    {
        if (is_null($root)) $root = AppServer::$sapp->getRootPath();
        $attr = ['/' => DIRECTORY_SEPARATOR, '\\' => DIRECTORY_SEPARATOR];
        return rtrim($root, '\\/') . DIRECTORY_SEPARATOR . ltrim(strtr($name, $attr), '\\/');
    }
}
if (!function_exists('arrayToJson')) {
    /**
     * 转换数组为JSON字符串
     * @access public
     * @param array $array 数组
     * @param integer $options json参数
     * @return string
     */
    function arrayToJson(array $array, $options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($array, $options);
    }
}
if (!function_exists('JsonToArray')) {
    /**
     * 转换JSON字符串为数组
     * @access public
     * @param array $json JSON字符串
     * @param integer $options json参数
     * @return string
     */
    function JsonToArray($json, $options = JSON_UNESCAPED_UNICODE)
    {
        return json_decode($json, $options);
    }
}
if (!function_exists('syconfig')) {
    /**
     * 获取系统配置
     * @param string $label 配置类型
     * @param string $key 下标
     * @param $default 默认值
     * @author 白沐泽
     */
    function syconfig($label, $key, $default = null)
    {
        return \BaiMuZe\library\ConfigServer::get($label, $key, $default = null);
    }
}
if (!function_exists('encode')) {

    /**
     * 加密函数
     * @param string $value 待解密的值
     * @param int $expiry 过期时间
     * @param int $key 加密码
     * @param int $base 是否使用base64_decode
     * @return string
     */
    function encode($value, $expiry = 0, $key = '', $base = 1)
    {
        return app('security')->encrypt($value, $expiry, $key, $base);
    }
}

if (!function_exists('decode')) {

    /**
     * 解密函数
     * @param string $value 待解密的值
     * @param int $key 加密码
     * @param int $base 是否使用base64_decode
     * @return string
     */
    function decode($value, $key = '', $base = 1)
    {
        return app('security')->decrypt($value, $key, $base);
    }
}
if (!function_exists('echoVideoStream')) {

    /**
     * 输出视频流
     * @encoding UTF-8
     * @param unknown $file 文件地址
     * @param unknown $mime 文件mime类型
     * @author Twinkly
     * @create 2021年7月10日
     * @update 2021年7月10日
     */
    function echoVideoStream($file, $mime)
    {

        header("Content-type: $mime");
        header("Accept-Ranges: bytes");
        $size = filesize($file);
        if (isset($_SERVER['HTTP_RANGE'])) {
            header("HTTP/1.1 206 Partial Content");
            list($name, $range) = explode("=", $_SERVER['HTTP_RANGE']);
            list($begin, $end) = explode("-", $range);
            if ($end == 0) {
                $end = $size - 1;
            }
        } else {
            $begin = 0;
            $end = $size - 1;
        }
        header("Content-Length: " . ($end - $begin + 1));
        header("Content-Disposition: filename=" . basename($file));
        header("Content-Range: bytes " . $begin . "-" . $end . "/" . $size);
        $fp = fopen($file, 'rb');
        fseek($fp, $begin);
        while (!feof($fp)) {
            $p = min(1024, $end - $begin + 1);
            echo fread($fp, $p);
        }
        fclose($fp);
        exit();

    }
}
if (!function_exists('analysis')) {

    /**
     * 通过附件hash值获取附件地址
     * @param string $hash hash值
     * @param $storage
     * @return string
     */
    function analysis($hash, $storage = 'local')
    {

        $base = request()->host();// 结尾不带‘/’
        if (empty($hash)) {
            return $base . '/assets/images/nopic.jpg';
        }
        if (strpos($hash, 'http') === 0) {
            return $hash;
        }
        // 如果是纯hash
        if (preg_match("/^[0-9a-z]{32}$/", $hash)) {
            if ($storage == 'local') {
                return shortUrl('@home/attachment/index/hash/' . $hash, array(), false, true);
            } else {

            }
        } else {

        }
    }
}
if (!function_exists('export_all')) {

    /**
     * 组装导出控件
     * @encoding UTF-8
     * @param unknown $label
     * @param unknown $main_table
     * @param unknown $tables
     * @return string
     * @create 2022年3月22日
     * @update 2022年3月22日
     */
    function export_all($label, $main_table, $tables, $url)
    {
        $html = '<a  class="layui-btn layui-btn-primary layui-btn-sm" data-page="-1" data-queue="' . $url . '?label=' . $label . '&table=' . $main_table . '" data-event="report">导出</a>';
        return $html;
    }
}
if (!function_exists('exdesign')) {
    /**
     * 组装报表设计控件
     * @encoding UTF-8
     * @param unknown $label
     * @param unknown $main_table
     * @param unknown $tables
     * @return string
     * @create 2022年3月22日
     * @update 2022年3月22日
     */
    function exdesign($label, $main_table, $tables, $url, $id = '')
    {
        if (!$id) $id = 'commonexdesign';
        $html = '<a class="layui-btn layui-btn-primary layui-btn-sm" data-id="' . $id . '"  data-modal="' . $url . '?label=' . $label . '&table=' . $tables . '"  data-title="报表设计器" data-height="80%" data-width="900">设计报表</a>';
        return $html;
    }
}
if (!function_exists('datavalue')) {

    /**
     * 获取数据源
     * @param string $name 数据源名称
     * @param string $value 值
     * @return mixed
     */
    function datavalue($name, $value)
    {
        $list = config($name, 'data');
        if (empty($list)) {
            return '未知数据源';
        } else {
            if (false !== strpos($value, ',')) {
                $value = explode(',', $value);
            } else {
                $value = (array)$value;
            }
            $values = [];
            foreach ($list as $key => $val) {
                if (in_array($val['val'], $value)) {
                    $values[] = $val['name'];
                }
            }
            return implode(',', $values);
        }
    }
}
if (!function_exists('write_xls')) {

    /**
     * 导出excel
     * @param $data xls文件内容正文
     * @param $title xls文件内容标题
     * @param $filename 导出的文件名
     */
    function write_xls($data = array(), $title = array(), $filename)
    {
        require_once root_path() . '/extend/BaiMuZe/library/excel/PHPExcel.php';
        $excel = new PHPExcel();
        $sheet = $excel->getActiveSheet();
        $cols = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $titleCount = count($title);
        $r = $cols{0} . '1';
        $c = $cols{$titleCount} . '1';
        // 设置表头样式
        $sheet->getStyle("$r:$c")->applyFromArray([
            'font' => [
                'bold' => true
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            ],
            'borders' => [
                'top' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN
                ]
            ],
            'fill' => [
                'type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
                'rotation' => 90,
                'startcolor' => [
                    'argb' => 'FFA0A0A0'
                ],
                'endcolor' => [
                    'argb' => 'FFFFFFFF'
                ]
            ]
        ]);
        // 写入表头数据
        for ($i = 0; $i < $titleCount; $i++) {
            $sheet->setCellValue($cols{$i} . '1', $title[$i]);
        }
        // 写入表格数据
        $i = 0;
        foreach ($data as $d) {
            $j = 0;
            foreach ($d as $v) {
                $sheet->setCellValue($cols{$j} . ($i + 2), $v);
                $j++;
            }
            $i++;
        }
        $write = new PHPExcel_Writer_Excel5($excel);
        $path = runtime_path('report') . '/excel/' . $filename . '.xls';
        if (file_exists($path)) {
            unlink($path);
        }
        $write->save($path);
        return $path;
    }
}

if (!function_exists('read_excel')) {

    /**
     * 读取Excel表格内容
     * @param string $file 文件地址
     * @param int $start 开始行数
     * @return void
     */
    function read_excel($filepath, $start = 1, $ext = '')
    {
        require_once path() . '/framework/library/excel/PHPExcel/IOFactory.php';
        if (empty($ext)) {
            $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        }
        if ($ext == 'xlsx') {
            $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
        } elseif ($ext == 'xls') {
            $objReader = \PHPExcel_IOFactory::createReader('Excel5');
        }
        $excel = $objReader->load($filepath);
        $sheet = $excel->getSheet(0); // 读取第一个工作表
        $lastRow = $sheet->getHighestRow(); // 取得总行数
        $lastColumm = $sheet->getHighestColumn(); // 取得总列数
        $lastColummIndex = \PHPExcel_Cell::columnIndexFromString($lastColumm);
        $data = [];
        for ($row = 1; $row <= $lastRow; $row++) { // 行数是以第1行开始
            if ($row >= $start) {
                $rows = [];
                for ($columnIndex = 0; $columnIndex < $lastColummIndex; $columnIndex++) { // 列数是以A列开始
                    $cell = $sheet->getCellByColumnAndRow($columnIndex, $row);
                    // $column = \PHPExcel_Cell::stringFromColumnIndex($columnIndex);
                    // $value = $sheet->getCell($column . $row)->getValue();
                    $value = $cell->getValue();
                    if ($cell->getDataType() == \PHPExcel_Cell_DataType::TYPE_NUMERIC) {
                        $cellstyleformat = $cell->getParent()
                            ->getStyle($cell->getCoordinate())
                            ->getNumberFormat();
                        $formatcode = $cellstyleformat->getFormatCode();
                        if (preg_match('/^(\[\$[A-Z]*-[0-9A-F]*\])*[hmsdy]/i', $formatcode)) {
                            $value = gmdate("Y-m-d H:i:s", \PHPExcel_Shared_Date::ExcelToPHP($value));
                        } else {
                            $value = \PHPExcel_Style_NumberFormat::toFormattedString($value, $formatcode);
                        }
                    }
                    $rows[] = $value;
                }
                $data[] = $rows;
            }
        }
        return $data;
    }
}


