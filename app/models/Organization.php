<?php
/**
*
*/
class Organization extends \Eloquent
{
    public $primaryKey = 'o_id';
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

    private function baseValidate()
    {
        $validator = Validator::make(
            ['组织名称' => $this->o_title, '用户' => $this->u_id],
            ['组织名称' => 'required', '用户' => 'required']
        );
        if ($validator->fails()) {
            $msg = $validator->messages();
            throw new Exception($msg->first(), 1);
        } else {
            return true;
        }
    }

    public function addOrganiztion()
    {
        $this->created_at = Tools::getNow();
        $this->o_status = 1;
        $this->save();
    }

    public function censor()
    {
        $old_status = '审核之前的状态为: '.Organization::getStatus($this->getOriginal('u_status')).', 审核之后的状态为: '.Organization::getStatus($this->u_status).'.';
        if ($this->u_status == 2) {
            $content = '组织信息审核未通过, '.$old_status.' 备注: '.$this->remark;
        } elseif ($this->u_status == 1) {
            $content = '组织信息审核通过, '.$old_status;
        } else {
            $content = '审核组织信息记录, '.$old_status;
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
        $log->cate = 'organization';
        $log->content = $content;
        $log->admin_id = Tools::getAdminId();
        $log->addLog();
    }
}
