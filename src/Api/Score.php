<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/27
 * @copyright Canton Univideo
 */

namespace SSOClientSDK\Api;

class Score extends ApiBase
{
    /**
     * @param $ssoToken
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/27
     */
    public function index($ssoToken)
    {
        return $this->client->get($ssoToken, $this->client->config['api']['score_journal']['index']);
    }

    /**
     * @param $ssoToken
     * @param $data
     *
     * @return mixed
     * @throws \Exception
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/27
     */
    public function add($ssoToken, $data)
    {
        return $this->client->post($ssoToken, $this->client->config['api']['score_journal']['add'], $data);
    }
}