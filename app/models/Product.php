<?php
/**
*
*/
class Product extends Eloquent
{
    public $primaryKey = 'p_id';
    public $timestamps = false;

    private $_imgs = [];

    private function baseValidate()
    {
        $validator = Validator::make(
            ['booth' => $this->b_id, 'title' => $this->p_title, 'user' => $this->u_id, 'cost' => $this->p_cost, 'price' => $this->p_price, 'desc' => $this->p_desc],
            ['booth' => 'required', 'title' => 'required', 'user' => 'required', 'cost' => 'required', 'price' => 'required', 'desc' => 'required']
        );
        if ($validator->fails()) {
            $msg = $validator->messages();
            throw new Exception($msg->first(), 1);
        } else {
            return true;
        }
    }

    public static function getProductCate($type)
    {
        $re = [];
        switch ($type) {
            // regular product
            case 1:
                $re = [
                '1' => '校创精品',
                '2' => '精神世界',
                '3' => '品质生活',
                '4' => '娱乐科技',
                '5' => '运动健康',
                '6' => '校创服务',
                '7' => '其他',
                ];
                break;
            // second hand product
            case 2:
                $re = [
                '1' => '家电数码',
                '2' => '鞋包服饰',
                '3' => '书籍影音',
                '4' => '创意手工',
                '5' => '运动出行',
                '6' => '虚拟商品',
                '7' => '其它',
                '8' => '赏金令',
                ];
                break;
            
            default:
                # code...
                break;
        }
        return $re;
    }

    public function showInList()
    {
        $this->loadImgs();
        $data = null;
        $data['id'] = $this->p_id;
        $data['title'] = $this->p_title;
        $data['brief'] = $this->p_brief;
        $data['cover_img'] = Img::filterKey('cover_img', $this->_imgs);
        $data['cover_img'] = Tools::checkNoImg($data['cover_img']);
        $data['imgs'] = Img::filterKey('prod_img_', $this->_imgs, true);
        if (!$data['imgs']) {
            $data['imgs'] = null;
        }
        $data['content'] = $this->getContent();
        $data['price_origin'] = $this->p_price_origin;
        $data['price'] = $this->p_price;
        $data['discount'] = $this->p_discount;
        $data['sort'] = $this->sort;
        $data['reply_count'] = $this->p_reply_count;
        $data['status'] = $this->p_status;
        $data['remark'] = $this->p_remark;
        $data['praise_count'] = $this->p_praise_count;
        $data['cate'] = $this->p_cate;
        $data['active_at'] = $this->active_at;
        $date = new DateTime($this->created_at);
        $data['created_at'] = $date->format('Y-m-d H:i:s');
        $data['created_at_timestamps'] = strtotime($data['created_at']);
        $data['cate_label'] = $this->getCateLabel();

        if (!empty($this->quantity)) {
            $quantity = $this->quantity->showInList();
            $data['quantity'] = $quantity;
        }
        if (!empty($this->promo)) {
            $promo = $this->promo->showInList();
            $data['promo'] = $promo;
        }
        if (!empty($this->booth)) {
            $data['booth'] = $this->booth->showInList();
        }

        if (!empty($this->user)) {
            $data['user'] = $this->user->showDetail();
        }

        return $data;
    }

    public function showDetail()
    {
        $this->loadImgs();
        $data = null;
        $data['prod_name'] = $this->p_title;
        $data['prod_brief'] = $this->p_brief;
        $data['prod_cost'] = $this->p_cost;
        $data['prod_price_origin'] = $this->p_price_origin;
        $data['prod_price'] = $this->p_price;
        $data['prod_discount'] = $this->p_discount;
        $data['cover_img'] = Img::filterKey('cover_img', $this->_imgs);
        $data['cover_img'] = Tools::checkNoImg($data['cover_img']);
        $data['imgs'] = Img::filterKey('prod_img_', $this->_imgs, true);
        if (!$data['imgs']) {
            $data['imgs'] = null;
        }
        $data['content'] = $this->getContent();
        $data['reply_count'] = $this->p_reply_count;
        $data['status'] = $this->p_status;
        $data['remark'] = $this->p_remark;
        $date = new DateTime($this->created_at);
        $data['created_at'] = $date->format('Y-m-d');
        $data['praise_count'] = $this->p_praise_count;
        $data['cate'] = $this->p_cate;
        $data['open_file'] = $this->open_file;
        $data['mobile'] = $this->p_mobile;
        $data['cate_label'] = $this->getCateLabel();

        $quantity = null;
        if (!empty($this->quantity)) {
            $quantity = $this->quantity->showInList();
        }
        $data['quantity'] = $quantity;

        $promo = null;
        if (!empty($this->promo)) {
            $promo = $this->promo->showDetail();
        }
        $data['promo'] = $promo;

        if (!empty($this->booth)) {
            $data['booth'] = $this->booth->showInList();
        }

        if (!empty($this->user)) {
            $data['user'] = $this->user->showDetail();
        }

        if ($this->replies) {
            $tmp = [];
            foreach ($this->replies as $key => $reply) {
                $tmp[] = $reply->showInList();
            }
            $data['replies'] = $tmp;
        }

        if ($this->favorites) {
            if (count($this->favorites) > 0) {
                $data['is_favorited'] = 1;
            } else {
                $data['is_favorited'] = 0;
            }
        }
        return $data;
    }

    public function getContent()
    {
        $content = json_decode($this->p_desc, JSON_OBJECT_AS_ARRAY);
        $pic_text = [];
        if (empty($this->_imgs)) {
            $this->loadImgs();
        }
        $imgs = Img::filterKey('prod_img_', $this->_imgs, true);
        foreach ($imgs as $key => $img) {
            $txt = empty($content[$key]) ? '' : $content[$key];
            $pic_text[] = ['img' => $img, 'text' => $txt];
        }

        return $pic_text;
    }

    public function addProduct()
    {
        // get max sort
        $sort = Product::where('u_id', '=', $this->u_id)->max('sort');
        if (empty($sort)) {
            $sort = 0;
        }
        $sort += 1;
        $now = new DateTime;
        $this->baseValidate();
        $this->sort = $sort;
        $this->p_reply_count = 0;
        $this->created_at = $now->format('Y-m-d H:i:s');
        $this->save();
        return $this->p_id;
    }

    public function saveProduct($stock)
    {
        $now = new DateTime;
        $this->baseValidate();
        $this->active_at = $now->format('Y-m-d H:i:s');
        $this->save();

        $quantity = ProductQuantity::where('p_id', '=', $this->p_id)->first();
        if (empty($quantity)) {
            $quantity = new ProductQuantity();
            $quantity->p_id = $this->p_id;
            $quantity->b_id = $this->b_id;
            $quantity->u_id = $this->u_id;
            $quantity->q_total = $stock;
            $quantity->addQuantity();
        }
        $freez = $quantity->q_cart + $quantity->q_sold;
        if ($freez > $stock) {
            throw new Exception("库存数量不能小于:".$freez, 1);
        }
        $quantity->q_total = $stock;
        $quantity->save();
        return $this->p_id;
    }

    public static function updateSort($sort)
    {
        $sql = 'UPDATE t_products SET sort = CASE p_id';
        $ids = [];
        foreach ($sort as $id => $s) {
            if (!is_numeric($id)) {
                throw new Exception("无效的排序数据", 1);
            }
            $sql .= ' WHEN '.$id.' THEN '.$s;
            $ids[] = $id;
        }
        $sql .= ' END WHERE p_id IN ('.implode(',', $ids).')';
        return DB::statement($sql);
    }

    public static function updateDiscount($discount)
    {
        $sql = 'UPDATE t_products SET ';
        $discountSql = ' p_discount = CASE p_id ';
        $priceSql = ' p_price = CASE p_id ';
        $ids = [];
        foreach ($discount as $id => $d) {
            if (!is_numeric($id)) {
                throw new Exception("无效的折扣数据", 7001);
            }
            if (!strpos($d, '@')) {
                throw new Exception("无效的折扣/百分比键值对", 7001);
            }
            $tmp = explode('@', $d);
            $discountSql .= ' WHEN '.$id.' THEN '.$tmp[0];
            $priceSql .= ' WHEN '.$id.' THEN '.$tmp[1];
            $ids[] = $id;
        }
        $sql = $sql.$discountSql.' END, '.$priceSql;
        $sql .= ' END WHERE p_id IN ('.implode(',', $ids).')';
        return DB::statement($sql);
    }

    public function getCateLabel()
    {
        if (!$this->p_type) {
            return '';
        }
        $cates = Product::getProductCate($this->p_type);
        if (!$this->p_cate) {
            return '';
        }
        return $cates[$this->p_cate];
    }

    private function loadImgs()
    {
        $this->_imgs = Img::toArray($this->p_imgs);
    }

    // laravel relation
    
    public function quantity()
    {
        return $this->hasOne('ProductQuantity', 'p_id', 'p_id');
    }

    public function booth()
    {
        return $this->belongsTo('Booth', 'b_id', 'b_id');
    }

    public function promo()
    {
        return $this->hasOne('PromotionInfo', 'p_id', 'p_id');
    }

    public function user()
    {
        return $this->belongsTo('User', 'u_id', 'u_id');
    }
    
    public function praises()
    {
        return $this->morphToMany('Praise', 'praisable');
    }

    public function favorites()
    {
        return $this->morphToMany('Favorite', 'favoriable');
    }

    public function replies()
    {
        return $this->morphToMany('Reply', 'repliable');
    }
}
