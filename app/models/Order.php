<?php
/**
*
*/
class Order extends Eloquent
{
    public $primaryKey = 'o_id';
    public $timestamps = false;

    public static $SHIPPING_STATUS_PREPARE = 1;
    public static $SHIPPING_STATUS_DELIVERING = 5;
    public static $SHIPPING_STATUS_FINISHED = 10;

    public static $STATUS_INVALIDE = 0;
    public static $STATUS_UNFINISHED = 1;
    public static $STATUS_FINISHED = 2;
    public static $STATUS_PACKED = 3;
    public static $STATUS_SHIPPED = 4;
    public static $STATUS_ORDERED = 5;
    public static $STATUS_PAIED = 6;

    private $_carts = [];
    private $_bills = [];

    private function baseValidate()
    {
        $validator = Validator::make(
            ['user' => $this->u_id, 'amount_origin' => $this->o_amount_origin, 'amount' => $this->o_amount, 'shpping_name' => $this->o_shipping_name, 'shpping_phone' => $this->o_shipping_phone, 'shpping_address' => $this->o_shipping_address, 'delivery_time' => $this->o_delivery_time, 'shpping' => $this->o_shipping, 'payment' => $this->o_payment, 'number' => $this->o_number],
            ['user' => 'required', 'amount_origin' => 'required', 'amount' => 'required', 'shpping_name' => 'required', 'shpping_phone' => 'required', 'shpping_address' => 'required', 'delivery_time' => 'required', 'shpping' => 'required', 'payment' => 'sometime', 'number' => 'required']
        );
        if ($validator->fails()) {
            $msg = $validator->messages();
            throw new Exception($msg->first(), 1);
        } else {
            return true;
        }
    }

    public function showInList()
    {
        $data = [];
        return $data;
    }

    public function showDetail($mask_status = false)
    {
        $data = [];
        $data['id'] = $this->o_id;
        $data['amount_origin'] = $this->o_amount_origin;
        $data['amount'] = $this->o_amount;
        $data['created_at'] = $this->created_at->format('Y-m-d H:i:s');
        $data['number'] = $this->o_number;
        $data['shipping_address'] = $this->o_shipping_address;
        $data['shipping_phone'] = $this->o_shipping_phone;
        $data['shipping_name'] = $this->o_shipping_name;

        if ($mask_status) {
            $data['status'] = $this->mapOrderStatus('all');
        } else {
            $data['status'] = $this->o_status;
            $data['shipping_status'] = $this->o_shipping_status;
        }

        if (!empty($this->carts)) {
            $carts = [];
            foreach ($this->carts as $key => $cart) {
                $carts[] = $cart->showInList();
            }
            $data['carts'] = $carts;
        }
        return $data;
    }

    public function mapOrderStatus($mask)
    {
        $status = $this->o_status.'.'.$this->o_shipping_status.'.'.$mask;
        switch ($status) {
            case '2.10.all':
            case '2.10.order':
            case '2.10.shipping':
                return Order::$STATUS_FINISHED;
                break;
            case '1.1.order':
            case '1.5.order':
            case '1.10.order':
            case '1.1.all':
                return Order::$STATUS_ORDERED;
                break;
            case '2.1.order':
            case '2.5.order':
            case '2.1.all':
                return Order::$STATUS_PAIED;
                break;
            case '1.1.shipping':
            case '2.1.shipping':
                return Order::$STATUS_PACKED;
                break;
            case '1.5.shipping':
            case '2.5.shipping':
            case '2.5.all':
                return Order::$STATUS_SHIPPED;
                break;
            case '1.1.all':
            case '1.5.all':
            case '1.10.all':
            case '2.1.all':
            case '2.5.all':
                return Order::$STATUS_UNFINISHED;
                break;
            default:
                return Order::$STATUS_INVALIDE;
                break;
        }
    }

    public function addOrder()
    {
        $now = new DateTime();
        $this->o_status = 1;
        $this->o_shipping_status = Order::$SHIPPING_STATUS_PREPARE;
        $this->created_at = $now->format('Y-m-d H:i:s');
        $this->save();
        return $this->o_id;
    }

    public static function generateOrderGroupNo($u_id)
    {
        $order_no = Tools::generateDateUserRandomNo($u_id);
        $chk = Order::where('o_number', '=', $order_no)->first();
        $count = 0;
        while (!empty($chk)) {
            $order_no = Tools::generateDateUserRandomNo($u_id);
            $chk = Order::where('o_number', '=', $order_no)->first();
            $count++;
            if ($count >= 10) {
                throw new Exception("无法生成订单号", 9001);
                break;
            }
        }
        return $order_no;
    }

    public static function updateShippingStatus($order_ids, $status)
    {
        if (!is_array($order_ids) || empty($order_ids)) {
            throw new Exception("无效的订单数据", 9002);
        }
        $re = Order::whereIn('o_id', $order_ids)->update(['o_shipping_status' => $status]);
        return $re;
    }

    public function pay($payment_type)
    {
        if ($this->o_status >= 2) {
            return true;
        }
        $now = new DateTime();
        $this->o_status = 2;
        $this->paied_at = $now->format('Y-m-d H:i:s');
        $this->o_payment = $payment_type;
        if (!$this->save()) {
            throw new Exception("结算订单失败", 9004);
        }
        return true;
    }

    public function getSummary()
    {
        $this->_carts = Cart::select('carts.*')->join('orders', function ($q) {
            $q->on('carts.o_id', '=', 'orders.o_id')
            ->where('orders.o_group_number', '=', $this->o_group_number);
        })
        ->where('carts.c_status', '<>', -1)
        ->get();
        foreach ($this->_carts as $key => $cart) {
            if (empty($this->_bills[$cart->b_id])) {
                if ($cart->c_status == 3) {
                    $this->_bills[$cart->b_id]['total']['paied'] = $cart->c_amount;
                    $this->_bills[$cart->b_id]['total_origin']['paied'] = $cart->c_amount_origin;
                } else {
                    $this->_bills[$cart->b_id]['total']['pending'] = $cart->c_amount;
                    $this->_bills[$cart->b_id]['total_origin']['pending'] = $cart->c_amount_origin;
                }
            } else {
                if ($cart->c_status == 3) {
                    $this->_bills[$cart->b_id]['total']['paied'] += $cart->c_amount;
                    $this->_bills[$cart->b_id]['total_origin']['paied'] += $cart->c_amount_origin;
                } else {
                    $this->_bills[$cart->b_id]['total']['pending'] += $cart->c_amount;
                    $this->_bills[$cart->b_id]['total_origin']['pending'] += $cart->c_amount_origin;
                }
            }
        }
    }

    public function confirm()
    {
        $this->getSummary();
        foreach ($this->_bills as $key => $bill) {
            $booth = Booth::find($key);
            $wallet = UsersWalletBalances::find($booth->u_id);
            if ($booth->b_with_fund) {
                $fund = Fund::where('b_id', '=', $key)->where('t_is_close', '=', 0)->first();
                if (empty($fund)) {
                    $wallet->putIn($bill['total']['paied']);
                }
            } else {
                $wallet->putIn($bill['total']['paied']);
            }
        }
        $this->o_shipping_status = 10;
        return $this->save();
    }

    public function checkoutCarts()
    {
        $this->getSummary();
        foreach ($this->_carts as $key => $cart) {
            if ($cart->c_status == 2) {
                $cart->checkout();
            }
        }
    }

    public function cancelOrder($remark)
    {
        $this->getSummary();
        foreach ($this->_carts as $key => $cart) {
            if ($cart->c_status == 2) {
                $cart->cancelCart();
            }
        }
        $this->o_status = -1;
        $this->o_remark = $remark;
        $this->save();
        return true;
    }

    public static function sumIncome($to = null, $from = null, $b_id = null, $u_id = null, $owner_id = null)
    {
        $query = Order::sum('o_amount');
        if ($from) {
            $query = $query->where('created_at', '>', $from);
        }
        if ($to) {
            $query = $query->where('created_at', '<', $to);
        }
        if ($b_id) {
            $query = $query->where('b_id', '=', $b_id);
        }
    }

    public static function getGroupOrdersByNo($group_no)
    {
        $orders = Order::where('o_group_number', '=', $group_no)->get();
        if (count($orders) == 0) {
            throw new Exception("没有找到订单", 9002);
        }
        return $orders;
    }

    public static function getOrderByNo($no)
    {
        $order = Order::where('o_number', '=', $no)->get();
        if (count($order) > 1) {
            throw new Exception("订单数据有误", 9010);
        }
        $order = $order->first();
        if (empty($order)) {
            throw new Exception("没有找到订单", 9002);
        }
        return $order;
    }

    // laravel relation
    
    public function carts()
    {
        return $this->hasMany('Cart', 'o_id', 'o_id');
    }
}
