
<?php

return [
    'admin' => [
        //登录失败超过10次则1天后重试
        'login_failure_retry' => true,
        //是否开启IP变动检测
        'loginip_check' => true,
        //是否同一账号同一时间只能在一个地方登录
        'login_unique' => false,
        //安全密钥
        'secretkey' => '&jnhwD9!x2',
        'ignores' => [//禁止访问内置方法
            '_save_filter',
            '_save_result',
            '_page_filter',
            '_form_result',
            '_form_filter',
            '_delete_filter',
            '_delete_result'
        ]
    ],
    'dir' => [
        'admin' => '系统管理',
        'api' => '接口管理',
        'design' => '定制开发',
        'home' => '公共访问',
        'wechat'=>'微信公众号'
    ],
    // 配置类型
    'config_source' => [
        'system' => ['name' => '系统配置', 'hidden' => false],
        'storage' => ['name' => '存储配置', 'hidden' => true],
        'app' => ['name' => '平台配置', 'hidden' => true],
        'wechat' => ['name' => '微信配置', 'hidden' => false],
        'albaba' => ['name' => '支付宝配置', 'hidden' => false]
    ],
    // 配置数据类型
    'config_type' => [
        'text' => '文本',
        'pwd' => '加密文本',
        'textarea' => '文本框',
        'cert' => '证书',
        'integer' => '整数',
        'float' => '浮点数',
        'json' => 'JSON',
        'date' => '日期',
        'datetime' => '时间',
        'image' => '图片',
        'radio' => '判断'
    ],
    //后台规则拦截
    'admin_root' => [
        'admin',
        'design'
    ],
    //token规则
    'token' => [
        //驱动
        'type' => 'Mysql',
        //缓存前缀
        'key' => 'baimuzesettoken220928',//请勿随意修改
        //加密方式
        'hashalgo' => 'ripemd160',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
        'table' => 'token'
    ]
];