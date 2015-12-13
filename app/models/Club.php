<?php
/**
*
*/
class Club extends \Eloquent
{
    public $primaryKey = 'c_id';
    public $timestamps = false;

    public static function getStatus($key = null, $all = false)
    {
        $status = [
            1 => '待审核',
            2 => '审核通过',
            3 => '审核不通过'
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

    public function showInList()
    {
        $data = [];
        $data['title'] = $this->c_title;
        $data['brief'] = $this->c_brief;
        $data['status'] = $this->c_status;
        $data['official_url'] = $this->c_official_url;
        return $data;
    }

    public function showDetail()
    {
        $data = [];
        $data['title'] = $this->c_title;
        $data['brief'] = $this->c_brief;
        $data['official_url'] = $this->c_official_url;
        $data['imgs'] = Img::toArray($this->c_imgs);
        if (empty($data['imgs']['club_proof_img'])) {
            $data['imgs']['club_proof_img'] = null;
        }
        $data['status'] = $this->c_status;
        $data['remark'] = $this->remark;
        if (!empty($this->school)) {
            $data['school'] = $this->school->showInList();
        }
        if (!empty($this->user)) {
            $data['user'] = $this->user->showInList();
        }
        return $data;
    }

    private function baseValidate()
    {
        $validator = Validator::make(
            ['社团名称' => $this->c_title, '用户' => $this->u_id],
            ['社团名称' => 'required', '用户' => 'required']
        );
        if ($validator->fails()) {
            $msg = $validator->messages();
            throw new Exception($msg->first(), 1);
        } else {
            return true;
        }
    }

    public function addClub()
    {
        $this->created_at = Tools::getNow();
        $this->c_status = 1;
        $this->save();
    }

    public function censor()
    {
        $original_status = $this->getOriginal('u_status');
        $new_status = $this->u_status;
        $old_status = '审核之前的状态为: '.Organization::getStatus($original_status).', 审核之后的状态为: '.Organization::getStatus($new_status).'.';
        if ($this->u_status == 2) {
            $content = '社团信息审核未通过, '.$old_status.' 备注: '.$this->remark;
        } elseif ($this->u_status == 1) {
            $content = '社团信息审核通过, '.$old_status;
        } else {
            $content = '审核社团信息记录, '.$old_status;
        }
        $msg = new MessageDispatcher($this->u_id);
        $msg->fireTextToUser($content);
        $this->addCensorLog($content);
        return $this->save();

    }

    public function addCensorLog($content)
    {
        $log = new LogUserProfileCensors();
        $log->u_id = $this->u_id;
        $log->cate = 'club';
        $log->content = $content;
        $log->admin_id = Tools::getAdminId();
        $log->addLog();
    }

    // relation
    //
    public function user()
    {
        return $this->belongsTo('User', 'u_id', 'u_id');
    }

    public function school()
    {
        return $this->hasOne('DicSchool', 's_id', 't_id');
    }
}
