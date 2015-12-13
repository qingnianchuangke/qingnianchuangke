<?php
/**
*
*/
class Cart extends Eloquent
{
    public $primaryKey = 'c_id';
    public $timestamps = false;

    public static $STATUS_DELETED = -1;
    public static $STATUS_INVALID = 0;
    public static $STATUS_PENDDING_CONFIRM = 1;
    public static $STATUS_PENDDING_PAY = 2;
    public static $STATUS_PAIED = 3;

    public static $TYPE_REGULAR_PRODUCT = 1;
    public static $TYPE_CROWD_FUNDING = 2;
    public static $TYPE_FLEA_PRODUCT = 3;
    public static $TYPE_AUCTION = 4;

    private $_quntityOri = 0;

    private function baseValidate()
    {
        $validator = Validator::make(
            ['booth' => $this->b_id, 'user' => $this->u_id, 'product' => $this->p_id, 'price' => $this->c_price_origin, 'quntity' => $this->c_quantity, 'discount' => $this->c_discount],
            ['booth' => 'required', 'user' => 'required', 'product' => 'required', 'price' => 'required', 'quntity' => 'required', 'discount' => 'required']
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
        $data['id'] = $this->c_id;
        $data['prod_name'] = $this->p_name;
        $data['prod_img'] = $this->p_img;
        $data['price_origin'] = $this->c_price_origin;
        $data['price'] = $this->c_price;
        $data['discount'] = $this->c_discount;
        $data['quntity'] = $this->c_quantity;
        $data['status'] = $this->c_status;

        if (!empty($this->product)) {
            $data['product'] = $this->product->showInList();
        }

        if (!empty($this->booth)) {
            $data['booth'] = $this->booth->showInList();
        }
        return $data;
    }

    public function addCart()
    {
        $now = new DateTime();
        $this->loadProduct();
        $this->baseValidate();
        $this->c_status = 1;
        $this->created_at = $now->format('Y-m-d H:i:s');
        $this->save();
        return $this->c_id;
    }

    public function updateCart($quntityOri)
    {
        $this->_quntityOri = $quntityOri;
        $this->loadProduct();
        return $this->save();
    }

    public function removeCart()
    {
        $this->unloadProduct();
        return $this->delete();
    }

    public function cancelCart()
    {
        $this->unloadProduct();
        $this->c_status = -1;
        return $this->save();
    }

    public function loadProduct()
    {
        if (!$this->p_id || !$this->c_quantity) {
            throw new Exception("购买数量不能为0", 7001);
        }
        $product = Product::find($this->p_id);
        if (empty($product->p_id)) {
            throw new Exception("没有获取到产品", 7001);
        }

        // caculate quantity
        $product->load('quantity');
        if (empty($product->quantity->q_id)) {
            throw new Exception("没有获取到产品库存信息", 7001);
        }
        $total = $product->quantity->q_total;
        $sold = $product->quantity->q_sold;
        $inCart = Cart::where('c_status', '=', 1)->where('p_id', '=', $this->p_id)->sum('c_quantity');
        $remain = (int)$total - (int)$sold - (int)$inCart + $this->_quntityOri;
        if ($this->c_quantity > $remain) {
            throw new Exception("产品库存不足", 7006);
        }
        $product->quantity->q_cart = $inCart + $this->c_quantity - $this->_quntityOri;
        $product->quantity->save();
        $this->c_price_origin = $product->p_price_origin;
        $this->c_discount = $product->p_discount;
        $this->c_price = $this->c_price_origin * $this->c_discount / 100;
        $this->c_amount_origin = $this->c_price_origin * $this->c_quantity;
        $this->c_amount = $this->c_amount_origin * $this->c_discount / 100;

        $this->p_name = $product->p_title;

        $imgs = Img::toArray($product->p_imgs);
        $this->p_img = array_pop($imgs);
    }

    public function unloadProduct()
    {
        if (!$this->p_id) {
            throw new Exception("该购物车没有绑定产品", 7001);
        }
        $product = Product::find($this->p_id);
        if (empty($product->p_id)) {
            throw new Exception("没有获取到产品", 7001);
        }

        // caculate quantity
        $product->load('quantity');
        if (empty($product->quantity->q_id)) {
            throw new Exception("没有获取到产品库存信息", 7001);
        }
        $product->quantity->q_cart -= $this->c_quantity;
        if ($product->quantity->q_cart < 0) {
            $product->quantity->q_cart = 0;
        }
        $product->quantity->save();
    }

    public static function bindOrder($order_ids)
    {
        if (empty($order_ids)) {
            throw new Exception("购物车数据无效", 7001);
        }

        foreach ($order_ids as $o_id => $carts) {
            Cart::whereIn('c_id', $carts)->update(['o_id' => $o_id, 'c_status' => 2]);
        }

        return true;
    }

    public function checkout()
    {
        $now = new DateTime();
        $this->checkout_at = $now->format('Y-m-d H:i:s');
        $this->checkoutCrowdFunding();
        $this->checkoutAuction();
        $this->c_status = 3;
        if (!$this->save()) {
            throw new Exception("结算购物车失败", 9005);
        }
        return true;
    }

    public function checkoutCrowdFunding()
    {
        if ($this->c_type != 2) {
            return true;
        }

        // push msg to seller
        $booth = Booth::find($this->b_id);

        $product = CrowdFundingProduct::find($this->p_id);
        $product->confirmProduct($this->c_quantity);
        $funding = CrowdFunding::find($product->cf_id);
        $funding->c_amount += $this->c_amount;
        $funding->save();

        $msg = new MessageDispatcher($booth->u_id);
        $msg->fireCateToUser('您的众筹'.$funding->c_title.'已有人认购', 1, $funding->cf_id);

        return true;
    }

    public function checkoutAuction()
    {
        if ($this->c_type != 4) {
            return true;
        }
        $auction = Auction::find($this->p_id);
        $auction->a_status = 3;
        $bid = AuctionBid::find($auction->a_win_id);
        $bid->is_pay = 1;
        $auction->save();
        $bid->save();

        return true;
    }

    public static function getCartTypeCount($type, $type_id)
    {
        if (!$type) {
            throw new Exception("请传入有效的cart类型", 1);
        }
        $count = Cart::where('c_type', '=', $type)->where('p_id', '=', $type_id)->count();
        if (!$count) {
            $count = 0;
        }
        return $count;
    }

    public static function sumIncome($from = null, $to = null, $b_id = null, $u_id = null, $owner_id = null)
    {
        $query = Cart::where('c_status', '=', 3);
        if ($from) {
            $query = $query->where('checkout_at', '>', $from);
        }
        if ($to) {
            $query = $query->where('checkout_at', '<', $to);
        }
        if ($b_id) {
            $query = $query->where('b_id', '=', $b_id);
        }
        if ($u_id) {
            $query = $query->where('u_id', '=', $u_id);
        }
        $amount = $query->sum('c_amount');
        return $amount;
    }

    // laravel relation

    public function user()
    {
        return $this->belongsTo('User', 'u_id', 'u_id');
    }

    public function product()
    {
        return $this->hasOne('Product', 'p_id', 'p_id');
    }

    public function booth()
    {
        return $this->hasOne('Booth', 'b_id', 'b_id');
    }

    public function order()
    {
        return $this->belongsTo('Order', 'o_id', 'o_id');
    }
}
