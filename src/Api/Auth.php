<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/26
 * @copyright Canton Univideo
 */

namespace SSOClientSDK\Api;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use SSOClientSDK\SDKException;

class Auth extends ApiBase
{
    /**
     * 不使用st login
     * st 是临时的, 无法复用.
     *
     * @param $st
     *
     * @return mixed
     * @throws GuzzleException
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
                throw new SDKException('未登录');
            }
            if ($code === 404) {
                throw new SDKException('请检查SSO域名配置是否正确.');
            }
        }
        throw new SDKException('未登录');
    }

    public function openidLogin($openid)
    {
        $params = [
            'openid'    => $openid,
            'timestamp' => time(),
            'uri'       => $this->client->config['api']['openid_login'],
        ];
        return $this->client->post('', $this->client->config['api']['openid_login'], $params);
    }

    /**
     * 退出登录, 通知sso业务端已经退出.
     *
     * @param $localToken
     *
     * @return bool
     * @throws GuzzleException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/25
     */
    public function logout($localToken): bool
    {
        try {

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
            if ($res->getStatusCode() === 200) {
                return true;
            }
            return false;
        } catch (ClientException $e) {
            $res = $e->getResponse();
            if ($res->getStatusCode() === 401) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $localToken
     * @param $ssoToken
     *
     * @throws SDKException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/7/12
     */
    public function setLogin($localToken, $ssoToken)
    {
        $token  = $this->client->util->jwt->parseToken($ssoToken, $this->client->config['jwt']['secret']);
        $expire = $token->getClaim('exp');
        $ttl    = $expire - time();
        $cache  = $this->client->cache;
        $set    = $this->client->config['cache']['set'];
        $cache->$set($localToken . '.sso_login', true, 300);
        $cache->$set($localToken . '.sso_token', $ssoToken, $ttl);
        $cache->$set(md5($ssoToken . '.local_token'), $localToken, $ttl);
    }

    /**
     * @param string $ssoToken
     *
     * @return null|string
     * @throws SDKException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/7/12
     */
    public function getLocalToken(string $ssoToken): ?string
    {
        $this->client->util->jwt->parseToken($ssoToken, $this->client->config['jwt']['secret']);
        $cache = $this->client->cache;
        $get   = $this->client->config['cache']['get'];
        return $cache->$get(md5($ssoToken . '.local_token'));
    }

    /**
     * @param string $localToken
     *
     * @return null|string
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/7/12
     */
    public function getSsoToken(string $localToken): ?string
    {
        $cache = $this->client->cache;
        $get   = $this->client->config['cache']['get'];
        return $cache->$get($localToken . '.sso_token');
    }

    /**
     * @param string $localToken
     *
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/7/12
     */
    public function setLogout(string $localToken)
    {
        $cache = $this->client->cache;

        $get      = $this->client->config['cache']['get'];
        $ssoToken = $cache->$get($localToken . '.sso_token');
        $m        = $this->client->config['cache']['delete'];
        $cache->$m($localToken . '.sso_login');
        $cache->$m($localToken . '.sso_token');
        $cache->$m(md5($ssoToken . '.local_token'));

        // 删除用户缓存 User::me
        $cache->$m(md5($ssoToken));
    }

    /**
     * 检查sso是否登录.
     *
     * @param string $localToken
     * @param bool   $remoteCheck
     *
     * @return bool
     * @throws GuzzleException
     * @throws SDKException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/19
     */
    public function checkStatus(string $localToken, $remoteCheck = false): bool
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
        if (!$ssoToken) {
            return false;
        }
        $this->client->util->jwt->parseToken($ssoToken, $this->client->config['jwt']['secret']);
        $url = $this->client->config['url'] . $this->client->config['api']['status'];

        try {
            $client = new HttpClient();
            $res    = $client->post($url, [
                'headers' => [
                    'Authorization' => 'bearer ' . $ssoToken,
                    'Accept'        => 'application/json',
                ],
            ]);

            if ($res->getStatusCode() === 200) {
                // 更新本地sso登录缓存
                $this->setLogin($localToken, $ssoToken);
                return true;
            }

        } catch (ClientException $e) {
            $res = $e->getResponse();
            if ($res->getStatusCode() === 401) {
                return false;
            }
        }
        return false;
    }
}