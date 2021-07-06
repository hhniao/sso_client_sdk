<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/25
 * @copyright Canton Univideo
 */

namespace SSOClientSDK\Utils;

use Exception;

class Signature
{

    /**
     * 校验签名.
     *
     * @param array  $data $_REQUEST 并且包含 ['uri' => $uri]
     * @param string $secret
     *
     * @return bool
     * @throws Exception
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/25
     */
    public static function checkSign(array $data, string $secret): bool
    {
        if (!isset($data['sign'])) {
            throw new Exception('签名必须.');
        }
        $sign = $data['sign'];
        unset($data['sign']);
        $str = static::buildSignString($data, $secret);

        return md5($str) === $sign;
    }

    /**
     * @param array  $data
     * @param string $secret
     *
     * @return string
     * @throws Exception
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/7/6
     */
    public static function buildSignString(array $data, string $secret): string
    {
        if (!isset($data['timestamp'])) {
            throw new Exception('时间戳必须.');
        }
        if ($data['timestamp'] < time() - 300 || $data['timestamp'] > time() + 300) {
            throw new Exception('时间戳错误.');
        }

        if (!isset($data['uri'])) {
            throw new Exception('URI必须.');
        }
        ksort($data);

        $str = '';

        foreach ($data as $k => $v) {
            $str .= $k . '=' . $v . '&';
        }
        $str = trim($str, '&');

        return $str . $secret;
    }

}