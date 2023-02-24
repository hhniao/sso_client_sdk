<?php

namespace App\Services\Score\Channels;

use App\Services\Score\Deposit;

interface ChannelInterface
{
    public function name();

    public function exec(Deposit $deposit);

    /**
     * @return boolean
     */
    public function callbackExists();
}
