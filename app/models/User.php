<?php

class User extends Eloquent
{

    public $primaryKey = 'u_id';

    public static function getVerifyType()
    {
        return [
            1 => '校园认证',
            2 => '官方认证',
            3 => '社团认证'
        ];
    }

    /**
     * validate base info
     * @author Kydz 2015-06-14
     * @return bool
     */
    public function baseValidate()
    {
        $validator = Validator::make(
            ['mobile' => $this->u_mobile, 'pass' => $this->u_password],
            ['mobile' => 'required|digits:11', 'pass' => 'required|min:6']
        );
        if ($validator->fails()) {
            $msg = $validator->messages();
            throw new Exception($msg->first(), 1);
        } else {
            return true;
        }
    }

    /**
     * get unique token
     * @author Kydz 2015-06-14
     * @return string token
     */
    public function getUniqueToken()
    {
        $token = Str::random(32);
        if (User::where('u_token', '=', $token)->count() > 0) {
            $token = $this->getUniqueToken();
        }
        return $token;
    }

    public function getInviteCode()
    {
        return $this->u_id;
        $code = Str::random(6);
        if (User::where('u_invite_code', '=', $code)->count() > 0) {
            $code = $this->getInviteCode();
        }
        $code = strtoupper($code);
        return $code;
    }

    /**
     * register user
     * @author Kydz 2015-06-14
     * @return string user token
     */
    public function register()
    {
        $this->baseValidate();
        if (empty($this->u_school_id)) {
            throw new Exception("没有传入有效的学校", 3002);
        }
        // chcek if mobile exsits
        if (User::where('u_mobile', '=', $this->u_mobile)->count() > 0) {
            throw new Exception("手机号码已被使用", 3002);
        }
        // generate token
        $this->u_token = $this->getUniqueToken();
        $this->u_password = Hash::make($this->u_password);
        $this->u_status = 1;
        $this->u_change = 1;
        $this->u_sex = 3;
        $this->u_birthday = Tools::getNow('Y-m-d');
        $this->save();
        $this->u_invite_code = $this->getInviteCode();
        $re = [];
        $this->u_nickname = $this->u_name = $this->u_id;
        $re['token'] = $this->u_token;
        $now = new Datetime();
        $now->modify('+ 30 days');
        $re['invite_code'] = $this->u_invite_code;
        $re['expire'] = $now->format('Y-m-d H:i:s');
        $re['mobile'] = $this->u_mobile;
        $re['id'] = $this->u_id;
        $re['biograph'] = $this->u_biograph;
        $re['is_verified'] = $this->u_is_verified;
        $re['name'] = $this->u_name;
        $re['nickname'] = $this->u_nickname;
        $re['head_img'] = $this->getHeadImg();
        $school = DicSchool::find($this->u_school_id);
        if (empty($school)) {
            $re['city'] = null;
            $re['school'] = null;
        } else {
            $re['city'] = DicCity::where('c_id', '=', $school->t_city)->where('c_province_id', '=', $school->t_province)->first()->showInList();
            $re['school'] = $school->showInList();
        }
        $this->save();
        return $re;
    }

    /**
     * log user in
     * @author Kydz 2015-06-14
     * @return sting user token
     */
    public function login()
    {
        $this->baseValidate();
        $user = User::where('u_mobile', '=', $this->u_mobile)->where('u_status', '=', 1)->first();
        if (!isset($user->u_id)) {
            throw new Exception("请求的用户不可用", 3002);
        }
        if (!Hash::check($this->u_password, $user->u_password)) {
            throw new Exception("密码错误", 3002);
        } else {
            $re = [];
            $re['token'] = $user->u_token;
            $now = new Datetime();
            $now->modify('+ 30 days');
            $re['expire'] = $now->format('Y-m-d H:i:s');
            $re['invite_code'] = $user->u_invite_code;
            $re['id'] = $user->u_id;
            $re['name'] = $user->u_name;
            $re['mobile'] = $user->u_mobile;
            $re['nickname'] = $user->u_nickname;
            $re['is_verified'] = $user->u_is_verified;
            $re['head_img'] = $user->getHeadImg();
            $re['biograph'] = $user->u_biograph;
            $school = DicSchool::find($user->u_school_id);
            if (empty($school)) {
                $re['city'] = null;
                $re['school'] = null;
            } else {
                $re['city'] = DicCity::where('c_id', '=', $school->t_city)->where('c_province_id', '=', $school->t_province)->first()->showInList();
                $re['school'] = $school->showInList();
            }
            $re['gender'] = $user->u_sex;
            $user->load('booths');
            $booths = null;
            if (!empty($user->booths)) {
                foreach ($user->booths as $key => $booth) {
                    $booths[] = $booth->showInLogin();
                }
            }
            $re['boohts'] = $booths;
            $re['import_type'] = 'phone';
            $user->save();
            return $re;
        }
    }

    /**
     * update user info
     * @author Kydz 2015-06-14
     * @return bool
     */
    public function updateUser($imgToken = '')
    {
        $validator = Validator::make(
            [
                'nickname' => $this->u_nickname,
                'school_id' => $this->u_school_id,
                'pass' => $this->u_password,
            ],
            [
                'nickname' => 'sometimes|max:32',
                'school_id' => 'sometimes',
                'pass' => 'sometimes',
            ]
        );
        if ($validator->fails()) {
            $msg = $validator->messages();
            throw new Exception($msg->first(), 1);
        }
        $user = User::chkUserByToken($this->u_token, $this->u_id);

        isset($this->u_nickname) ? $user->u_nickname = $this->u_nickname : '';
        isset($this->u_age) ? $user->u_age = $this->u_age : '';
        isset($this->u_name) ? $user->u_name = $this->u_name : '';
        isset($this->u_sex) ? $user->u_sex = $this->u_sex : '';
        isset($this->u_school_id) ? $user->u_school_id = $this->u_school_id : '';
        isset($this->u_password) ? $user->u_password = Hash::make($this->u_password) : '';

        if ($imgToken) {
            $img = new Img('user', $imgToken);
            $user->u_head_img = $img->getSavedImg($user->u_id, $user->u_head_img);
            $user->u_head_img = implode(',', $user->u_head_img);
        }

        $user->updated_at = date('Y-m-d H:i:s');
        if (!$user->save()) {
            throw new Exception("更新用户信息失败", 1);
        } else {
            return true;
        }
    }

    /**
     * show list data
     * @author Kydz 2015-06-24
     * @return array data row
     */
    public function showInList()
    {
        $data = [];
        $data['id'] = $this->u_id;
        $data['name'] = $this->u_name;
        $data['is_verified'] = $this->u_is_verified;
        $data['verify'] = $this->getVerify();
        $data['nickname'] = $this->u_nickname;
        $data['head_img'] = $this->getHeadImg();
        $data['gender'] = $this->u_sex;
        $data['lat'] = $this->latitude;
        $data['lng'] = $this->longitude;
        $school = null;
        if (isset($this->school)) {
            $school = $this->school->showInList();
        }
        $data['school'] = $school;
        $data['status'] = $this->u_status;
        $data['remark'] = $this->u_remark;
        return $data;
    }

    public function showInImList()
    {
        $data['id'] = $this->u_id;
        $data['is_verified'] = $this->u_is_verified;
        $data['head_img'] = $this->getHeadImg();
        $data['nickname'] = $this->u_nickname;
        return $data;
    }

    public function showInOffice()
    {
        $data = [];
        $data['id'] = $this->u_id;
        $data['name'] = $this->u_name;
        $data['nickname'] = $this->u_nickname;
        $data['head_img'] = $this->getHeadImg();
        $data['is_verified'] = $this->u_is_verified;
        $data['gender'] = $this->u_sex;
        $data['lat'] = $this->latitude;
        $data['lng'] = $this->longitude;
        $data['mobile'] = $this->u_mobile;
        $school = null;
        if (isset($this->school)) {
            $school = $this->school->showInList();
        }
        $data['school'] = $school;
        $data['status'] = $this->u_status;
        $data['remark'] = $this->u_remark;
        return $data;
    }

    /**
     * show detailed data
     * @author Kydz 2015-06-24
     * @return array detai info
     */
    public function showDetail()
    {
        if (!isset($this->u_id)) {
            throw new Exception("no such user", 1);
        }
        $data = [];
        $data['id'] = $this->u_id;
        $data['mobile'] = $this->u_mobile;
        $data['nickname'] = $this->u_nickname;
        $data['age'] = $this->u_age;
        $data['name'] = $this->u_name;
        $data['gender'] = $this->u_sex;
        $data['sex'] = $this->u_sex;
        $data['praise_count'] = $this->u_praise_count;
        if (!empty($this->u_birthday)) {
            $birthday = new DateTime($this->u_birthday);
            $birthday = $birthday->format('Y-m-d');
        } else {
            $birthday = '';
        }
        $data['birth'] = $birthday;
        $data['head_img'] = $this->getHeadImg();
        $data['school_id'] = $this->u_school_id;
        $data['created_at'] = $this->created_at->format('Y-m-d H:i:s');
        $data['follower_count'] = $this->u_follower_count;
        $data['following_count'] = $this->u_following_count;
        $data['status'] = $this->u_status;
        $data['remark'] = $this->u_remark;
        $data['home_imgs'] = Img::toArray($this->u_home_img, true);
        $data['is_verified'] = $this->u_is_verified;
        $data['verify'] = $this->getVerify();
        $data['biograph'] = $this->u_biograph;
        $data['age'] = $this->u_age;
        $data['lat'] = $this->latitude;
        $data['lng'] = $this->longitude;
        if (!empty($this->school)) {
            $data['school'] = $this->school->showInList();
            $data['city'] = DicCity::where('c_id', '=', $this->school->t_city)->where('c_province_id', '=', $this->school->t_province)->first()->showInList();
        }
        return $data;
    }

    /**
     * check if user is legal
     * @author Kydz 2015-06-24
     * @param  string $token user token
     * @return eloquent        user
     */
    public static function chkUserByToken($token, $id = 0)
    {
        if (empty($token)) {
            throw new Exception("请传入token", 3002);
        }
        $query = User::where('u_token', '=', $token)->where('u_status', '=', 1);
        if ($id > 0) {
            $query->where('u_id', '=', $id);
        }
        $user = $query->first();
        if (!isset($user->u_id)) {
            throw new Exception('您的登录已过期， 请重新登录', 3003);
        } else {
            return $user;
        }
    }

    public function showBankCards()
    {
        $cards = [];
        if (isset($this->bankCards)) {
            foreach ($this->bankCards as $key => $card) {
                $cards[] = $card->showInList();
            }
        }
        return $cards;
    }

    public function showContact()
    {
        $data = null;
        if (isset($this->contact)) {
            $data = $this->contact->showDetail();
        }
        return $data;
    }

    public function showSchool()
    {
        $data = null;
        if (isset($this->school)) {
            $data = $this->school->showDetail();
        }
        return $data;
    }

    public static function follow($from, $to)
    {
        $count = Attention::where('u_fans_id', '=', $from->u_id)->where('u_id', '=', $to->u_id)->count();
        if ($count >= 1) {
            throw new Exception("您已经关注过了", 1);
        }
        $attention = new Attention();
        $attention->u_fans_id = $from->u_id;
        $attention->u_id = $to->u_id;
        $attention->created_at = date('Y-m-d H:i:s');
        if (!$attention->save()) {
            throw new Exception("关注用户失败", 1);
        }
        $from->u_following_count += 1;
        $to->u_follower_count += 1;
        if ($from->save() && $to->save()) {
            return true;
        } else {
            throw new Exception("关注用户失败", 1);
        }
    }

    public static function unfollow($from, $to)
    {
        $attention = Attention::where('u_fans_id', '=', $from->u_id)->where('u_id', '=', $to->u_id)->first();
        if (!isset($attention->a_id)) {
            return true;
        }
        if (!$attention->delete()) {
            throw new Exception("取消关注失败", 1);
        }
        $from->u_following_count -= 1;
        if ($from->u_following_count <= 0) {
            $from->u_following_count = 0;
        }
        $to->u_follower_count -= 1;
        if ($to->u_follower_count <= 0) {
            $to->u_follower_count = 0;
        }
        if ($from->save() && $to->save()) {
            return true;
        } else {
            throw new Exception("取消关注失败", 1);
        }
    }

    public function getPosts()
    {
        $posts = null;
        if (isset($this->posts)) {
            foreach ($this->posts as $key => $post) {
                $posts[] = $post->showInList();
            }
        }
        return $posts;
    }

    public function getFollowers()
    {
        $followers = null;
        if (isset($this->followers)) {
            foreach ($this->followers as $key => $follower) {
                $followers[] = $follower->showInList();
            }
        }
        return $followers;
    }

    public function getFollowings()
    {
        $followings = null;
        if (isset($this->followings)) {
            foreach ($this->followings as $key => $follower) {
                $followings[] = $follower->showInList();
            }
        }
        return $followings;
    }

    public function delUser()
    {
        // todo delete rlations with booth, product, set status to 3
    }

    public function getHeadImg()
    {
        if (!empty($this->u_head_img)) {
            $img = Img::toArray($this->u_head_img);
            $img = empty($img) ? '' : reset($img);
            return $img;
        } else {
            $this->load('importQq');
            if (!empty($this->importQq)) {
                if ($this->importQq->u_head_img) {
                    return $this->importQq->u_head_img;
                }
            }
            $this->load('importWechat');
            if (!empty($this->importWechat)) {
                if ($this->importWechat->u_head_img) {
                    return $this->importWechat->u_head_img;
                }
            }
        }
        return '';
    }

    private function getVerify()
    {
        $verify_tag = 'V';
        if ($this->u_is_verified) {
            $verify_tag = '认';
            $verify_type = 1;
        } elseif ($this->u_is_club_verified) {
            $verify_type = 3;
        } elseif ($this->u_type == 2) {
            $verify_type = 2;
        } else {
            $verify_type = 0;
            $verify_tag = '';
        }
        return ['verify_tag' => $verify_tag, 'verify_type' => $verify_type];
    }

    public function fakeUser()
    {
        $this->u_school_id = rand(1, 2569);
        $this->u_password = 111111;
        $this->u_token = $this->getUniqueToken();
        $this->u_password = Hash::make($this->u_password);
        $this->u_status = 1;
        $this->u_change = 1;
        $this->u_type = 1;
        $this->u_sex = 3;
        $this->u_birthday = Tools::getNow('Y-m-d');
        $this->save();
        $this->u_invite_code = $this->getInviteCode();
        $this->save();
    }

    public static function thanksForInvite($code, $u_id)
    {
        if (!$code) {
            return true;
        }
        $user = User::find($code);
        if (empty($user)) {
            throw new Exception("无法找到输入的邀请码", 2001);
        }
        // !!!!! set reward to 0.00, no need to charge user wallet
        $reward = '0.00';
        $log = new LogUserInviteCode();
        $log->u_id = $u_id;
        $log->inviter_id = $user->u_id;
        $log->amount = $reward;
        $log->save();
        // $wallet = UsersWalletBalances::find($user->u_id);
        // $wallet->putIn($reward);
        return true;
    }

    public static function filterByDistance($lat, $lng, $distance)
    {
        $distance = $distance * $distance;
        $sql = 'SELECT u_id from t_users WHERE (power(latitude - ?, 2) + power(longitude - ?, 2) * power(111, 2)) < ?';
        $re = DB::select($sql, [$lat, $lng, $distance]);
        $ids = [];
        $ids[] = 0;
        if (!empty($re)) {
            foreach ($re as $user) {
                $ids[] = $user->u_id;
            }
        }
        return $ids;
    }

    // eloquent realtions
    //
    public function posts()
    {
        return $this->hasMany('Post', 'u_id', 'u_id');
    }

    public function replies()
    {
        return $this->hasMany('PostsReply', 'u_id', 'u_id');
    }

    public function replyPosts()
    {
        return $this->hasMany('PostsReply', 'u_id', 'to_u_id');
    }

    public function activity()
    {
        return $this->hasMany('Activity', 'u_id', 'ac_creat_user');
    }

    public function followingActivites()
    {
        return $this->belongsToMany('Activity', 'activities_follows', 'ac_id', 'ac_id');
    }

    public function signedActivities()
    {
        return $this->hasMany('ActivitiesSignUser', 'u_id', 'u_id');
    }

    public function postPraises()
    {
        return $this->hasMany('PostsPraise', 'u_id', 'u_id');
    }

    public function followers()
    {
        return $this->belongsToMany('User', 'attentions', 'u_id', 'u_fans_id');
    }

    public function followings()
    {
        return $this->belongsToMany('User', 'attentions', 'u_fans_id', 'u_id');
    }

    public function bankCard()
    {
        return $this->hasOne('UserProfileBankcard', 'u_id', 'u_id');
    }

    public function school()
    {
        return $this->hasOne('DicSchool', 't_id', 'u_school_id');
    }

    public function carts()
    {
        return $this->hasMany('Cart', 'u_id', 'u_id');
    }

    public function promoPraises()
    {
        return $this->hasMany('PromotionPraise', 'u_id', 'u_id');
    }

    public function booth()
    {
        return $this->hasOne('Booth', 'u_id', 'u_id');
    }

    public function booths()
    {
        return $this->hasMany('Booth', 'u_id', 'u_id');
    }

    public function profileBase()
    {
        return $this->hasOne('UserProfileBase', 'u_id', 'u_id');
    }

    public function praises()
    {
        return $this->morphToMany('Praise', 'praisable');
    }

    public function favorites()
    {
        return $this->morphToMany('Favorite', 'favoriable');
    }

    public function importQq()
    {
        return $this->hasOne('UserImportQq', 'u_id', 'u_id');
    }

    public function importWechat()
    {
        return $this->hasOne('UserImportWechat', 'u_id', 'u_id');
    }

    public function club()
    {
        return $this->hasOne('Club', 'u_id', 'u_id');
    }
}
