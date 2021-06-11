# sso_client_sdk

#### 安装

    // 设置阿里云镜像
    composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
    // 安装
    composer require ny/sso_client_sdk -vvv
    // 配置文件, 复制配置文件到你的项目配置文件目录
    cp vendor/ny/sso_client_sdk/src/config/config.php <your config path>
    

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

    // 其他接口
    $client     = new Client(config('sso'), Cache::store('redis'));
    $client->get($ssoToken, $path);
    $client->get($ssoToken, '/api/user/info');
    $client->post($ssoToken, '/api/user/info', ['foo' => 'bar]);
