<?php


namespace BaiMuZe\Admin\model;


use BaiMuZe\Admin\core\Model;

class Command extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /**
     * 表格设置
     * @author 白沐泽
     */
    public function getTitleSetting()
    {
        $titleSettings = [
            ['field' => 'id', 'title' => 'ID'],
            ['field' => 'type', 'title' => '类型'],
            ['field' => 'params', 'title' => '参数'],
            ['field' => 'command', 'title' => '命令'],
            ['field' => 'content', 'title' => '返回结果'],
            ['field' => 'status', 'title' => '结果', 'toolbar' => '#status'],
            ['field' => 'executetime', 'title' => '执行时间'],
            ['field' => 'created_at', 'title' => '创建时间'],
            ['fixed' => 'right', 'title' => '操作', 'toolbar' => '#barDemo'],
        ];
        return arrayToJson($titleSettings);
    }
}