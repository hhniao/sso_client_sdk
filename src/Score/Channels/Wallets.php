<?php

namespace App\Services\Score\Channels;

use App\Models\UserWalletLog;
use App\Services\Score\Deposit;
use Exception;
use Illuminate\Support\Facades\DB;

class Wallets implements ChannelInterface
{
    private $name = '钱包';

    public function name()
    {
        return $this->name;
    }

    public function exec(Deposit $deposit)
    {
        DB::beginTransaction();
        try {
            $beforeAmount = $deposit->wallet->amount;
            $amount = $deposit->cashAmount * $deposit->wallet->exchangeRate();
            $deposit->wallet->amount -= $amount;
            $deposit->wallet->save();

            $log = new UserWalletLog();
            $log->amount = $amount;
            $log->order_no = $deposit->userScoreLog->order_no;
            $log->before_amount = $beforeAmount;
            $log->currency = $deposit->wallet->currency;
            $log->user_id = $deposit->wallet->user_id;
            $log->status = 3;
            $log->mark = '余额充值积分';
            $log->save();
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }


    public function callbackExists()
    {
        return false;
    }
}
