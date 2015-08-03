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
        $data['status'] = $this->t_status;
        $loans = null;
        if (!empty($this->loans)) {
            foreach ($this->loans as $key => $loan) {
                $loans[] = $loan->showInList();
            }
        }
        $data['loans'] = $loans;
        return $data;
    }

    public function addFund()
    {
        $now = new DateTime();
        $this->baseValidate();
        $this->created_at = $now->format('Y-m-d H:i:s');
        $this->save();
        return $this->t_id;
    }

    public function apply()
    {
        $this->t_status = 0;
        return $this->addFund();
    }

    public static function clearByUser($u_id)
    {
        $record = Fund::where('u_id', '=', $u_id)->where('t_status', '=', 0)->first();
        $f_id = $record->t_id;
        $record->delete();
        return $f_id;
    }

    // laravel relations

    public function loans()
    {
        return $this->hasMany('Repayment', 'f_id', 't_id');
    }
}
