<?php

namespace App\Http\Controllers;

use App\Lib\Wxpay;
use Illuminate\Http\Request;

class WechatController extends Controller {

    // 设置head
    protected $types = [
        1 => 'JSAPI支付',
        2 => '刷卡支付',
        3 => '扫码支付',
        4 => '订单查询',
        5 => '订单退款',
        6 => '退款查询',
        7 => '下载订单',
    ];

    public function home() {
        return view('home');
    }

    //jsapi支付页面
    public function jsapi() {
        $wxpay = new Wxpay;
        $data['jsApiParameters'] = $wxpay->jsapi()['jsApiParameters'];
        $data['type'] = $this->types[1];
        return view('jsapi', $data);
    }

    //刷卡支付页面
    public function micropay() {
        $data['type'] = $this->types[2];
        return view('micropay', $data);
    }

    //扫码支付页面
    public function native() {
        $data['mode'] = 1;
        $wxpay = new Wxpay;
        $data['url'] = $wxpay->native($data['mode']);
        $data['type'] = $this->types[3];
        return view('native', $data);
    }

    //订单查询页面
    public function refund() {
        $data['type'] = $this->types[4];
        return view('refund', $data);
    }

    //订单退款页面
    public function refundquery() {
        $data['type'] = $this->types[5];
        return view('refundquery', $data);
    }

    //订单查询页面
    public function order() {
        $data['type'] = $this->types[6];
        return view('orderquery', $data);
    }

    public function orderQuery(Request $request) {
        dd($request->all());
    }

    //下载订单页面
    public function download() {
        $data['type'] = $this->types[7];
        return view('refundquery', $data);
    }

    public function postMicropay(Request $request) {
        $wepay = new Wxpay;
        $res = $wepay->micropay($request->all());
        dd($res);
    }
}
