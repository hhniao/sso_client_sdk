<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/19
 * @copyright Canton Univideo
 */

namespace SSOClientSDK;

use Closure;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\SimpleCache\CacheInterface;
use SSOClientSDK\Api\Auth;
use SSOClientSDK\Api\ScoreJournal;
use SSOClientSDK\Api\User;
use SSOClientSDK\Utils\Signature;
use SSOClientSDK\Utils\Util;

/**
 * Class Client
 *
 * @package SSOClientSDK
 * @property-read array          $config
 * @property-read CacheInterface $cache
 * @property-read Util           util
 * @property-read Auth           $auth
 * @property-read User           $user
 * @property-read ScoreJournal   $scoreJournal
 * @author  liuchunhua<448455556@qq.com>
 * @date    2021/5/26
 */
class Client
{
    private static $instance;
    /**
     * @var CacheInterface
     */
    protected $cache;
    /**
     * @var array $config
     */
    protected $config;
    /**
     * 是否使用缓存
     *
     * @var bool
     */
    private $useCache = true;
    private $api      = [];
    /**
     * @var Util
     */
    private $util;

    public function __construct($config, $cache)
    {
        if (isset($config['api'])) {
            unset($config['api']);
        }
        $localConfig  = require(__DIR__ . '/config/config.php');
        $this->config = array_merge($localConfig, $config);
        $this->checkConfig();
        $this->cache = $cache;

    }

    private function checkConfig()
    {
        if (!isset($this->config['sign']) || empty($this->config['sign'])) {
            throw new SDKException("签名参数未配置.");
        }

        if (!isset($this->config['sign']['app_key']) || empty($this->config['sign']['app_key'])) {
            throw new SDKException("签名参数APP KEY未配置.");
        }
        if (!isset($this->config['sign']['secret']) || empty($this->config['sign']['secret'])) {
            throw new SDKException("签名参数APP SECRET未配置.");
        }

        if (!isset($this->config['jwt']) || empty($this->config['jwt'])) {
            throw new SDKException("jwt参数未配置.");
        }
        if (!isset($this->config['jwt']['secret']) || empty($this->config['jwt']['secret'])) {
            throw new SDKException("jwt secret参数未配置.");
        }
    }

    /**
     * 静态调用
     *
     * @param $config
     * @param $cache
     *
     * @return Client
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/7/6
     * @since  2.0.1.0
     */
    public static function getInstance($config, $cache): Client
    {
        if (static::$instance === null) {
            static::$instance = new static($config, $cache);
        }

        return static::$instance;
    }

    public function user($ssoToken)
    {
        return $this->user->me($ssoToken);
    }

    /**
     * @param $data
     *
     * @return bool
     * @throws SDKException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/7/12
     */
    public function checkSign($data): bool
    {
        return Signature::checkSign($data, $this->config['sign']['secret']);
    }

    /**
     * @param $name
     *
     * @return mixed|Util
     * @throws SDKException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/7/12
     */
    public function __get($name)
    {
        if (isset($this->api[$name])) {
            return $this->api[$name];
        }
        if (strtolower($name) === 'util') {
            if ($this->util === null) {
                $this->util = new Util();
            }
            return $this->util;
        }

        $class = __NAMESPACE__ . '\\Api\\' . ucfirst($name);

        if (class_exists($class)) {
            $this->api[$name] = new $class($this);

            return $this->api[$name];
        }

        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new SDKException($class . '不存在.');
    }

    /**
     * @param bool $y
     *
     * @return $this
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/9/6
     */
    public function useCache($y = true): Client
    {
        $this->useCache = $y;
        return $this;
    }

    /**
     * @param string $ssoToken
     * @param string $path
     * @param array  $query
     *
     * @return mixed
     * @throws SDKException
     * @throws GuzzleException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/7/12
     */
    public function get(string $ssoToken, string $path, $query = [])
    {
        try {
            return $this->cacheGet($ssoToken, $path, $query, function () use ($ssoToken, $path, $query) {
                $query['timestamp'] = time();
                $query['app_key']   = $this->config['sign']['app_key'];
                $tmp                = $query;
                $tmp['uri']         = $path;
                $query['sign']      = Signature::sign($tmp, $this->config['sign']['secret']);
                $client             = new \GuzzleHttp\Client();

                $res = $client->get($this->config['url'] . $path, [
                    'headers' => [
                        'Authorization' => 'bearer ' . $ssoToken,
                        'Accept'        => 'application/json',
                    ],
                    'query'   => $query,
                ]);

                return $res->getBody()->getContents();
            });
        } catch (ClientException $e) {
            $code = $e->getResponse()->getStatusCode();
            if ($code === 401) {
                throw new SDKException('未登录', $code);
            } else if ($code === 404) {
                throw new SDKException('请检查SSO域名配置是否正确.', $code);
            } else if ($code === 419) {
                throw new SDKException('请求频率过高.', $code);
            } else if ($code === 403) {
                throw new SDKException('签名错误.', $code);
            } else if ($code === 422) {
                $data = json_decode($e->getResponse()->getBody()->getContents(), true);
                throw new SDKException("参数错误.", 422, $data);
            }
            throw new SDKException('系统故障.', $code, [], $e);
        }
    }

    private function cacheGet(string $ssoToken, string $path, $data, Closure $default)
    {
        if ($this->useCache !== true) {
            return json_decode($default(), true);
        }

        $key = $this->buildCacheKey($ssoToken, $path, $data);

        $get = $this->config['cache']['get'];

        $lock = $this->util->locker->lock($key, $this);

        $result = [];

        if ($lock['level'] > 0) {
            $ms = 200000 - ($lock['level'] * 100000);
            if ($ms > 0) {
                usleep($ms);
            }
            // 获取缓存数据
            $result = $this->cache->$get($key);
        }
        if (!$result) {
            // 发送http 请求
            $result = $default();
            if ($lock['locked'] == 0) {
                $set = $this->config['cache']['set'];
                $this->cache->$set($key, $result, $this->config['cache']['expire']);
                $this->util->locker->unlock($key, $this);
            }
        }
        $this->util->locker->decLvl($key, $this);
        return json_decode($result, true);
    }

    private function buildCacheKey(string $ssoToken, string $path, $data = []): string
    {
        $data['sso_token'] = $ssoToken;
        $data['uri']       = $path;

        return md5(http_build_query($data));
    }

    /**
     * @param string $ssoToken
     * @param string $path
     * @param array  $data
     *
     * @return array
     * @throws GuzzleException
     * @throws SDKException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/7/12
     */
    public function post(string $ssoToken, string $path, $data = []): array
    {
        try {

            return $this->cacheGet($ssoToken, $path, $data, function () use ($ssoToken, $path, $data) {
                $data['timestamp'] = time();
                $data['app_key']   = $this->config['sign']['app_key'];
                $tmp               = $data;
                $tmp['uri']        = $path;
                $data['sign']      = Signature::sign($tmp, $this->config['sign']['secret']);
                $client            = new \GuzzleHttp\Client();

                $res = $client->post($this->config['url'] . $path, [
                    'headers' => [
                        'Authorization' => 'bearer ' . $ssoToken,
                        'Accept'        => 'application/json',
                        'Content-Type'  => 'application/json',
                    ],
                    'json'    => $data,
                ]);
                return $res->getBody()->getContents();
            });

        } catch (ClientException $e) {
            $code = $e->getResponse()->getStatusCode();
            if ($code === 401) {
                throw new SDKException('未登录', $code);
            } else if ($code === 404) {
                throw new SDKException('请检查SSO域名配置是否正确.', $code);
            } else if ($code === 429) {
                throw new SDKException('请求频率过高.', $code);
            } else if ($code === 403) {
                throw new SDKException('签名错误.', $code);
            } else if ($code === 422) {
                $data = json_decode($e->getResponse()->getBody()->getContents(), true);
                throw new SDKException("参数错误.", 422, $data);
            }
            throw new SDKException('系统故障.', $code, [], $e);
        }
    }
}