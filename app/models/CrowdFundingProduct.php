<?php
/**
*
*/
class CrowdFundingProduct extends Eloquent
{
    public $primaryKey = 'p_id';
    public $timestamps = false;

    private function baseValidate()
    {
        $validator = Validator::make(
            ['user' => $this->u_id, 'booth' => $this->b_id],
            ['user' => 'required', 'booth' => 'required']
        );
        if ($validator->fails()) {
            $msg = $validator->messages();
            throw new Exception($msg->first(), 1);
        } else {
            return true;
        }
    }

    public function getPercentage()
    {
        if ($this->p_target_quantity == 0) {
            $re = 0;
        } else {
            $re = ($this->p_sold_quantity / $this->p_target_quantity) * 100;
        }
        return number_format($re, 2);
    }

    public function addProduct()
    {
        $this->baseValidate();
        $now = Tools::getNow();
        $this->created_at = $now;
        $this->p_status = 1;
        return $this->save();
    }

    public function loadProduct($quantity)
    {
        $up = false;
        $limit = false;
        $re = false;
        if ($this->p_max_quantity > 0) {
            $remain = $this->p_max_quantity - $this->p_sold_quantity - $this->p_cart_quantity;
            if ($quantity <= $remain) {
                $up = true;
                $limit = true;
            } else {
                // retrive stock
                $remain = $this->retriveStock();
                if ($quantity > $remain) {
                    throw new Exception("库存不足", 7006);
                } else {
                    $up = true;
                }
            }
        } else {
            $up = true;
        }
        $query = DB::table('crowd_funding_products')->where('p_id', '=', $this->p_id);
        if ($limit) {
            $query->where('p_max_quantity', '>=', '(p_sold_quantity + p_cart_quantity + '.$quantity.')');
        }
        if ($up) {
            $re = $query->increment('p_cart_quantity', $quantity);
            $this->p_cart_quantity += $quantity;
        } else {
            throw new Exception("修改库存失败", 7001);
        }
        return $re;
    }

    public function unloadProduct($quantity)
    {
        $down = false;
        $re = false;
        if ($quantity > $this->p_cart_quantity) {
            throw new Exception("最多还能退".$this->p_cart_quantity.'份', 7001);
        } else {
            $down = true;
        }
        $re = DB::table('crowd_funding_products')->where('p_id', '=', $this->p_id)->where('p_cart_quantity', '<=', $quantity)->decrement('p_cart_quantity', $quantity);
        $this->p_cart_quantity -= $quantity;
        return $re;
    }

    public function confirmProduct($quantity)
    {
        DB::table('crowd_funding_products')->where('p_id', '=', $this->p_id)->lockForUpdate()->decrement('p_cart_quantity', $quantity);
        DB::table('crowd_funding_products')->where('p_id', '=', $this->p_id)->lockForUpdate()->increment('p_sold_quantity', $quantity);
        $this->p_cart_quantity -= $quantity;
        $this->p_sold_quantity += $quantity;
    }

    public function retriveStock()
    {
        $carts = Cart::with(['order'])->where('p_id', '=', $this->p_id)->where('c_type', '=', 2)
        ->where(function ($q) {
            $q->where('c_status', '=', Cart::$STATUS_PENDDING_CONFIRM)->orWhere('c_status', '=', Cart::$STATUS_PENDDING_PAY);
        })->get();
        $orders = [];
        foreach ($carts as $key => $cart) {
            if (!empty($cart->order) && $cart->order->o_status == 1) {
                $orders[$cart->order->o_id] = $cart->order;
                $this->unloadProduct($cart->c_quantity);
                $cart->c_status = -1;
                $cart->save();
            }
        }
        if (!empty($orders)) {
            foreach ($orders as $key => $order) {
                $order->o_status = -1;
                $order->o_remark = '超时未支付, 自动回收订单';
                $order->save();
            }
        }
        $this->save();
        $remain = $this->p_max_quantity - $this->p_sold_quantity - $this->p_cart_quantity;
        return $remain;
    }
    // relation
}
