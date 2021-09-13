<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/9/13
 * @copyright Canton Univideo
 */

namespace SSOClientSDK\Utils;


use SSOClientSDK\Client;
use SSOClientSDK\SDKException;

class Locker
{
    public function lock($key, Client $client)
    {

        $cache  = $client->cache;
        $level  = (int)$cache->get($key . '.locked.level', 0);
        $locked = (int)$cache->get($key . '.locked', 0);
        if ($locked === 0) {
            $cache->set($key . '.locked', 1, $client->config['cache']['expire']);
        }

        $inc = $client->config['cache']['inc'];

        if (!method_exists($cache, $inc)) {
            throw new SDKException(get_class($cache) . '::' . $inc . "方法不存在");
        }
        $cache->$inc($key . '.locked.level', 1);

        return [
            'locked' => $locked,
            'level'  => $level,
        ];
    }

    public function decLvl($key, Client $client)
    {
        $cache = $client->cache;
        $dec   = $client->config['cache']['dec'];

        if (!method_exists($cache, $dec)) {
            throw new SDKException(get_class($cache) . '::' . $dec . "方法不存在");
        }
        $cache->$dec($key . '.locked.level', 1);
    }

    public function unlock($key, Client $client)
    {
        $cache = $client->cache;
        $cache->set($key . '.locked', 0, $client->config['cache']['expire']);
    }
}