<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/26
 * @copyright Canton Univideo
 */

namespace SSOClientSDK\Utils;

use Exception;

/**
 *
 * Class Util
 *
 *
 * @package SSOClientSDK\Utils
 * @property-read \SSOClientSDK\Utils\Signature $signature
 * @property-read \SSOClientSDK\Utils\Jwt $jwt
 * @author  liuchunhua<448455556@qq.com>
 * @date    2021/5/26
 */
class Util
{
    public function __get($name)
    {
        if (isset($api[$name])) {
            return $api[$name];
        }
        $class = __NAMESPACE__ . '\\' . ucfirst($name);

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