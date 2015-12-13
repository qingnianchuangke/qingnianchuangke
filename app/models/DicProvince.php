<?php
/**
*
*/
class DicProvince extends Eloquent
{
    public $primaryKey = 'id';
    public $timestamps = false;

    const VER = 1;

    public function showInList()
    {
        $data['id'] = $this->id;
        $data['name'] = $this->province;
        return $data;
    }
}
