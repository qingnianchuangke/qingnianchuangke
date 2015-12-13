<?php
/**
*
*/
class TmpUsersContactPeople extends Eloquent
{

    public $primaryKey = 'u_id';
    public $timestamps = false;

    private function baseValidate()
    {
        $validator = Validator::make(
            ['teacher_name' => $this->u_teacher_name, 'teacher_phone' => $this->u_teacher_telephone, 'student_number' => $this->u_student_number],
            ['teacher_name' => 'required', 'teacher_phone' => 'required', 'student_number' => 'required']
        );
        if ($validator->fails()) {
            $msg = $validator->messages();
            throw new Exception($msg->first(), 1);
        } else {
            return true;
        }
    }

    public function addContactPerson()
    {
        $this->baseValidate();
        $this->save();
        return $this->id;
    }

    public function register()
    {
        $this->u_status = 0;
        return $this->addContactPerson();
    }

    public function showDetail()
    {
        $data = [];

        $this->load('school');
        $data['school'] = $this->school->showInList();
        $data['student_img'] = Img::toArray($this->u_student_img, true);
        $data['student_number'] = $this->u_student_number;
        $data['teacher_name'] = $this->u_teacher_name;
        $data['teacher_phone'] = $this->u_teacher_telephone;
        $data['frend_name1'] = $this->u_frend_name1;
        $data['frend_telephone1'] = $this->u_frend_telephone1;
        $data['frend_name2'] = $this->u_frend_name2;
        $data['frend_telephone2'] = $this->u_frend_telephone2;
        $data['profession'] = $this->u_prof;
        $data['degree'] = $this->u_degree;
        $data['entry_year'] = $this->u_entry_year;
        $data['status'] = $this->u_status;
        if ($this->u_status == 2) {
            $data['remark'] = $this->remark;
        }
        return $data;
    }

    public static function clearByUser($u_id)
    {
        $record = TmpUsersContactPeople::find($u_id);
        $record->delete();
    }

    public static function checkProfile($u_id)
    {
        $contact = TmpUsersContactPeople::find($u_id);
        if (!isset($contact->u_id)) {
            return 0;
        }
        if ($contact->u_status == 1) {
            return 1;
        } else {
            return 0;
        }
    }

    public function addCensorLog($content)
    {
        $log = new LogUserProfileCensors();
        $log->u_id = $this->u_id;
        $log->cate = 'contact';
        $log->content = $content;
        $log->admin_id = Tools::getAdminId();
        $log->addLog();
    }

    public function censor()
    {
        $old_status = '审核之前的状态为: '.$this->getOriginal('u_status').', 审核之后的状态为: '.$this->u_status.'.';
        if ($this->u_status == 2) {
            $content = '用户联系人信息审核未通过, '.$old_status.' 备注: '.$this->remark;
        } elseif ($this->u_status == 1) {
            $content = '用户联系人信息审核通过, '.$old_status;
        } else {
            $content = '审核联系人信息记录, '.$old_status;
        }
        $msg = new MessageDispatcher($this->u_id);
        $msg->fireTextToUser($content);
        $this->addCensorLog($content);
        return $this->save();
    }

    // lavaral realtions

    public function user()
    {
        return $this->belongsTo('User', 'u_id', 'u_id');
    }

    public function school()
    {
        return $this->hasOne('DicSchool', 't_id', 'u_school_id');
    }
}
