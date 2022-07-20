<?php

/**
 * sync with https://github.com/cblink/yapi-doc/blob/master/config/yapi.php
 */
return [
    // yap请求地址
    // 'base_url' => 'http://xxxxxxxx/', // custom yapi url
    // 'base_url' => 'http://yapi.smart-xwork.cn/', // yapi url
    'base_url' => false, // disable upload to yapi
    // 文档合并方式，"normal"(普通模式) , "good"(智能合并), "merge"(完全覆盖)
    'merge' => 'merge',

    // yapi
    // 1. 查看项目设置 -> 项目配置 -> 项目ID -> 填入 config.default.id 字段 (项目 id 与 url 中的 projectId 相同 /project/{projectId}/setting)
    // 2. 查看项目设置 -> token配置 -> token (点击复制按钮获取 项目 token)
    'config' => [
        'default' => [
            'id' => 1,
            'token' => '',
        ]
    ],

    'openapi' => [
        'enable' => true, // generate openapi.json
        'path' => public_path('openapi.json'),
    ],

    // todo: eolink 
    // 1. 在 eolink -> 其他 -> API 文档生成中配置 swagger 源
    // 2. 通过 Open API 触发同步操作
    'eolink' => [
        'enable' => false,
        'eo_secret_key' => '',
        'project_id' => '',
        'space_id' => '',
    ],

    'public' => [
        'prefix' => 'data',

        // 公共的请求参数,query部分
        'query' => [
            'page' => ['plan' => '页码，默认 1'],
            'per_page' => ['plan' => '每页数量，不超过 200，默认 15'],
        ],

        // 公共的响应参数
        'data' => [
            'err_code' => ['plan' => '错误码，200 表示成功', 'must' => true, 'required' => true],
            'err_msg' => ['plan' => '错误说明，请求失败时返回', 'must' => true],
            'meta' => [
                'plan' => '分页数据',
                'must' => false,
                'children' => [
                    'current_page' => ['plan' => '当前页数'],
                    'total' => ['plan' => '总数量'],
                    'per_page' => ['plan' => '每页数量'],
                ]
            ]
        ]
    ]
];