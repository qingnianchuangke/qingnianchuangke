<?php
/**
*
*/
class PayController extends \BaseController
{
    public function callbackAlipay()
    {
        $order_no = Input::get('out_trade_no', 0);
        $ali_trade_no = Input::get('trade_no', '');
        $ali_trade_status = Input::get('trade_status', '');
        $amount = Input::get('total_fee', 0);
        $via_id = Input::get('buyer_id', '');
        $refund_account = Input::get('buyer_email', '');

        DB::beginTransaction();
        try {
            $alipay = new Alipay();
            $alipay->verifyNotify();
            $transaction_id = $ali_trade_no;
            $orders = Order::getGroupOrdersByNo($order_no);
            if ($ali_trade_status == 'TRADE_FINISHED' || $ali_trade_status == 'TRADE_SUCCESS') {
                $u_id = 0;
                foreach ($orders as $key => $order) {
                    $u_id = $order->u_id;
                    $o_id = $order->o_id;
                    $order->pay(Alipay::PAYMENT_TAG);
                    $order->checkoutCarts();
                }
                $cart = Cart::where('o_id', '=', $o_id)->where('c_status', '<>', 0)->first();
                if ($cart->c_type == Cart::$TYPE_REGULAR_PRODUCT || $cart->c_type == Cart::$TYPE_FLEA_PRODUCT) {
                    $log_cate = LogTransaction::$CATE_PRODUCT;
                } elseif ($cart->c_type == Cart::$TYPE_CROWD_FUNDING) {
                    $log_cate = LogTransaction::$CATE_CROWDFUNDING;
                } elseif ($cart->c_type == Cart::$TYPE_AUCTION) {
                    $log_cate = LogTransaction::$CATE_AUCTION;
                } else {
                    $log_cate = 0;
                }
                //add transaction log
                $log = new LogTransaction();
                $log->l_type = LogTransaction::$TYPE_TRADE;
                $log->l_cate = $log_cate;
                $log->l_amt = $amount;
                $log->from_type = LogTransaction::$OPERATOR_USER;
                $log->from_id = $u_id;
                $log->to_type = LogTransaction::$OPERATOR_QNCK;
                $log->to_id = 1;
                $log->via_type = LogTransaction::$PAYMENT_ALIPAY;
                $log->via_id = $via_id;
                $log->transaction_id = $transaction_id;
                $log->addLog();
                if (empty($log->l_id)) {
                    throw new Exception("添加交易记录失败", 2001);
                }
                $log_order = new LogTransactionOrders();
                $log_order->l_id = $log->l_id;
                $log_order->o_group_number = $order_no;
                $log_order->refund_account = $refund_account;
                $log_order->save();
            }
            DB::commit();
            echo "success";
            die();
        } catch (Exception $e) {
            DB::rollback();
            echo "fail";
            die();
        }
        exit;
    }

    public function callbackWechat()
    {
        DB::beginTransaction();
        try {
            $wechat = new WechatPay();
            $wechat->log->INFO('callback start');
            $input = file_get_contents('php://input', 'r');
            $wechat->log->INFO('POSTED DATE FRON WX SERVER:'.$input);

            $re = $wechat->verifyNotify();
            $transaction_id = $re['transaction_id'];
            $re['total_fee'] = $re['total_fee'] * 0.01;
            $orders = Order::getGroupOrdersByNo($re['out_trade_no']);
            foreach ($orders as $key => $order) {
                $u_id = $order->u_id;
                $o_id = $order->o_id;
                $order->pay(WechatPay::PAYMENT_TAG);
                $order->checkoutCarts();
            }
            $wechat->_notify->SetReturn_code('SUCCESS');
            $wechat->_notify->SetReturn_msg('OK');

            $cart = Cart::where('o_id', '=', $o_id)->where('c_status', '<>', 0)->first();
            if ($cart->c_type == Cart::$TYPE_REGULAR_PRODUCT || $cart->c_type == Cart::$TYPE_FLEA_PRODUCT) {
                $log_cate = LogTransaction::$CATE_PRODUCT;
            } elseif ($cart->c_type == Cart::$TYPE_CROWD_FUNDING) {
                $log_cate = LogTransaction::$CATE_CROWDFUNDING;
            } elseif ($cart->c_type == Cart::$TYPE_AUCTION) {
                $log_cate = LogTransaction::$CATE_AUCTION;
            } else {
                $log_cate = 0;
            }
            //add transaction log
            $log = new LogTransaction();
            $log->l_type = LogTransaction::$TYPE_TRADE;
            $log->l_cate = $log_cate;
            $log->l_amt = $re['total_fee'];
            $log->from_type = LogTransaction::$OPERATOR_USER;
            $log->from_id = $u_id;
            $log->to_type = LogTransaction::$OPERATOR_QNCK;
            $log->to_id = 1;
            $log->via_type = LogTransaction::$PAYMENT_WECHAT;
            $log->transaction_id = $transaction_id;
            $log->addLog();
            if (empty($log->l_id)) {
                throw new Exception("添加交易记录失败", 2001);
            }
            $log_order = new LogTransactionOrders();
            $log_order->l_id = $log->l_id;
            $log_order->o_group_number = $re['out_trade_no'];
            $log_order->save();
            DB::commit();
        } catch (Exception $e) {
            $wechat->log->ERROR('exeception caught:'.$e->getMessage());
            $wechat->_notify->SetReturn_code('FAIL');
            $wechat->_notify->SetReturn_msg($e->getMessage());
            DB::rollback();
        }
        $re = $wechat->_notify->ToXml();
        $wechat->log->INFO('XML:'.$re);
        WxpayApi::replyNotify($re);
        exit;
    }

    public function wechatPayPreOrder()
    {
        $order_no = Input::get('order_no', '');
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $order = Order::getOrderByNo($order_no);
            $product_names = Cart::where('o_id', '=', $order->o_id)->lists('p_name');
            $wechat = new WechatPay();
            $body = $product_names[0].'等商品';
            $params = [
                'out_trade_no' => $order_no,
                'total_fee' => $order->o_amount,
                'body' => $body,
                'detail' => implode(',', $product_names),
            ];
            $re = $wechat->preOrder($params);
            $re = Tools::reTrue('微信预支付成功', $re);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '微信预支付失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function payFailed()
    {
        $order_no = Input::get('order_no', '');
        $u_id = Input::get('u_id', '');
        $token = Input::get('token', '');

        DB::beginTransaction();
        try {
            $orders = Order::getGroupOrdersByNo($order_no);
            foreach ($orders as $key => $order) {
                $carts = Cart::where('o_id', '=', $order->o_id)->get();
                foreach ($carts as $cart) {
                    $cart->delete();
                }
                $order->delete();
            }
            $re = Tools::reTrue('取消成功');
            DB::commit();
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '取消失败:'.$e->getMessage());
            DB::rollback();
        }
        return Response::json($re);
    }
}
