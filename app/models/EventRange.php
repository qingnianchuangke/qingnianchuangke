<?php
/**
*
*/
class EventRange extends Eloquent
{
    public $timestamps = false;
    protected $fillable = ['e_id', 's_id', 'c_id', 'p_id'];

    public function showInList()
    {
        $data = [];
        $data['city'] = $this->city();
        $this->load('school');
        if (empty($this->school)) {
            $data['school'] = [
                'id' => '',
                'school_name' => '',
            ];
        } else {
            $data['school'] = $this->school->showInList();
        }
        return $data;
    }

    public function city()
    {
        $city = DicCity::where('c_id', '=', $this->c_id)->where('c_province_id', '=', $this->p_id)->first();
        if (empty($city)) {
            $data['id'] = '';
            $data['name'] = '';
            $data['province_id'] = '';
        } else {
            $data = $city->showInList();
        }
        return $data;
    }

    // relations
    // 
    public function school()
    {
        return $this->hasOne('DicSchool', 't_id', 's_id');
    }

}
