<?php

namespace App\Services\Score;

use App\Models\User;
use App\Models\UserScore;
use App\Models\UserScoreLog;
use App\Models\UserScoreRate;
use App\Models\UserWallet;
use App\Services\Score\Channels\ChannelInterface;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Deposit
{

    private $channels = [
        'wallets'
    ];
    /**
     * 支付渠道
     * @var ChannelInterface
     */
    private $channel;
    /**
     * 现金金额, 单位元.
     * @var float
     */
    private $cashAmount;
    /**
     * 币种
     * @var string
     */
    private $currency;
    /**
     * 积分编码
     * @var string
     */
    private $scoreCode;

    /**
     * @var Authenticatable|User
     */
    private $user;

    /**
     * @var UserScore
     */
    private $userScore;
    /**
     * @var float|int
     */
    private $score;

    /**
     * @var UserWallet
     */
    private $wallet;

    /**
     * @var UserScoreLog
     */
    private $userScoreLog;

    public function __construct($channel, $cashAmount, $currency, $scoreCode)
    {
        if (!in_array($channel, $this->channels)) {
            abort(400, '渠道不支持');
        }
        $this->cashAmount = $cashAmount;
        $this->currency = $currency;
        $this->scoreCode = $scoreCode;
        $this->user = Auth::user();

        $class = '\\App\\Services\\Score\\Channels\\' . ucfirst($channel);
        $this->channel = new $class();
    }

    /**
     * 异步操作, 异步回调渠道
     * @return void
     */
    public static function asyncAction()
    {

    }

    public function exec()
    {
        $wallet = UserWallet::query()->where('currency', $this->currency)->first();

        if (!$wallet || $wallet->amount < $this->cashAmount * $wallet->exchangeRate()) {
            return [false, '余额不足.'];
        }
        $this->wallet = $wallet;
        $userScore = UserScore::query()->where('score_code', $this->scoreCode)->first();
        if (!$userScore) {
            $userScore = new UserScore();
            $userScore->user_id = $this->user->id;
            $userScore->score_code = $this->scoreCode;
            $userScore->amount = 0;
            $userScore->frozen_amount = 0;
            $userScore->save();
        }
        $this->userScore = $userScore;

        $rate = UserScoreRate::query()->where('score_code', $this->scoreCode)
            ->where('currency', $this->currency)
            ->where('cash_min', '<=', $this->cashAmount)
            ->where('cash_max', '>', $this->cashAmount)
            ->first();

        if (!$rate) {
            return [false, '未设置积分兑换比例'];
        }
        $this->score = $rate->change_rate * $this->cashAmount;

        DB::beginTransaction();

        try {

            $this->log();
            $this->channel->exec($this);
            if (!$this->channel->callbackExists()) {
                $this->syncAction();
            }
            DB::commit();
            return [true, '操作成功'];
        } catch (Exception $e) {
            DB::rollBack();
            return [false, config('app.debug') ? $e->getMessage() : '服务器错误.'];
        }
    }

    private function log()
    {
        $model = new UserScoreLog();
        $model->user_id = $this->user->id;
        $model->score_code = $this->scoreCode;
        $model->amount = $this->score;
        $model->before_amount = $this->userScore->amount;
        $model->mark = $this->channel->name() . '充值';
        $model->status = $this->channel->callbackExists() ? 1 : 3;
        $model->save();
        $this->userScoreLog = $model;
    }

    /**
     * 同步操作, 无异步回调渠道
     * @return void
     */
    private function syncAction()
    {
        $this->userScore->amount += $this->score;
        $this->userScore->save();
    }

    public function __get($key)
    {
        return $this->$key;
    }
}
