<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/19
 * @copyright Canton Univideo
 */

namespace SSOClientSDK\Api;


use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

class SocialiteUser extends ApiBase
{
    /**
     * @param $ssoOpenid
     * @param $appid
     *
     * @return array
     * @throws GuzzleException
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/7/12
     */
    public function info($ssoOpenid, $appid): array
    {

        try {
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

            if (!$res->getStatusCode() === 200) {
                return [];
            }

            $str = $res->getBody()->getContents();

            if (empty($str)) {
                return [];
            }

            $arr = json_decode($str, true);
            if (empty($arr) || !isset($arr['code']) || $arr['code'] !== 20000) {
                return [];
            }
            return $arr;
        } catch (ClientException $e) {
            $res = $e->getResponse();

            if ($res->getStatusCode() === 401) {
                return [];
            }
        }
        return [];
    }
}