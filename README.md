# sso_client_sdk

#### 使用示例

    // SSO TOKEN LOGIN
    $ssoToken = $_REQUEST['sso_token'];
    $client   = new Client(config('sso'), Cache::store('redis'));
    $ssoUser  = $client->user->me($ssoToken);
    // ... 本地登录业务代码
    // ... 根据 sso 用户信息查询本地用户信息
    $localtoken = '';
    $client->auth->setLogin($localtoken, $ssoToken);


    // SSO LOGOUT
    $client     = new Client(config('sso'), Cache::store('redis'));
    $localToken = $client->auth->getLocalToken($ssoToken);
    $client->auth->setLogout($localToken);
