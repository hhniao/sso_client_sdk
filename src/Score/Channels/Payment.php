<?php

namespace App\Services\Score\Channels;

use App\Models\UserScoreLog;
use App\Services\Score\Deposit;

class Payment extends ChannelBase implements ChannelInterface
{
    public function name()
    {
        return '第三方支付';
    }

    public function setOrderNo(UserScoreLog $log)
    {
        if (isset($this->data['order_no'])) {
            $log->order_no = $this->data['order_no'];
        }
    }

    public function callbackExists()
    {
        return true;
    }

}
