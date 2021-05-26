<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/19
 * @copyright Canton Univideo
 */

namespace SSOClientSDK;

use Exception;
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
        $localConfig  = require(dirname(__DIR__) . '/config/config.php');
        $this->config = array_merge($localConfig, $config);
        $this->cache  = $cache;

    }

    public function user($ssoToken)
    {
        return $this->user->me($ssoToken);
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