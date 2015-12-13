<?php
/**
*
*/
class UsersBankCard extends Eloquent
{

    public $primaryKey = 't_id';
    public $timestamps = false;

    public function showInList()
    {
        $data = [];
        $data['card_number'] = $this->b_card_number;
        if (!empty($this->bank)) {
            $data['bank'] = $this->bank->showInList();
        }
        $data['holder'] = $this->b_holder_name;
        return $data;
    }

    public function user()
    {
        return $this->belongsTo('User', 'u_id', 'u_id');
    }

    public function bank()
    {
        return $this->belongsTo('DicBank', 'b_id', 'b_id');
    }
}
