<?php
/**
*
*/
class UserImport extends Eloquent
{
    protected $fillable = ['u_id', 'u_ext_id', 'u_ext_token', 'u_head_img', 'u_nickname', 'u_gender'];

    protected function baseValidate()
    {
        $validator = Validator::make(
            ['ext_id' => $this->u_ext_id],
            ['ext_id' => 'required']
        );
        if ($validator->fails()) {
            $msg = $validator->messages();
            throw new Exception($msg->first(), 3005);
        } else {
            return true;
        }
    }

    public function register($ext_id, $ext_token = '', $nickname = '', $head_img = '', $gender = 3)
    {
        $user = new User();
        $user->u_nickname = $nickname;
        $user->u_name = $nickname;
        $user->u_sex = $gender;
        $user->u_birthday = Tools::getNow('Y-m-d');
        $user->u_token = $user->getUniqueToken();
        $user->u_invite_code = $user->getInviteCode();
        $user->created_at = Tools::getNow();
        $user->updated_at = Tools::getNow();
        $user->u_type = 1;
        $user->u_status = 1;
        $user->save();
        if (!$nickname) {
            $user->u_nickname = $this->u_id;
            $user->u_name = $this-u_id;
            $user->save();
        }
        $data = ['u_id' => $user->u_id, 'ext_id' => $ext_id, 'ext_token' => $ext_token];
        return $data;
    }

    public function login($u_id)
    {
        $user = User::find($u_id);
        if (empty($user)) {
            throw new Exception("没有找到用户", 3005);
        }
        if ($user->u_status != 1) {
            throw new Exception("您的账号不可用, 请联系客服", 3005);
        }
        $re = [];
        $re['token'] = $user->u_token;
        $now = new Datetime();
        $now->modify('+ 30 days');
        $re['expire'] = $now->format('Y-m-d H:i:s');
        $re['id'] = $user->u_id;
        $re['name'] = $user->u_name = $this->u_nickname;
        $re['nickname'] = $user->u_nickname = $this->u_nickname;
        $re['invite_code'] = $user->u_invite_code;
        $re['head_img'] = $user->u_head_img = $this->u_head_img;
        $re['biograph'] = $user->u_biograph;
        $school = DicSchool::find($user->u_school_id);
        if (empty($school)) {
            $re['city'] = null;
            $re['school'] = null;
        } else {
            $re['city'] = DicCity::where('c_id', '=', $school->t_city)->where('c_province_id', '=', $school->t_province)->first()->showInList();
            $re['school'] = $school->showInList();
        }
        $user->save();
        $re['gender'] = $user->u_sex;
        $user->load('booths');
        $booths = null;
        if (!empty($user->booths)) {
            foreach ($user->booths as $key => $booth) {
                $booths[] = $booth->showInLogin();
            }
        }
        $re['boohts'] = $booths;
        return $re;
    }
}
