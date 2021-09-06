# sso_client_sdk

#### 安装

    // 设置阿里云镜像
    composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
    // 安装
    composer require ny/sso_client_sdk -vvv
    // 配置文件, 复制配置文件到你的项目配置文件目录
    cp vendor/ny/sso_client_sdk/src/config/config.php <your config path>
    

#### 使用示例

    // !!! 注意以下接口需要在sso 后台添加推送地址
    // 1. 注册
    $ssoToken = $_REQUEST['sso_token'];
    $client   = Client::getInstance(config('sso'), Cache::store('redis'));
    $ssoUser  = $client->user->me($ssoToken);
    // ... 本地登录业务代码
    // ... 根据 sso 用户信息查询本地用户信息
    $localtoken = '';
    $client->auth->setLogin($localtoken, $ssoToken);


    // 2. 退出登录
    $client     = Client::getInstance(config('sso'), Cache::store('redis'));
    $localToken = $client->auth->getLocalToken($ssoToken);
    $client->auth->setLogout($localToken);

    // 3. 更新用户资料 
    try {

        $client = Client::getInstance(config('sso'), CacheClient::getClient());

        $data        = $_POST;
        $data['uri'] = '/' . request()->path();
        if (!$client->checkSign($data)) {
            die('error');
        }

        $tmp = [];
        if (isset($data['sex'])) {
            $tmp['gender'] = $data['sex'];
        }
        if (isset($data['head_img'])) {
            $tmp['avatar'] = $data['head_img'];
        }
        if (isset($data['nickname'])) {
            $tmp['nickname'] = $data['nickname'];
        }

        $userUnion = new UserUnion();
        $model = $userUnion->where('open_id', $data['openid'])->find();
        if ($model === null) {
            die('success'); // 找不到该用户, 返回成功, 不再推送.
        }
        /* @var \app\common\model\User $user */
        $user = $model->user;
        $user->save($tmp);
        die('success');
    } catch (\Exception $e) {

    }
    die('error');

#### 版本日志

1. v2.1.0.0
   
    a. 增加缓存