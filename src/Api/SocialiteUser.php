<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/19
 * @copyright Canton Univideo
 */

namespace SSOClientSDK\Api;


use GuzzleHttp\Client as HttpClient;

class SocialiteUser extends ApiBase
{
    public function info($ssoOpenid, $appid)
    {

        $url = $this->client->config['url'] . $this->client->config['api']['socialite_user']['info'];

        $client = new HttpClient();

        $res = $client->get($url, [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'query'   => [
                'openid' => $ssoOpenid,
                'appid'  => $appid,
            ],
        ]);


        if ($res->getStatusCode() !== 200) {
            return [];
        }

        $content = $res->getBody()->getContents();

        return json_decode($content, true);
    }
}