<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/26
 * @copyright Canton Univideo
 */

namespace SSOClientSDK\Utils;

use SSOClientSDK\SDKException;

/**
 *
 * Class Util
 *
 *
 * @package SSOClientSDK\Utils
 * @property-read \SSOClientSDK\Utils\Signature $signature
 * @property-read \SSOClientSDK\Utils\Jwt $jwt
 * @property-read \SSOClientSDK\Utils\Locker locker
 * @author  liuchunhua<448455556@qq.com>
 * @date    2021/5/26
 */
class Util
{
    private $tools = [];
    public function __get($name)
    {
        if (isset($this->tools[$name])) {
            return $this->tools[$name];
        }
        $class = __NAMESPACE__ . '\\' . ucfirst($name);

        if (class_exists($class)) {
            $this->tools[$name] = new $class($this);

            return $this->tools[$name];
        }

        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new SDKException($class . '不存在.');
    }
}