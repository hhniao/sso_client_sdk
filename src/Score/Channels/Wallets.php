<?php

namespace App\Services\Score\Channels;

use App\Models\UserWallet;
use App\Models\UserWalletLog;
use App\Services\Score\Deposit;
use Exception;
use Illuminate\Support\Facades\DB;

class Wallets extends ChannelBase implements ChannelInterface
{
    protected $name = '钱包';
    /**
     * @var UserWallet
     */
    private $wallet;

    public function name()
    {
        return $this->name;
    }

    public function init(Deposit $deposit)
    {
        $wallet = UserWallet::query()->where('currency', $deposit->currency)->first();

        if (!$wallet || $wallet->amount < $deposit->cashAmount * $wallet->exchangeRate()) {
            abort(400, '余额不足.');
        }
        $this->wallet = $wallet;
    }

    public function exec(Deposit $deposit)
    {

        DB::beginTransaction();
        try {
            $beforeAmount = $this->wallet->amount;
            $amount = $deposit->cashAmount * $this->wallet->exchangeRate();
            $this->wallet->amount -= $amount;
            $this->wallet->save();

            $log = new UserWalletLog();
            $log->amount = $amount;
            $log->order_no = $deposit->userScoreLog->order_no;
            $log->before_amount = $beforeAmount;
            $log->currency = $this->wallet->currency;
            $log->user_id = $this->wallet->user_id;
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
