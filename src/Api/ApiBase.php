<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/26
 * @copyright Canton Univideo
 */

namespace SSOClientSDK\Api;


use SSOClientSDK\Client;

abstract class ApiBase
{
    protected $client;
    public function __construct(Client $client)
    {
        $this->client = $client;
    }
}