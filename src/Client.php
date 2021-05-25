<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/19
 * @copyright Canton Univideo
 */

namespace SSOClientSDK;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Psr\SimpleCache\CacheInterface;
use SSOClientSDK\Utils\Signature;

class Client
{
    /**
     * @var CacheInterface
     */
    protected $cache;
    /**
     * @var array $config
     */
    protected $config;

    public function __construct($config, $cache)
    {
        $localConfig  = require(__DIR__ . '/config/config.php');
        $this->config = array_merge($localConfig, $config);
        $this->cache  = $cache;

    }

    public function parseToken($ssoToken)
    {
        $parse = new Parser();
        $token = $parse->parse($ssoToken);

        $check = $token->verify(new Sha256(), $this->config['jwt']['secret']);

        if (!$check) {
            throw new Exception("非法token, 请确认是否配置jwt key");
        }

        return $token;
    }

    public function user($ssoToken)
    {
        try {

            $this->parseToken($ssoToken);
            $url = $this->config['url'] . $this->config['api']['sso_user'];

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
     * 不使用st login
     * st 是临时的, 无法复用.
     *
     * @param $st
     *
     * @return mixed
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/20
     */
    public function stLogin($st)
    {
        try {

            $url = $this->config['url'] . $this->config['api']['st_login'];

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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/25
     */
    public function logout($localToken)
    {
        $cache    = $this->cache;
        $get      = $this->config['cache']['get'];
        $ssoToken = $cache->$get($localToken . '.sso_token');
        $url      = $this->config['url'] . $this->config['api']['logout'];

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
        $token  = $this->parseToken($ssoToken);
        $expire = $token->getClaim('exp');
        $ttl    = $expire - time();
        $cache  = $this->cache;
        $set    = $this->config['cache']['set'];
        $cache->$set($localToken . '.sso_login', true, 300);
        $cache->$set($localToken . '.sso_token', $ssoToken, $ttl);
        $cache->$set(md5($ssoToken . '.sso_token'), $localToken, $ttl);
    }

    public function getLocalToken($ssoToken)
    {
        $this->parseToken($ssoToken);
        $cache = $this->cache;
        $get   = $this->config['cache']['get'];
        return $cache->$get(md5($ssoToken . '.sso_token'));
    }

    public function setLogout($localToken)
    {
        $cache = $this->cache;

        $m = $this->config['cache']['delete'];
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
        $cache = $this->cache;
        $has   = $this->config['cache']['has'];
        if (!$remoteCheck) {
            //检查本地sso登录即可
            if ($cache->$has($localToken . '.sso_login') && $cache->$has($localToken . '.sso_token')) {
                return true;
            }
        }

        $get      = $this->config['cache']['get'];
        $ssoToken = $cache->$get($localToken . '.sso_token');
        $this->parseToken($ssoToken);
        if (!$ssoToken) {
            return false;
        }
        $url = $this->config['url'] . $this->config['api']['status'];

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

    public function checkSign($data)
    {
        return Signature::checkSign($data, $this->config['sign']['secret']);
    }
}