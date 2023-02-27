<?php

namespace App\Services\Score\Channels;

use App\Models\UserScoreLog;
use App\Services\Score\Deposit;

interface ChannelInterface
{
    public function __construct($data = []);

    public function name();

    public function exec(Deposit $deposit);

    /**
     * 渠道初始化, 前置检查
     * @param Deposit $deposit
     * @return mixed
     */
    public function init(Deposit $deposit);

    public function setOrderNo(UserScoreLog $log);
    /**
     * @return boolean
     */
    public function callbackExists();
}
