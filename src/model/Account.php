<?php


namespace BaiMuZe\Admin\model;

use BaiMuZe\Admin\core\Model;

/**
 * 用户过渡中间表
 * @author 白沐泽
 */
class Account extends Model
{
    protected $table = 'bmz_account';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'created_time';
    protected $updateTime = 'update_time';
}