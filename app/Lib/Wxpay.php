<?php
namespace App\Lib;
require_once "WxPay/WxPay.Api.php";

require_once "WxPay/WxPay.Config.php";
require_once "WxPay.NativePay.php";
require_once "WxPay.MicroPay.php";
require_once "WxPay.JsApiPay.php";
require_once 'Log.php';
/**
 *
 */
class Wxpay {
    public function micropay($data) {
        /*$PNG_TEMP_DIR = getcwd() . '/logs/';
        if (!file_exists($PNG_TEMP_DIR)) {
        mkdir($PNG_TEMP_DIR);
        }
        $filename = $PNG_TEMP_DIR . date('Y-m-d') . '.log';
        //初始化日志
        $logHandler = new \CLogFileHandler($filename);
        $log = Log::Init($logHandler, 15);*/

        //打印输出数组信息
        /*function printf_info($data) {
        foreach ($data as $key => $value) {
        echo "<font color='#00ff55;'>$key</font> : $value <br/>";
        }
        }*/

        if (isset($data["auth_code"]) && $data["auth_code"] != "") {
            $auth_code = $data["auth_code"];
            $input = new \WxPayMicroPay();
            $input->SetAuth_code($auth_code);
            $input->SetBody("刷卡测试样例-支付");
            $input->SetTotal_fee("1");
            $input->SetOut_trade_no(\WxPayConfig::MCHID . date("YmdHis"));

            $microPay = new \MicroPay();
            //printf_info($microPay->pay($input));
            return $microPay->pay($input);
        }
    }

    public function jsapi() {
        //①、获取用户openid
        $tools = new \JsApiPay();
        $openId = $tools->GetOpenid();

        //②、统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody("test");
        $input->SetAttach("test");
        $input->SetOut_trade_no(WxPayConfig::MCHID . date("YmdHis"));
        $input->SetTotal_fee("1");
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("test");
        $input->SetNotify_url("http://paysdk.weixin.qq.com/example/notify.php");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = WxPayApi::unifiedOrder($input);
        echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
        printf_info($order);
        $jsApiParameters = $tools->GetJsApiParameters($order);

        //获取共享收货地址js函数参数
        $editAddress = $tools->GetEditAddressParameters();

        $data = ['jsApiParameters' => $jsApiParameters, 'editAddress' => $editAddress];
        return $data;
    }

    public function native($type) {
        if ($type == 1) {
            //模式一
            /**
             * 流程：
             * 1、组装包含支付信息的url，生成二维码
             * 2、用户扫描二维码，进行支付
             * 3、确定支付之后，微信服务器会回调预先配置的回调地址，在【微信开放平台-微信支付-支付配置】中进行配置
             * 4、在接到回调通知之后，用户进行统一下单支付，并返回支付信息以完成支付（见：native_notify.php）
             * 5、支付完成之后，微信服务器会通知支付成功
             * 6、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
             */
            $notify = new \NativePay();
            $url = $notify->GetPrePayUrl("123456789");

        } else {
            //模式二
            /**
             * 流程：
             * 1、调用统一下单，取得code_url，生成二维码
             * 2、用户扫描二维码，进行支付
             * 3、支付完成之后，微信服务器会通知支付成功
             * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
             */
            $input = new \WxPayUnifiedOrder();
            $input->SetBody("test");
            $input->SetAttach("test");
            $input->SetOut_trade_no(\WxPayConfig::MCHID . date("YmdHis"));
            $input->SetTotal_fee("1");
            $input->SetTime_start(date("YmdHis"));
            $input->SetTime_expire(date("YmdHis", time() + 3600));
            $input->SetGoods_tag("test");
            $input->SetNotify_url("http://paysdk.weixin.qq.com/example/notify.php");
            $input->SetTrade_type("NATIVE");
            $input->SetProduct_id("123456789");
            $result = $notify->GetPayUrl($input);
            if (array_key_exists('return_code', $result) &&
                array_key_exists('result_code', $result) &&
                $result['return_code'] == 'SUCCESS' &&
                $result['result_code'] == 'SUCCESS') {
                $url = $result["code_url"];
            }
        }

        return $url;
    }

    public function orderQuery($data) {
        if (isset($data["transaction_id"]) && $data["transaction_id"] != "") {
            $transaction_id = $data["transaction_id"];
            $input = new \WxPayOrderQuery();
            $input->SetTransaction_id($transaction_id);
            printf_info(\WxPayApi::orderQuery($input));
            exit();
        }

        if (isset($data["out_trade_no"]) && $data["out_trade_no"] != "") {
            $out_trade_no = $data["out_trade_no"];
            $input = new \WxPayOrderQuery();
            $input->SetOut_trade_no($out_trade_no);
            printf_info(\WxPayApi::orderQuery($input));
            exit();
        }
    }
}