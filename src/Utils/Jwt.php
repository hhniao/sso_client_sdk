<?php
/**
 * @author    liuchunhua<448455556@qq.com>
 * @date      2021/5/26
 * @copyright Canton Univideo
 */

namespace SSOClientSDK\Utils;

use Exception;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token;

class Jwt
{
    /**
     * @param $ssoToken
     * @param $secret
     *
     * @return \Lcobucci\JWT\Token
     * @throws \Exception
     * @author liuchunhua<448455556@qq.com>
     * @date   2021/5/26
     */
    public function parseToken($ssoToken, $secret): Token
    {
        $parse = new Parser();
        $token = $parse->parse($ssoToken);

        $check = $token->verify(new Sha256(), $secret);

        if (!$check) {
            throw new Exception("非法token, 请确认是否配置jwt key");
        }

        return $token;
    }
}