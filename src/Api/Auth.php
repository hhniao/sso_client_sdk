<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/26
 * @copyright Canton Univideo
 */

namespace SSOClientSDK;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use SSOClientSDK\Api\ApiBase;

class Auth extends ApiBase
{
    /**
     * 不使用st login
     * st 是临时的, 无法复用.
     *
     * @param $st
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/20
     */
    public function stLogin($st)
    {
        try {

            $url = $this->client->config['url'] . $this->client->config['api']['st_login'];

            $client = new HttpClient();

            $res = $client->post($url, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'json'    => [
                    'server_ticket' => $st,
                ],
            ]);


            $content = $res->getBody()->getContents();
            $data    = json_decode($content, true);

            return $data['data'];
        } catch (RequestException $e) {
            $code = $e->getResponse()->getStatusCode();
            if ($code === 401) {
                throw new Exception('未登录');
            }
            if ($code === 404) {
                throw new Exception('请检查SSO域名配置是否正确.');
            }
        }
        throw new Exception('未登录');
    }

    /**
     * 退出登录, 通知sso业务端已经退出.
     *
     * @param $localToken
     *
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/25
     */
    public function logout($localToken): bool
    {
        $cache    = $this->client->cache;
        $get      = $this->client->config['cache']['get'];
        $ssoToken = $cache->$get($localToken . '.sso_token');
        $url      = $this->client->config['url'] . $this->client->config['api']['logout'];

        $client = new HttpClient();

        $res = $client->post($url, [
            'headers' => [
                'Authorization' => 'bearer ' . $ssoToken,
                'Accept'        => 'application/json',
            ],
        ]);

        if ($res->getStatusCode() === 401) {
            return true;
        }

        if ($res->getStatusCode() === 200) {
            return true;
        }

        return false;
    }

    public function setLogin($localToken, $ssoToken)
    {
        $token  = $this->client->util->jwt->parseToken($ssoToken, $this->client->config['jwt']['secret']);
        $expire = $token->getClaim('exp');
        $ttl    = $expire - time();
        $cache  = $this->client->cache;
        $set    = $this->client->config['cache']['set'];
        $cache->$set($localToken . '.sso_login', true, 300);
        $cache->$set($localToken . '.sso_token', $ssoToken, $ttl);
        $cache->$set(md5($ssoToken . '.sso_token'), $localToken, $ttl);
    }

    public function getLocalToken($ssoToken)
    {
        $this->client->util->jwt->parseToken($ssoToken, $this->client->config['jwt']['secret']);
        $cache = $this->client->cache;
        $get   = $this->client->config['cache']['get'];
        return $cache->$get(md5($ssoToken . '.sso_token'));
    }

    public function setLogout($localToken)
    {
        $cache = $this->client->cache;

        $m = $this->client->config['cache']['delete'];
        $cache->$m($localToken . '.sso_login');
        $cache->$m($localToken . '.sso_token');
    }

    /**
     * 检查sso是否登录.
     *
     * @param      $localToken
     * @param bool $remoteCheck
     *
     * @return bool
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/19
     */
    public function checkStatus($localToken, $remoteCheck = false)
    {
        $cache = $this->client->cache;
        $has   = $this->client->config['cache']['has'];
        if (!$remoteCheck) {
            //检查本地sso登录即可
            if ($cache->$has($localToken . '.sso_login') && $cache->$has($localToken . '.sso_token')) {
                return true;
            }
        }

        $get      = $this->client->config['cache']['get'];
        $ssoToken = $cache->$get($localToken . '.sso_token');
        $this->client->util->jwt->parseToken($ssoToken, $this->client->config['jwt']['secret']);
        if (!$ssoToken) {
            return false;
        }
        $url = $this->client->config['url'] . $this->client->config['api']['status'];

        $client = new HttpClient();

        $res = $client->post($url, [
            'headers' => [
                'Authorization' => 'bearer ' . $ssoToken,
                'Accept'        => 'application/json',
            ],
        ]);

        if ($res->getStatusCode() === 401) {
            return false;
        }

        if ($res->getStatusCode() === 200) {
            // 更新本地sso登录缓存
            $this->setLogin($localToken, $ssoToken);
            return true;
        }

        return false;
    }
}