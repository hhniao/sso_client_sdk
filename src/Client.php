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
use Psr\SimpleCache\CacheInterface;
use SSOClientSDK\Utils\Signature;
use SSOClientSDK\Utils\Util;

/**
 * Class Client
 *
 * @package SSOClientSDK
 * @property-read array                  $config
 * @property-read CacheInterface         $cache
 * @property-read Util                   util
 * @property-read \SSOClientSDK\Api\Auth $auth
 * @property-read \SSOClientSDK\Api\User $user
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
     * @var \SSOClientSDK\Utils\Util
     */
    private $util;

    public function __construct($config, $cache)
    {
        $localConfig  = require(__DIR__ . '/config/config.php');
        $this->config = array_merge($localConfig, $config);
        $this->cache  = $cache;

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

    public function checkSign($data)
    {
        return Signature::checkSign($data, $this->config['sign']['secret']);
    }

    public function __get($name)
    {
        if (isset($api[$name])) {
            return $api[$name];
        }
        if (strtolower($name) === 'util') {
            if ($this->util === null) {
                $this->util = new Util();
            }
            return $this->util;
        }

        $class = __NAMESPACE__ . '\\Api\\' . ucfirst($name);

        if (class_exists($class)) {
            $api[$name] = new $class($this);

            return $api[$name];
        }

        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new Exception($class . '不存在.');
    }
}