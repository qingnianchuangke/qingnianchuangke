<?php
/**
*
*/
class Fund extends Eloquent
{
    public $primaryKey = 't_id';
    public $timestamps = false;

    private function baseValidate()
    {
        $validator = Validator::make(
            ['user' => $this->u_id, 'amount' => $this->t_apply_money, 'profit_rate' => $this->t_profit_rate, 'booth' => $this->b_id],
            ['user' => 'required', 'amount' => 'required', 'profit_rate' => 'required|digits:2', 'booth' => 'required']
        );
        if ($validator->fails()) {
            $msg = $validator->messages();
            throw new Exception($msg->first(), 1);
        } else {
            return true;
        }
    }

    public function showDetail()
    {
        $data = [];
        $data['id'] = $this->t_id;
        $data['profit'] = $this->t_profit_rate;
        $data['loan'] = $this->t_apply_money;

        if ($this->t_repay_start_date) {
            $startObj = new DateTime($this->t_repay_start_date);
            $start = $startObj->getTimestamp();
            $data['loan_start'] = $startObj->format('Y-m-d H:i:s');
        } else {
            $start = 0;
            $data['loan_start'] = null;
        }
        // if ($this->t_repay_end_date) {
        //     $endObj = new DateTime($this->t_repay_end_date);
        //     $end = $endObj->getTimestamp();
        //     $data['loan_end'] = $endObj->format('Y-m-d H:i:s');
        // } else {
        //     $end = 0;
        //     $data['loan_end'] = null;
        // }
        // $data['loan_period'] = ceil(($end - $start) / (3600 * 24));
        $data['status'] = $this->t_status;
        $data['is_closed'] = (int)$this->t_is_closed;
        if (!empty($this->closed_at)) {
            $date = new DateTime($this->closed_at);
            $data['closed_at'] = $date->format('Y-m-d H:i:s');
        } else {
            $data['closed_at'] = null;
        }
        if (!empty($this->booth)) {
            $data['booth'] = $this->booth->showDetail();
        }
        $loans = null;
        $last_retrive = null;
        $last_allot = null;
        if (!empty($this->loans)) {
            foreach ($this->loans as $key => $loan) {
                $loans[] = $loan->showInList();
                if (!empty($loan->repaied_at)) {
                    $date = new DateTime($loan->repaied_at);
                    $date = $date->format('Y-m-d H:i:s');
                    if (empty($last_retrive)) {
                        $last_retrive = $date;
                    } else {
                        $last_retrive = $last_retrive < $date ? $date : $last_retrive;
                    }
                }
                if (!empty($loan->created_at)) {
                    $date = new DateTime($loan->created_at);
                    $date = $date->format('Y-m-d H:i:s');
                    if (empty($last_allot)) {
                        $last_allot = $date;
                    } else {
                        $last_allot = $last_allot < $date ? $date : $last_allot;
                    }
                }
            }
        }
        $data['last_retrive'] = $last_retrive;
        $data['last_allot'] = $last_allot;
        $data['loans'] = $loans;
        $data['loan_period'] = count($loans);
        return $data;
    }

    public function addFund()
    {
        $this->baseValidate();
        if (empty($this->created_at)) {
            $now = new DateTime();
            $this->created_at = $now->format('Y-m-d H:i:s');
        }
        $this->save();
        return $this->t_id;
    }

    public function addCensorLog($content)
    {
        $log = new LogUserProfileCensors();
        $log->u_id = $this->u_id;
        $log->cate = 'fund';
        $log->content = $content;
        $log->admin_id = Tools::getAdminId();
        $log->addLog();
    }

    public function interview()
    {
        $old_status = '审核之前的状态为: '.$this->getOriginal('t_status').', 审核之后的状态为: '.$this->t_status.'.';
        if ($this->t_status == 1) {
            $content = '基金审核未通过, '.$old_status.' 备注: '.$this->remark;
        } elseif ($this->t_status == 3) {
            $content = '基金审核通过, '.$old_status;
        } else {
            $content = '审核基金记录, '.$old_status;
        }
        $msg = new MessageDispatcher($this->u_id);
        $msg->fireTextToUser($content);
        $this->addCensorLog($content);
        return $this->save();
    }

    public function censorPass($interview)
    {
        if ($interview == 1) {
            $re = $this->censorPassNeedInterview();
        } else {
            $re = $this->censorPassNoInterview();
        }
        return $re;
    }

    public function censorPassNeedInterview()
    {
        $this->t_status = 2;
        return $this->save();
    }

    public function censorPassNoInterview()
    {
        $this->t_status = 3;
        return $this->save();
    }

    public function censorFailed()
    {
        $this->t_status = 1;
        return $this->save();
    }

    public function apply()
    {
        $this->t_status = 0;
        return $this->addFund();
    }

    public function getCurrentPeriodIncome()
    {
        $lastDate = $this->getLastRepayDate();
        $income = Cart::sumIncome($lastDate, null, $this->b_id);
        return $income;
    }

    public function getAllIncome()
    {

    }

    public function getLastRepayDate()
    {
        if (empty($this->loans)) {
            throw new Exception("该基金下没有借款", 6001);
        }
        $lastDate = null;
        foreach ($this->loans as $key => $loan) {
            if (!$loan->repaied_at) {
                continue;
            }
            $date = new DateTime($this->repaied_at);
            if ($lastDate == '') {
                $lastDate = $date;
            }
            if ($lastDate < $date) {
                $lastDate = $data;
            }
        }
        if ($lastDate) {
            $lastDate = $lastDate->format('Y-m-d');
        }
        return $lastDate;
    }

    public function chkLoansAlloc()
    {
        $check = true;
        if (empty($this->loans)) {
            $this->load('loans');
        }
        foreach ($this->loans as $key => $loan) {
            if ($loan->f_status < 1) {
                $check =false;
                break;
            }
        }
        return $check;
    }

    public static function clearByUser($u_id)
    {
        $record = Fund::where('u_id', '=', $u_id)->where('t_status', '=', 0)->first();
        $f_id = 0;
        if (!empty($record)) {
            $f_id = $record->t_id;
            $record->delete();
        }
        return $f_id;
    }

    // laravel relations

    public function loans()
    {
        return $this->hasMany('Repayment', 'f_id', 't_id');
    }

    public function booth()
    {
        return $this->belongsTo('Booth', 'b_id', 'b_id');
    }
}
