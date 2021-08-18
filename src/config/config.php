<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/25
 * @copyright Canton Univideo
 */

return [
    'url' => '',
    // 接口文档参考: https://www.showdoc.com.cn/1398225327041100
    'api' => [
        'sso_user'      => '/api/auth/me',
        'logout'        => '/api/auth/logout',
        'status'        => '/api/auth/status',
        'st_login'      => '/api/auth/st-login',
        'edit_user'     => '/api/user/profile/edit',
        'edit_password' => '/api/user/user/edit',
        'register'      => '/api/user/user/add',
        'openid_login'  => '/api/auth/openid-login',

        'score_journal' => [
            'index' => '/api/score-journal/index',
            'add' => '/api/score-journal/add',
        ],
        'socialite_user' => [
            'info' => '/api/socialite-user/info'
        ],
    ],
    'jwt' => [
        'secret' => '',
    ],

    'sign'             => [
        'app_key' => '',
        'secret' => '',
    ],
    'cache'            => [
        'get'    => 'get',
        'set'    => 'set',
        'delete' => 'delete',
        'has'    => 'has',
    ],
    // 列表中的 路径强制校验sso登录状态.
    'force_sso_verify' => [],
    'exception_class'  => Exception::class,
];