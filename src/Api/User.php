<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/19
 * @copyright Canton Univideo
 */

namespace SSOClientSDK\Api;


use GuzzleHttp\Client as HttpClient;

class User extends ApiBase
{

    /**
     * 修改密码.
     *
     * @param       $localToken
     * @param array $data ['password' => 'new password']
     *
     * @return bool
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/20
     */
    public function editPassword($localToken, $data)
    {
        $cache    = $this->client->cache;
        $ssoToken = $cache->get($localToken . '.sso_token');
        $url      = $this->client->config['url'] . $this->client->config['api']['edit_password'];

        $client = new HttpClient();

        $res = $client->post($url, [
            'headers'     => [
                'Authorization' => 'bearer ' . $ssoToken,
                'Accept'        => 'application/json',
            ],
            'form_params' => $data,
        ]);

        if ($res->getStatusCode() === 401) {
            return true;
        }

        if ($res->getStatusCode() === 200) {
            return true;
        }

        return false;
    }

    /**
     * 修改个人资料, 积分
     *
     * @param string $localToken
     * @param array  $data ["name" => "姓名","nickname" => "昵称","mobile" => "手机号","head_img" => "头像","sex" => "性别"]
     *
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/20
     */
    public function editUserProfile($localToken, $data): bool
    {
        $cache    = $this->client->cache;
        $ssoToken = $cache->get($localToken . '.sso_token');
        $url      = $this->client->config['url'] . $this->client->config['api']['edit_password'];

        $client = new HttpClient();

        $res = $client->post($url, [
            'headers'     => [
                'Authorization' => 'bearer ' . $ssoToken,
                'Accept'        => 'application/json',
            ],
            'form_params' => $data,
        ]);

        if ($res->getStatusCode() === 401) {
            return true;
        }

        if ($res->getStatusCode() === 200) {
            return true;
        }

        return false;
    }

    /**
     * 注册
     *
     * @param array $data [ "username" => "用户名", "password" => "密码", "name" => "姓名" ]
     *
     * @return bool
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/20
     */
    public function register($data)
    {
        $url = $this->client->config['url'] . $this->client->config['api']['register'];

        $client = new HttpClient();

        $res = $client->post($url, [
            'headers'     => [
                'Accept' => 'application/json',
            ],
            'form_params' => $data,
        ]);

        if ($res->getStatusCode() === 200) {
            return true;
        }

        return false;
    }
}