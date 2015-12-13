<?php
/**
*
*/
class UserProfileBase extends Eloquent
{

    public $primaryKey = 'u_id';
    public $timestamps = false;

    public function showDetail()
    {
        $data = [];

        $this->load('school');
        $data['school'] = empty($this->school) ? null : $this->school->showInList();
        $data['student_img'] = Img::toArray($this->u_student_imgs, true);
        $data['student_number'] = $this->u_student_number;
        $data['identity_number'] = $this->u_id_number;
        $data['identity_img'] = Img::toArray($this->u_id_imgs, true);
        $data['entry_year'] = $this->u_entry_year;
        $data['major'] = $this->u_major;
        $data['emergency_phone'] = $this->em_contact_phone;
        $data['status'] = $this->u_status;
        return $data;
    }

    // laravel relations

    public function user()
    {
        return $this->belongsTo('User', 'u_id', 'u_id');
    }

    public function school()
    {
        return $this->hasOne('DicSchool', 's_id', 't_id');
    }
}
