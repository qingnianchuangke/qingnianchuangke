<?php
/**
*
*/
class UserImportQq extends UserImport
{
    public $primaryKey = 'u_id';
    public $timestamps = false;

    public function import()
    {
        $this->baseValidate();
        $chk = UserImportQq::where('u_ext_id', '=', $this->u_ext_id)->first();
        $u_id = 0;
        if (empty($chk)) {
            $re = $this->register($this->u_ext_id, $this->u_ext_token, $this->u_nickname, $this->u_head_img, $this->u_gender);
            $this->u_id = $re['u_id'];
            $this->created_at = Tools::getNow();
            $this->save();
            $u_id = $re['u_id'];
        } else {
            $u_id = $chk->u_id;
        }
        $re = $this->login($u_id);
        $re['import_type'] = 'qq';
        return $re;
    }
}
