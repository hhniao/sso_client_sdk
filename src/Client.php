<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/19
 * @copyright Canton Univideo
 */

namespace SSOClientSDK;

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
    /**
     * @var CacheInterface
     */
    protected $cache;
    /**
     * @var array $config
     */
    protected $config;

    private $api = [];

    /**
     * @var Util
     */
    private $util;

    private static $instance;

    public function __construct($config, $cache)
    {
        if (isset($config['api'])) {
            unset($config['api']);
        }
        $localConfig  = require(__DIR__ . '/config/config.php');
        $this->config = array_merge($localConfig, $config);
        $this->cache  = $cache;

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

            $client = new \GuzzleHttp\Client();

            $res = $client->get($this->config['url'] . $path, [
                'headers' => [
                    'Authorization' => 'bearer ' . $ssoToken,
                    'Accept'        => 'application/json',
                ],
                'query'   => $query,
            ]);

            return json_decode($res->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $res = $e->getResponse();

            if ($res->getStatusCode() === 401) {
                throw new SDKException('未登录');
            }

            if ($res->getStatusCode() !== 200) {
                throw new SDKException('未知错误, 稍后再试.');
            }
        }
        throw new SDKException('未知错误, 稍后再试.');
    }

    /**
     * @param string $ssoToken
     * @param string $path
     * @param array  $data
     *
     * @return mixed
     * @throws GuzzleException
     * @throws SDKException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/7/12
     */
    public function post(string $ssoToken, string $path, $data = [])
    {
        try {

            $client = new \GuzzleHttp\Client();

            $res = $client->post($this->config['url'] . $path, [
                'headers' => [
                    'Authorization' => 'bearer ' . $ssoToken,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ],
                'json'    => $data,
            ]);

            return json_decode($res->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $res = $e->getResponse();
            if ($res->getStatusCode() === 401) {
                throw new SDKException('未登录');
            }

            if ($res->getStatusCode() !== 200) {
                throw new SDKException('未知错误, 稍后再试.');
            }
        }
        throw new SDKException('未知错误, 稍后再试.');
    }
}