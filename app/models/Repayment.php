<?php
/**
*
*/
class Repayment extends Eloquent
{
    public $primaryKey = 't_id';
    public $timestamps = false;

    private function baseValidate()
    {
        $validator = Validator::make(
            ['fund' => $this->f_id, 'amount' => $this->f_re_money, 'schema' => $this->f_schema],
            ['fund' => 'required', 'amount' => 'required', 'schema' => 'required']
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
        $data['id'] = $this->t_id;
        $data['schema'] = $this->f_schema;
        $data['percentage'] = $this->f_percentage;
        $data['amount'] = $this->f_re_money;
        $data['paied_amount'] = $this->f_money;
        $data['status'] = $this->f_status;
        $data['income'] = $this->f_income;
        if (!empty($this->repaied_at)) {
            $date = new DateTime($this->repaied_at);
            $data['repaied_at'] = $date->format('Y-m-d H:i:s');
        } else {
            $data['repaied_at'] = null;
        }
        return $data;
    }

    public function addRepayment()
    {
        $now = new DateTime();
        $this->baseValidate();
        $this->created_at = $now->format('Y-m-d H:i:s');
        $this->save();
        return $this->id;
    }

    public function apply()
    {
        $this->f_status = 0;
        return $this->addRepayment();
    }

    public function addAllocLog($comment)
    {
        $comment = '状态变化:'.$this->getOriginal('f_status').'->'.$this->f_status.'; 备注:'.$comment;
        $log = new LogRepaymentsAllocate();
        $log->repayment_id = $this->t_id;
        $log->admin_id = Tools::getAdminId();
        $log->comment = $comment;
        $log->addLog();
    }

    public function allocate($comment)
    {
        if ($this->f_status == 1) {
            throw new Exception("该借款已放", 10001);
        }
        $this->f_status = 1;
        $fund = Fund::find($this->f_id);
        if (empty($fund)) {
            throw new Exception("没有找到相关的基金信息", 10001);
        }
        // do transaction
        $userBankCard = UserProfileBankcard::find($fund->u_id);
        if (empty($userBankCard)) {
            throw new Exception("没有找到用户相关的银行卡信息", 10001);
        }
        
        $this->addAllocLog($comment);
        
        return $this->save();
    }

    public function repayAll()
    {
        $this->f_status = 3;
        return $this->save();
    }

    public function repayPartial()
    {
        $this->f_status = 2;
        return $this->save();
    }
}
