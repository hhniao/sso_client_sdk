<?php

namespace App\Services\Score;

use App\Models\User;
use App\Models\UserScore;
use App\Models\UserScoreLog;
use App\Models\UserScoreRate;
use App\Services\Score\Channels\ChannelInterface;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Deposit
{

    private $channels = [
        'wallets' => '\\App\\Services\\Score\\Channels\\Wallets',
        'payment' => '\\App\\Services\\Score\\Channels\\Payment'
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
     * @var UserScoreLog
     */
    private $userScoreLog;

    public function __construct($channel, $cashAmount, $currency, $scoreCode, $channelData = [])
    {
        if (!isset($this->channels[$channel])) {
            abort(400, '渠道不支持');
        }
        $this->cashAmount = $cashAmount;
        $this->currency = $currency;
        $this->scoreCode = $scoreCode;
        $this->user = Auth::user();

        $class = $this->channels[$channel];
        $this->channel = new $class($channelData);

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
    }

    /**
     * 异步操作, 异步回调渠道
     * @return
     */
    public static function asyncAction($orderNo, $status)
    {
        $log = UserScoreLog::query()->where('order_no', $orderNo)->first();
        if (!$log) {
            return [false, '找不到记录'];
        }

        DB::beginTransaction();
        try {

            $log->status = $status;

            if ($status === 3) {
                $model = UserScore::query()->where('user_id', $log->user_id)
                    ->where('score_code', $log->score_code)->first();

                if (!$model) {
                    DB::rollBack();
                    return [false, '找不到记录'];
                }

                $model->amount += $log->amount;
                $model->save();
            }

            $log->save();
            DB::commit();
            return [true, ''];
        } catch (QueryException $e) {
            DB::rollBack();
            return [false, config('app.debug') ? $e->getMessage() : '系统错误'];
        }
    }

    /**
     * 现金充值, 即时到账.
     * @return array
     */
    public function exec()
    {
        $this->init();
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

    private function init()
    {
        $rate = UserScoreRate::query()->where('score_code', $this->scoreCode)
            ->where('currency', $this->currency)
            ->where('cash_min', '<=', $this->cashAmount)
            ->where('cash_max', '>', $this->cashAmount)
            ->first();

        if (!$rate) {
            abort(400, '未设置积分兑换比例');
        }
        $this->score = $rate->change_rate * $this->cashAmount;
        $this->channel->init($this);
    }

    private function log()
    {
        $model = new UserScoreLog();
        $model->user_id = $this->user->id;
        $model->score_code = $this->scoreCode;
        $model->amount = $this->score;
        $model->before_amount = $this->userScore->amount;
        $model->mark = '[购买-积分增加] ' . $this->channel->name() . '购买';
        $model->status = $this->channel->callbackExists() ? 1 : 3;
        $this->channel->setOrderNo($model);
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
