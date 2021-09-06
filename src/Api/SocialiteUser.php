<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/19
 * @copyright Canton Univideo
 */

namespace SSOClientSDK\Api;


use GuzzleHttp\Exception\GuzzleException;
use SSOClientSDK\SDKException;

class SocialiteUser extends ApiBase
{
    /**
     * @param $ssoOpenid
     * @param $appid
     *
     * @return array
     * @throws GuzzleException|SDKException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/7/12
     */
    public function info($ssoOpenid, $appid): array
    {
        return $this->client->get('', $this->client->config['api']['socialite_user']['info'], [
            'openid' => $ssoOpenid,
            'appid'  => $appid,
        ]);
    }
}