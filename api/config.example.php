<?php
// 复制为 config.php，并仅在服务器上填写。config.php 已被 .gitignore 排除。
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'tk_edc',
        'user' => 'tk_edc_user',
        'password' => 'CHANGE_ME',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        // 空字符串表示仅允许同源访问；跨域部署时填写完整来源，例如 https://example.com
        'allowed_origin' => '',
        'session_name' => 'tk_edc_session',
        // 仅允许创建第一个账号，适合当前单账号模式。
        'first_user_registration_only' => true,
    ],
];
