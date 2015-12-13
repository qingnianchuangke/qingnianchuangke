<?php
/**
*
*/
class UsersDraw extends Eloquent
{
    public $primaryKey = 'd_id';
    public $timestamps = false;

    private function baseValidate()
    {
        $validator = Validator::make(
            ['account' => $this->d_account, 'amount' => $this->d_amount, 'payment' => $this->d_payment],
            ['account' => 'required', 'amount' => 'required', 'payment' => 'required']
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
        $data['id'] = $this->d_id;
        $data['payment'] = $this->d_payment;
        $data['account'] = $this->d_account;
        $data['amount'] = $this->d_amount;
        $date = new DateTime($this->created_at);
        $data['draw_at'] = $date->format('Y-m-d H:i:s');
        if (!empty($this->bank)) {
            $data['bank'] = $this->bank->showInList();
            $data['holder'] = $this->b_holder_name;
        }
        $data['status'] = $this->d_status;
        return $data;
    }

    public function addDraw()
    {
        $this->baseValidate();
        $now = new DateTime();
        $this->created_at = $now->format('Y-m-d H:i:s');
        $this->d_status = 0;

        $this->save();

        return $this->d_id;
    }

    public function addLog($comment)
    {
        $log = new LogDrawConfirm();
        $log->admin_id = Tools::getAdminId();
        $log->draw_id = $this->id;
        $log->comment = $comment;
    }

    public function confirm($comment)
    {
        $logComment = '状态改变, 由'.$this->getOriginal('d_status').'到'.$this->d_status.', 备注:'.$comment;
        if ($this->d_status == 0) {
            throw new Exception("不能将记录状态确认到待处理", 9008);
        } elseif ($this->d_status == 1) {
            if ($this->getOriginal('d_status') == 1) {
                throw new Exception("无法重复提现", 9008);
            }
            $string = '放款成功, '.$this->d_amount.'将会转入您的账户';
        } elseif ($this->d_status == 2) {
            $string = '放款失败, 平台备注:'.$comment;
        }
        $msg = new MessageDispatcher($this->u_id);
        $msg->fireTextToUser($string);
        return $this->save();
    }

    // relations
    public function bank()
    {
        return $this->hasOne('DicBank', 'b_id', 'b_id');
    }
}
