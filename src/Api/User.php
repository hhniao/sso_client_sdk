<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/19
 * @copyright Canton Univideo
 */

namespace SSOClientSDK\Api;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\SimpleCache\InvalidArgumentException;
use SSOClientSDK\SDKException;

class User extends ApiBase
{

    /**
     * @param $ssoToken
     *
     * @return mixed
     * @throws GuzzleException|SDKException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/6/29
     */
    public function me($ssoToken)
    {
        try {

            $this->client->util->jwt->parseToken($ssoToken, $this->client->config['jwt']['secret']);
            $url = $this->client->config['url'] . $this->client->config['api']['sso_user'];

            $client = new HttpClient();

            $res = $client->post($url, [
                'headers' => [
                    'Authorization' => 'bearer ' . $ssoToken,
                    'Accept'        => 'application/json',
                ],
            ]);


            $content = $res->getBody()->getContents();
            $data    = json_decode($content, true);

            return $data['data'];
        } catch (ClientException $e) {
            $code = $e->getResponse()->getStatusCode();
            if ($code === 401) {
                throw new SDKException('未登录');
            }
            if ($code === 404) {
                throw new SDKException('请检查SSO域名配置是否正确.');
            }
        }
        throw new SDKException('未登录');
    }

    /**
     * 修改密码.
     *
     * @param string $localToken
     * @param array  $data ['password' => 'new password']
     *
     * @return bool
     * @throws GuzzleException
     * @throws InvalidArgumentException|SDKException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/20
     */
    public function editPassword(string $localToken, array $data): bool
    {
        try {

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

            if ($res->getStatusCode() === 200) {
                return true;
            }
        } catch (ClientException $e) {
            $res = $e->getResponse();

            if ($res->getStatusCode() === 401) {
                throw new SDKException('未登录');
            }
        }
        return false;
    }

    /**
     * 修改个人资料
     *
     * @param string $localToken
     * @param array  $data ["name" => "姓名","nickname" => "昵称","mobile" => "手机号","head_img" => "头像","sex" => "性别"]
     *
     * @return bool
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/20
     */
    public function editUserProfile(string $localToken, array $data): bool
    {
        try {
            $cache    = $this->client->cache;
            $ssoToken = $cache->get($localToken . '.sso_token');
            $url      = $this->client->config['url'] . $this->client->config['api']['edit_user'];

            $client = new HttpClient();

            $res = $client->post($url, [
                'headers'     => [
                    'Authorization' => 'bearer ' . $ssoToken,
                    'Accept'        => 'application/json',
                ],
                'form_params' => $data,
            ]);

            if (!$res->getStatusCode() === 200) {
                return false;
            }

            $str = $res->getBody()->getContents();

            if (empty($str)) {
                return false;
            }

            $arr = json_decode($str, true);
            if (empty($arr) || !isset($arr['code']) || $arr['code'] !== 20000) {
                return false;
            }
            return true;
        } catch (ClientException $e) {
            $res = $e->getResponse();

            if ($res->getStatusCode() === 401) {
                return false;
            }
        }
        return false;
    }

    /**
     * 注册
     *
     * @param array $data [ "username" => "用户名", "password" => "密码", "name" => "姓名" ]
     *
     * @return bool
     * @throws GuzzleException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/20
     */
    public function register(array $data): bool
    {
        try {
            $url = $this->client->config['url'] . $this->client->config['api']['register'];

            $client = new HttpClient();

            $res = $client->post($url, [
                'headers'     => [
                    'Accept' => 'application/json',
                ],
                'form_params' => $data,
            ]);


            if (!$res->getStatusCode() === 200) {
                return false;
            }

            $str = $res->getBody()->getContents();

            if (empty($str)) {
                return false;
            }

            $arr = json_decode($str, true);
            if (empty($arr) || !isset($arr['code']) || $arr['code'] !== 20000) {
                return false;
            }
            return true;
        } catch (ClientException $e) {
            $res = $e->getResponse();

            if ($res->getStatusCode() === 401) {
                return false;
            }
        }
        return false;
    }
}