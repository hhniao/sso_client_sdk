<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/25
 * @copyright Canton Univideo
 */

return [
    'url'              => '',
    // 接口文档参考: https://www.showdoc.com.cn/1398225327041100
    'api'              => [
        'sso_user'      => '/api/auth/me',
        'logout'        => '/api/auth/logout',
        'status'        => '/api/auth/status',
        'st_login'      => '/api/auth/st-login',
        'edit_user'     => '/api/user/profile/edit',
        'edit_password' => '/api/user/user/edit',
        'register'      => '/api/user/user/add',

    ],
    'jwt'              => [
        'secret' => '',
    ],

    'sign' => [
        'secret' => ''
    ],
    // 列表中的 路径强制校验sso登录状态.
    'force_sso_verify' => [],
    'exception_class'  => Exception::class,
];