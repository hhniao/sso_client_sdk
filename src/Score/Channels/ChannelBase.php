<?php

namespace App\Services\Score\Channels;

use App\Models\UserScoreLog;
use App\Services\Score\Deposit;

class ChannelBase
{
    protected $name = '';
    protected $data;

    public function __construct($data = [])
    {
        $this->data = $data;
    }
    public function exec(Deposit $deposit)
    {
    }

    public function init(Deposit $deposit)
    {
    }

    public function setOrderNo(UserScoreLog $log)
    {

    }

    public function callbackExists()
    {
        return false;
    }
}
