<?php
/**
*
*/
class Booth extends Eloquent
{
    public $primaryKey = 'b_id';
    public $timestamps = false;

    public static $type = [1 => '便利店', 2 => '创的店', 3 => '创的店与便利店'];

    private function baseValidate()
    {
        $validator = Validator::make(
            ['site' => $this->s_id, 'type' => $this->b_type, 'user' => $this->u_id, 'title' => $this->b_title],
            ['site' => 'required', 'type' => 'required', 'user'=> 'required', 'title' => 'required']
        );
        if ($validator->fails()) {
            $msg = $validator->messages();
            throw new Exception($msg->first(), 1);
        } else {
            return true;
        }
    }

    public function showInLogin()
    {
        $data = [];
        $data['id'] = $this->b_id;
        $data['title'] = $this->b_title;
        $data['desc'] = $this->b_desc;
        $data['type'] = $this->b_type;
        $data['category'] = $this->b_product_category;
        $data['logo'] = $this->getLogo();
        $data['fans'] = $this->b_fans_count;
        $data['status'] = $this->b_status;
        $data['open'] = $this->b_open;
        $data['open_from'] = $this->b_open_from;
        $data['open_to'] = $this->b_open_to;
        $data['open_on'] = $this->open_on;

        return $data;

    }

    public function showInList()
    {
        $data = [];
        $data['id'] = $this->b_id;
        $data['title'] = $this->b_title;
        $data['desc'] = $this->b_desc;
        $data['type'] = $this->b_type;
        $data['category'] = $this->b_product_category;
        $data['user'] = null;
        $data['status'] = $this->b_status;
        $data['remark'] = $this->remark;
        if (!empty($this->user)) {
            $data['user'] = $this->user->showInList();
        }
        return $data;
    }

    public function showDetail()
    {
        $data = [];
        $data['id'] = $this->b_id;
        $data['title'] = $this->b_title;
        $data['desc'] = $this->b_desc;
        $data['type'] = $this->b_type;
        $data['category'] = $this->b_product_category;
        $data['source'] = $this->b_product_source;
        $data['logo'] = $this->getLogo();
        $data['fans'] = $this->b_fans_count;
        $data['status'] = $this->b_status;
        $data['lng'] = $this->longitude;
        $data['lat'] = $this->latitude;
        $data['cust_group'] = $this->b_customer_group;
        $data['promo_strategy'] = $this->b_promo_strategy;
        $data['is_fund'] = $this->b_with_fund;
        $data['open'] = $this->b_open;
        $data['open_from'] = $this->b_open_from;
        $data['open_to'] = $this->b_open_to;
        $data['open_on'] = $this->open_on;
        $data['status'] = $this->b_status;
        $data['remark'] = $this->remark;
        $user = null;
        if (!empty($this->user)) {
            $user = $this->user->showInList();
        }
        $data['user'] = $user;

        return $data;
    }

    public function showInOffice()
    {
        $data = [];
        $data['id'] = $this->b_id;
        $data['title'] = $this->b_title;
        $data['desc'] = $this->b_desc;
        $data['type'] = $this->b_type;
        $data['category'] = $this->b_product_category;
        $data['source'] = $this->b_product_source;
        $data['logo'] = $this->getLogo();
        $data['fans'] = $this->b_fans_count;
        $data['status'] = $this->b_status;
        $data['lng'] = $this->longitude;
        $data['lat'] = $this->latitude;
        $data['cust_group'] = $this->b_customer_group;
        $data['promo_strategy'] = $this->b_promo_strategy;
        $data['is_fund'] = $this->b_with_fund;
        $data['open'] = $this->b_open;
        $data['open_from'] = $this->b_open_from;
        $data['open_to'] = $this->b_open_to;
        $data['open_on'] = $this->open_on;
        $data['source'] = $this->b_product_source;
        $data['cust_group'] = $this->b_customer_group;
        $data['promo_strategy'] = $this->b_promo_strategy;
        $data['is_fund'] = $this->b_with_fund;
        $data['status'] = $this->b_status;
        $data['remark'] = $this->remark;
        if (!empty($this->fund)) {
            $data['fund'] = $this->fund->showDetail();
        }

        if (!empty($this->user)) {
            $data['user'] = $this->user->showInOffice();
        }
        return $data;
    }

    public function addBooth()
    {
        $this->baseValidate();
        if (empty($this->created_at)) {
            $now = new DateTime();
            $this->created_at = $now->format('Y-m-d H:i:s');
        }
        $this->save();
        return $this->b_id;
    }

    public function register()
    {
        $this->b_status = 0;
        return $this->addBooth();
    }

    public function addCensorLog($content)
    {
        $log = new LogUserProfileCensors();
        $log->u_id = $this->u_id;
        $log->cate = 'booth';
        $log->content = $content;
        $log->admin_id = Tools::getAdminId();
        $log->addLog();
    }

    public function censor()
    {
        $old_status = '审核之前的状态为: '.$this->getOriginal('b_status').', 审核之后的状态为: '.$this->b_status.'.';
        if ($this->b_status == 2) {
            $content = '店铺审核未通过, '.$old_status.' 备注: '.$this->remark;
        } elseif ($this->b_status == 1) {
            $content = '店铺审核通过, '.$old_status;
        } else {
            $content = '审核店铺记录, '.$old_status;
        }
        $pushMsgObj = new PushMessage($this->u_id);
        $pushMsgObj->pushMessage($content);
        $this->addCensorLog($content);
        return $this->save();
    }

    public function getLogo()
    {
        $logo = null;
        $imgs = Img::toArray($this->b_imgs);
        if (empty($imgs['logo'])) {
            $logo = null;
        } elseif (strpos($imgs['logo'], 'http://') !== false) {
            $logo = $imgs['logo'];
        } else {
            $logo = substr($imgs['logo'], 5);
        }
        return $logo;
    }

    public static function clearByUser($u_id)
    {
        $record = Booth::where('u_id', '=', $u_id)->where('b_status', '=', 0)->first();
        if (!empty($record)) {
            $record->delete();
        }
    }

    // laravel relations
    
    public function user()
    {
        return $this->belongsTo('User', 'u_id', 'u_id');
    }

    public function products()
    {
        return $this->hasMany('Product', 'b_id', 'b_id');
    }

    public function promo()
    {
        return $this->hasMany('PromotionInfo', 'b_id', 'b_id');
    }

    public function fund()
    {
        return $this->hasOne('Fund', 'b_id', 'b_id');
    }
}
