<?php
/**
*
*/
class TmpUserProfileBase extends Eloquent
{

    public $primaryKey = 'u_id';
    public $timestamps = false;

    public static function getStatus($key = null, $all = false)
    {
        $status = [
            '0' => '待审核',
            '1' => '审核通过',
            '2' => '审核不通过'
        ];
        if ($key !== null && array_key_exists($key, $status)) {
            return $status[$key];
        } else {
            if ($all) {
                return $status;
            } else {
                return '未知状态';
            }
        }
    }

    private function baseValidate()
    {
        $validator = Validator::make(
            ['user' => $this->u_id],
            ['user' => 'required']
        );
        if ($validator->fails()) {
            $msg = $validator->messages();
            throw new Exception($msg->first(), 1);
        } else {
            return true;
        }
    }

    public function addProfile()
    {
        $this->baseValidate();
        $this->save();
        return $this->u_id;
    }

    public function register()
    {
        $this->u_status = 0;
        return $this->addProfile();
    }

    public function showDetail()
    {
        $data = [];

        $this->load('school');
        $data['school'] = empty($this->school) ? null : $this->school->showInList();
        $data['student_img'] = Img::toArray($this->u_student_imgs, true);
        $data['student_number'] = $this->u_student_number;
        $data['student_verified'] = $this->u_is_student_verified;
        $data['identity_number'] = $this->u_id_number;
        $data['identity_img'] = Img::toArray($this->u_id_imgs, true);
        $data['identity_verified'] = $this->u_is_id_verified;
        $data['entry_year'] = $this->u_entry_year;
        $data['major'] = $this->u_major;
        $data['emergency_phone'] = $this->em_contact_phone;
        $data['emergency_name'] = $this->em_contact_name;
        $data['home_adress'] = $this->u_home_adress;
        $data['father_name'] = $this->u_father_name;
        $data['father_telephone'] = $this->u_father_phone;
        $data['mother_name'] = $this->u_mother_name;
        $data['mother_telephone'] = $this->u_mother_phone;
        $data['apartment'] = $this->u_apartment_no;
        $data['status'] = $this->u_status;
        if ($this->u_status == 2) {
            $data['remark'] = $this->remark;
        }
        return $data;
    }

    public static function clearByUser($u_id)
    {
        $record = TmpUserProfileBase::find($u_id);
        $record->delete();
    }

    public static function checkProfile($u_id)
    {
        $contact = TmpUserProfileBase::find($u_id);
        if (empty($contact)) {
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
        $log->cate = 'base';
        $log->content = $content;
        $log->admin_id = Tools::getAdminId();
        $log->addLog();
    }

    public function censor()
    {
        $original_statue = $this->getOriginal('u_status');
        $new_status = $this->u_status;
        $old_status = '审核之前的状态为: '.TmpUserProfileBase::getStatus($original_statue).', 审核之后的状态为: '.TmpUserProfileBase::getStatus($new_status).'.';
        if ($this->u_status == 2) {
            $content = '用户信息审核未通过, '.$old_status.' 备注: '.$this->remark;
        } elseif ($this->u_status == 1) {
            $content = '用户信息审核通过, '.$old_status;
        } else {
            $content = '审核信息记录, '.$old_status;
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
        return $this->hasOne('DicSchool', 't_id', 's_id');
    }
}
