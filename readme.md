## 微信支付for PHP(laravel)

## 流程


- **[接入微信扫码支付](https://pay.weixin.qq.com/guide/qrcode_payment.shtml)**
- **[选择扫码支付模式](https://pay.weixin.qq.com/wiki/doc/api/native.php?chapter=6_3)**
- **[微信扫码支付模式二](https://pay.weixin.qq.com/wiki/doc/api/native.php?chapter=6_5)**


## 示例


创建商户订单
```php
public function recharge(Request $request, $user_id) {
        $validator = Validator::make($request->all(), [
            'total_amount' => 'required',
            'payment_type' => 'required',
        ]);

        if ($validator->fails()) {
            return StarShowResponse::response($validator->errors()->first(), 422);
        }
        $total_amount = $request->input('total_amount');
        $payment_type = $request->input('payment_type');
        //账户余额

        $account_balance = DspUser::find($user_id)->account;

        //->select("select * from users where user_id=" . $user_id);
        $out_trade_no = date('YmdHis') . rand(0, 9);
        $account = DspAccount::create([
            'user_id' => $user_id,
            'payment_type' => $payment_type,
            'account_balance' => $account_balance,
            'name' => 'DSP账户充值',
            'out_trade_no' => $out_trade_no,
            'total_amount' => $total_amount,
        ]);
}
```

统一下单API
```php
    $params = array(
        'appid' => config('wechat.app_id'),
        'mch_id' => config('wechat.mch_id'),
        'nonce_str' => str_random(16),
        'body' => '时尚星秀-DSP充值',
        'out_trade_no' => $out_trade_no,
        'total_fee' => $total_amount * 100, //微信支付单位为分
        'time_start' => date("YmdHis"),
        'time_expire' => date("YmdHis", time() + 300),
        'spbill_create_ip' => $_SERVER['SERVER_ADDR'],
        'notify_url' => config('weixin.dsp_notify_url'),
        'trade_type' => 'NATIVE',
        'product_id' => $out_trade_no,
    );
        Log::info('订单失效时间' . $params['time_expire']);
        $params['sign'] = $this->wechat->getSign($params, true);
        $rst = $this->wechat->curl_post_ssl('https://api.mch.weixin.qq.com/pay/unifiedorder', $this->wechat->arrayToXml($params));
        $result = $this->wechat->xmlToArray($rst);
        if (array_key_exists("return_code", $result) &&
            array_key_exists("result_code", $result) &&
            $result["return_code"] == "SUCCESS" &&
            $result["result_code"] == "SUCCESS") {
            $url = $result["code_url"];
            $code_url = $this->qrcode->generate($url);
            Log::info('二维码' . env('APP_HOST') . $code_url);
            $data = [
                'code_url' => env('APP_HOST') . $code_url,
                'out_trade_no' => $out_trade_no,
                'time_expire' => date('Y-m-d H:i:s', strtotime($params['time_expire'])),
            ];
            return StarShowResponse::response($data, 200);
        }
```

同步验证
```php
        public function webReturn(Request $request) {
            Log::info('wechatpay return verify' . json_encode($request->all()));
            //业务逻辑验证
            if (!array_key_exists("out_trade_no", $request->all())) {
                return StarShowResponse::response("请输入商户订单号", 500);
            }
            $trade = DspAccount::where('out_trade_no', $request->input('out_trade_no'))->first();

            if (empty($trade)) {
                return StarShowResponse::response('DSP充值记录不存在', 500);
            }
            if ($trade->status == 1) {
                return StarShowResponse::response('已经支付成功', 200);
            }
            //查询订单，判断订单真实性
            if (!$this->query(array('out_trade_no' => $request->input('out_trade_no')))) {
                return StarShowResponse::response(date('Y-m-d H:i:s'), 500);
            } else {
                if ($trade->status == 0) {
                    $trade->status = 1;
                    $trade->save();
                }
                return StarShowResponse::response('支付成功', 200);
            }
    }
```

异步回调
```php
    public function wxPayNotify(Request $request) {
        if (!$this->handleNotify($request)) {
            return response('账户充值失败', 500);
        }
        $response = $this->wechat->arrayToXml(array(
            'return_code' => 'SUCCESS',
        ));
        return response($response, 200)->header('Content-Type', 'application/xml');
    }
    public function handleNotify($request) {
            Log::info('wechat native notify verify' . $request->getContent());
            $content = $this->wechat->xmlToArray($request->getContent());
            if (!isset($content['return_code'])) {
                Log::info('支付结果通知失败');
                return false;
            }

            if (!array_key_exists("return_code", $content) &&
                !array_key_exists("result_code", $content) &&
                !$content["return_code"] == "SUCCESS" &&
                !$content["result_code"] == "SUCCESS") {
                return false;
            }

            if (array_key_exists("return_msg", $content) &&
                $content['return_code'] == 'FAIL') {
                Log::info('支付结果返回失败:' . $content['return_msg']);
                return false;
            }
            if ($content['result_code'] == 'FAIL') {
                Log::info('支付失败:' . $content['err_code'] . ':' . $content['err_code_des']);
                return false;
            }
            DB::beginTransaction();
            try{
                $trading = DspAccount::where('out_trade_no', $content['out_trade_no'])->first();
                $user = DspUser::find($trading->user_id);
                $trading->status = 1;
                $trading->total_amount = $content['total_fee'];
                $trading->account_balance = $user->account + $content['total_fee'];
                $trading->trade_no = $content['transaction_id'];
                $trading->success_at = date('Y-m-d H:i:s', strtotime($content['time_end']));
                $trading->save();

                $user->account = $user->account + $content['total_fee'];
                $res = $user->save();

                DB::commit();
                return $res;
            }catch (\Exception $e) {
                Log::info('充值失败'.$e);
                DB::rollback();
                return false;
        }
    }
```



