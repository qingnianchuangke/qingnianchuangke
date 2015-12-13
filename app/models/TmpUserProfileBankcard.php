<?php
/**
*
*/
class TmpUserProfileBankcard extends Eloquent
{

    public $primaryKey = 'u_id';
    public $timestamps = false;

    public static function getStatus($key = null, $all = false)
    {
        $status = [
            '0' => '待审核',
            '1' => '审核通过',
            '2' => '审核不通过'
        ];
        if ($key !== null && array_key_exists($key, $status)) {
            return $status[$key];
        } else {
            if ($all) {
                return $status;
            } else {
                return '未知状态';
            }
        }
    }

    private function baseValidate()
    {
        $validator = Validator::make(
            ['user' => $this->u_id, 'bank' => $this->b_id, 'card_number' => $this->b_card_number],
            ['user' => 'required', 'bank' => 'required', 'card_number' => 'required']
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
        $data['card_number'] = $this->b_card_number;
        $data['bank'] = $this->bank->b_name;
        return $data;
    }

    public function showDetail()
    {
        $data = [];
        $this->load('bank');
        $data['u_id'] = $this->u_id;
        $data['id'] = $this->u_id;
        $data['card_number'] = $this->b_card_number;
        $data['bank'] = $this->bank->showInList();
        $data['holder_name'] = $this->b_holder_name;
        $data['holder_phone'] = $this->b_holder_phone;
        $data['holder_identity_number'] = $this->b_holder_id_number;
        $data['status'] = $this->b_status;
        if ($this->b_status == 2) {
            $data['remark'] = $this->remark;
        }
        return $data;
    }

    public function addBankCard()
    {
        $this->baseValidate();
        $this->save();
        return $this->id;
    }

    public function register()
    {
        $this->b_status = 0;
        return $this->addBankCard();
    }

    public static function checkProfile($u_id)
    {
        $card = TmpUserProfileBankcard::find($u_id);
        if (empty($card)) {
            return 0;
        }
        if ($card->b_status == 1) {
            return 1;
        } else {
            return 0;
        }
    }

    public function addCensorLog($content)
    {
        $log = new LogUserProfileCensors();
        $log->u_id = $this->u_id;
        $log->cate = 'bank_card';
        $log->content = $content;
        $log->admin_id = Tools::getAdminId();
        $log->addLog();
    }

    public function censor()
    {
        $original_status = $this->getOriginal('b_status');
        $new_status = $this->b_status;
        $old_status = '审核之前的状态为: '.TmpUserProfileBankcard::getStatus($original_status).', 审核之后的状态为: '.TmpUserProfileBankcard::getStatus($new_status).'.';
        if ($this->b_status == 2) {
            $content = '用户银行卡信息审核未通过, '.$old_status.' 备注: '.$this->remark;
        } elseif ($this->b_status == 1) {
            $content = '用户银行卡信息审核通过, '.$old_status;
        } else {
            $content = '审核银行卡信息记录, '.$old_status;
        }
        $msg = new MessageDispatcher($this->u_id);
        $msg->fireTextToUser($content);
        $this->addCensorLog($content);
        return $this->save();
    }

    // laravel relations

    public function user()
    {
        return $this->belongsTo('User', 'u_id', 'u_id');
    }

    public function bank()
    {
        return $this->hasOne('DicBank', 'b_id', 'b_id');
    }

    public static function clearByUser($u_id)
    {
        $record = TmpUserProfileBankcard::where('u_id', '=', $u_id)->where('b_status', '=', 0)->first();
        $record->delete();
    }
}
