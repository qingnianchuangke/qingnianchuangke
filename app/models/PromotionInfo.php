<?php
/**
*
*/
class PromotionInfo extends Eloquent
{
    public $primaryKey = 'p_id';
    public $timestamps = false;

    private function baseValidate()
    {
        $validator = Validator::make(
            ['content' => $this->p_content, 'range' => $this->p_range, 'city' => $this->c_id, 'school' => $this->s_id, 'booth' => $this->b_id],
            ['content' => 'required', 'range' => 'required', 'city' => 'required', 'school' => 'required', 'booth' => 'required']
        );
        if ($validator->fails()) {
            $msg = $validator->messages();
            throw new Exception($msg->first(), 1);
        } else {
            return true;
        }
    }

    public function addPromo()
    {
        $this->baseValidate();
        $now = new DateTime();
        $this->created_at = $now->format('Y-m-d H:i:s');
        $this->updated_at = $now->format('Y-m-d H:i:s');
        $this->p_praise_count = 0;
        $this->p_reply_count = 0;
        $this->save();
        return $this->p_id;
    }

    public function showDetail()
    {
        $data = [];
        $data['content'] = $this->p_content;
        $data['praise_count'] = $this->p_praise_count;
        $data['praises'] = null;
        if (!empty($this->praises)) {
            $tmp = null;
            foreach ($this->praises as $key => $praise) {
                $tmp[] = $praise->showInList();
            }
            $data['praises'] = $tmp;
        }
        return $data;
    }

    public function showInList()
    {
        $data = [];
        $data['content'] = $this->p_content;
        $data['praise_count'] = $this->p_praise_count;
        return $data;
    }

    public function showInListWithProduct()
    {
        $data = null;
        $data['product'] = null;
        if (!empty($this->product)) {
            $data['product'] = $this->product->showInList();
        }
        $data['booth'] = null;
        if (!empty($this->booth)) {
            $data['booth'] = $this->booth->showDetail();
        }
        $data['school'] = null;
        if (!empty($this->school)) {
            $data['school'] = $this->school->showInList();
        }
        if ($this->city) {
            $data['city']  = $this->city->showInList();
        }
        $data['praises'] = null;
        if (!empty($this->praises)) {
            $tmp = null;
            foreach ($this->praises as $key => $praise) {
                $tmp[] = $praise->showInList();
            }
            $data['praises'] = $tmp;
        }
        $data['created_at'] = $this->created_at->format('Y-m-d H:i:s');
        $data['prom_id'] = $this->p_id;
        return $data;
    }

    // laravel relation
    
    public function product()
    {
        return $this->belongsTo('Product', 'p_id', 'p_id');
    }

    public function booth()
    {
        return $this->belongsTo('Booth', 'b_id', 'b_id');
    }

    public function school()
    {
        return $this->hasOne('DicSchool', 't_id', 's_id');
    }

    public function city()
    {
        return $this->hasOne('DicCity', 'c_id', 'c_id');
    }
    public function praises()
    {
        return $this->hasMany('PromotionPraise', 'prom_id', 'p_id');
    }
}
