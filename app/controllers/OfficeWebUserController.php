<?php
/**
*
*/
class OfficeWebUserController extends \BaseController
{
    public function listUserProfiles()
    {
        $per_page = Input::get('per_page', 10000000);
        $s_id = Input::get('s_id', 0);
        $s_name = Input::get('s_name', '');
        $name = Input::get('name', '');
        $has_id_img = Input::get('has_id_img', -1);
        $has_stu_img = Input::get('has_stu_img', -1);


        try {
            $query = DB::table('users')
            ->select('users.u_id AS id', 'users.u_mobile', 'users.u_name', 'users.u_nickname', 'users.u_status', 'users.u_remark', 'users.u_is_verified as is_verified', 'users.u_head_img', 'dic_schools.t_name', 'tmp_user_profile_bases.u_status AS base_status', 'tmp_user_profile_bases.u_id_imgs', 'tmp_user_profile_bases.u_student_imgs', 'tmp_user_profile_bankcards.b_status AS bank_status', 'tmp_user_profile_bases.u_is_id_verified AS id_verified', 'tmp_user_profile_bases.u_is_student_verified AS stu_verified', 'clubs.c_status AS club_status')
            ->leftJoin('tmp_user_profile_bases', function ($q) {
                $q->on('users.u_id', '=', 'tmp_user_profile_bases.u_id');
            })->leftJoin('tmp_user_profile_bankcards', function ($q) {
                $q->on('users.u_id', '=', 'tmp_user_profile_bankcards.u_id');
            })->leftJoin('dic_schools', function ($q) {
                $q->on('dic_schools.t_id', '=', 'users.u_school_id');
            })->leftJoin('clubs', function ($q) {
                $q->on('clubs.u_id', '=', 'users.u_id');
            });

            if ($s_id) {
                $query = $query->where('users.u_school_id', '=', $s_id);
            }

            if ($s_name) {
                $query = $query->where('dic_schools.t_name', 'LIKE', '%'.$s_name.'%');
            }

            if ($name) {
                $query = $query->where(function ($q) use ($name) {
                    $q->where('users.u_name', 'LIKE', '%'.$name.'%')->orWhere('users.u_nickname', 'LIKE', '%'.$name.'%')->orWhere('users.u_mobile', 'LIKE', '%'.$name.'%');
                });
            }

            if ($has_id_img == 1) {
                $query = $query->whereNotNull('tmp_user_profile_bases.u_id_imgs')->where('tmp_user_profile_bases.u_id_imgs', '<>', '');
            } elseif ($has_id_img == 0) {
                $query = $query->whereNull('tmp_user_profile_bases.u_id_imgs')->where('tmp_user_profile_bases.u_id_imgs', '=', '');
            }

            if ($has_stu_img == 1) {
                $query = $query->whereNotNull('tmp_user_profile_bases.u_student_imgs')->where('tmp_user_profile_bases.u_student_imgs', '<>', '');
            } elseif ($has_stu_img == 0) {
                $query = $query->whereNull('tmp_user_profile_bases.u_student_imgs')->where('tmp_user_profile_bases.u_student_imgs', '=', '');
            }

            $list = $query->paginate($per_page);
            $array = $list->toArray();
            $data['rows'] = [];
            foreach ($array['data'] as $key => $userProfile) {
                if ($userProfile->u_id_imgs) {
                    $userProfile->has_id_img = 1;
                } else {
                    $userProfile->has_id_img = 0;
                }
                if ($userProfile->u_student_imgs) {
                    $userProfile->has_student_img = 1;
                } else {
                    $userProfile->has_student_img = 0;
                }
                unset($userProfile->u_id_imgs);
                unset($userProfile->u_student_imgs);
                $data['rows'][] = $userProfile;
            }
            $data['total'] = $list->getTotal();
            $re = Tools::reTrue('获取用户信息成功', $data, $list);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取用户信息失败:'.$e->getMessage());
        }
        return Response::Json($re);
    }

    // abondan
    public function getUserProfile($id)
    {
        try {
            $user = User::find($id);
            $bank = TmpUserProfileBankcard::find($id);
            $base = TmpUserProfileBase::find($id);
            $club = Club::where('u_id', '=', $id)->first();

            if (empty($user)) {
                throw new Exception("查找的用户的不存在", 10001);
            } else {
                $user_data = $user->showDetail();
            }

            if (empty($bank)) {
                $user_bank = null;
            } else {
                $user_bank = $bank->showDetail();
            }

            if (empty($base)) {
                $user_base = null;
            } else {
                $base->load('school');
                $user_base = $base->showDetail();
            }

            if (empty($club)) {
                $club_info = null;
            } else {
                $club_info = $club->showDetail();
            }

            $data = [];
            $data['bank'] = $user_bank;
            $data['base'] = $user_base;
            $data['club'] = $club_info;

            $re = Tools::reTrue('获取用户信息成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取用户信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function censorUserProfileBase($id)
    {
        $check = Input::get('check', 0);
        $remark = Input::get('remark', '');

        try {
            if ($check == 0) {
                if (!$remark) {
                    throw new Exception("备注不能为空", 10001);
                }
            }

            $tmp_base = TmpUserProfileBase::find($id);
            if (empty($tmp_base)) {
                throw new Exception("查找的用户信息不存在", 10001);
            }

            if ($tmp_base->u_status == 1) {
                throw new Exception("审核已经通过了", 10002);
            }

            $base = UserProfileBase::find($id);
            if (empty($base)) {
                $base = new UserProfileBase();
            }

            $user = User::find($id);

            if ($check == 1) {
                $base->u_id = $tmp_base->u_id;
                $base->u_id_number = $tmp_base->u_id_number;
                $base->u_id_imgs = $tmp_base->u_id_imgs;
                $base->u_is_id_verified = $tmp_base->u_is_id_verified = 1;
                $base->s_id = $tmp_base->s_id;
                $base->u_entry_year = $tmp_base->u_entry_year;
                $base->u_major = $tmp_base->u_major;
                $base->u_student_number = $tmp_base->u_student_number;
                $base->u_student_imgs = $tmp_base->u_student_imgs;
                $base->u_is_student_verified = $tmp_base->u_is_student_verified = 1;
                $base->em_contact_phone = $tmp_base->em_contact_phone;
                $base->em_contact_name = $tmp_base->em_contact_name;
                $base->u_father_name = $tmp_base->u_father_name;
                $base->u_father_phone = $tmp_base->u_father_phone;
                $base->u_mother_name = $tmp_base->u_mother_name;
                $base->u_mother_phone = $tmp_base->u_mother_phone;
                $base->u_home_address = $tmp_base->u_home_address;
                $base->u_apartment_no = $tmp_base->u_apartment_no;
                $base->u_status = 1;
                $base->save();
                $tmp_base->u_status = 1;
                $tmp_base->remark = '';
                $user->u_is_verified = 1;
            } else {
                $tmp_base->u_status = 2;
                $tmp_base->remark = $remark;
                $user->u_is_verified = 0;
            }
            $user->save();
            $tmp_base->censor();
            $re = Tools::reTrue('审核用户联系人信息成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '审核用户联系人信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function censorUserProfileBank($id)
    {
        $check = Input::get('check', 0);
        $remark = Input::get('remark', '');

        try {
            if ($check == 0) {
                if (!$remark) {
                    throw new Exception("备注不能为空", 10001);
                }
            }

            $tmp_bank = TmpUserProfileBankcard::find($id);
            if (empty($tmp_bank)) {
                throw new Exception("查找的用户信息不存在", 10001);
            }

            if ($tmp_bank->b_status == 1) {
                throw new Exception("审核已经通过了", 10002);
            }
            $bank = UserProfileBankcard::find($id);
            if (empty($bank)) {
                $bank = new UserProfileBankcard();
            }

            if ($check == 1) {
                $bank->u_id = $tmp_bank->u_id;
                $bank->b_id = $tmp_bank->b_id;
                $bank->b_card_number = $tmp_bank->b_card_number;
                $bank->b_holder_name = $tmp_bank->b_holder_name;
                $bank->b_holder_phone = $tmp_bank->b_holder_phone;
                $bank->b_holder_id_number = $tmp_bank->b_holder_id_number;
                $bank->save();
                $tmp_bank->b_status = 1;
                $tmp_bank->remark = '';
            } else {
                $tmp_bank->b_status = 2;
                $tmp_bank->remark = $remark;
            }
            $tmp_bank->censor();
            $re = Tools::reTrue('审核用户银行信息成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '审核用户银行信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function censorUserProfileIdentity($id)
    {
        $check = Input::get('check');

        try {
            $tmp = TmpUserProfileBase::find($id);
            $base = UserProfileBase::find($id);
            $log = new LogUserProfileCensors();
            $log->u_id = $id;
            $log->cate = 'base';
            $log->admin_id = Tools::getAdminId();
            if ($check == 1) {
                $tmp->u_is_id_verified = 1;
                if (!empty($base)) {
                    $base->u_is_id_verified = 1;
                }
                if ($tmp->u_is_student_verified) {
                    $user = User::find($id);
                    $user->u_is_verified = 1;
                    $user->save();
                }
                $log->content = '认证用户身份证信息: 通过';
            } else {
                $tmp->u_is_id_verified = 2;
                if (!empty($base)) {
                    $base->u_is_id_verified = 2;
                }
                $log->content = '认证用户身份证信息: 不通过';
            }
            $msg = new MessageDispatcher($id);
            $msg->fireTextToUser($log->content);
            $log->addLog();
            $tmp->save();
            if (!empty($base)) {
                $base->save();
            }
            $re = Tools::reTrue('审核用户身份证信息成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '审核用户身份证信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function censorUserProfileStudent($id)
    {
        $check = Input::get('check');

        try {
            $tmp = TmpUserProfileBase::find($id);
            $base = UserProfileBase::find($id);
            $log = new LogUserProfileCensors();
            $log->u_id = $id;
            $log->cate = 'base';
            $log->admin_id = Tools::getAdminId();
            if ($check == 1) {
                $tmp->u_is_student_verified = 1;
                if (!empty($base)) {
                    $base->u_is_student_verified = 1;
                }
                if ($tmp->u_is_id_verified) {
                    $user = User::find($id);
                    $user->u_is_verified = 1;
                    $user->save();
                }
                $log->content = '认证用户学生证信息: 通过';
            } else {
                $tmp->u_is_student_verified = 2;
                if (!empty($base)) {
                    $base->u_is_student_verified = 2;
                }
                $log->content = '认证用户学生证信息: 不通过';
            }
            $msg = new MessageDispatcher($id);
            $msg->fireTextToUser($log->content);
            $log->addLog();
            $tmp->save();
            if (!empty($base)) {
                $base->save();
            }
            $re = Tools::reTrue('审核用户身份证信息成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '审核用户身份证信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function censorUserProfileClub($id)
    {
        $check = Input::get('check');
        $remark = Input::get('remark');

        try {
            $club = Club::where('u_id', '=', $id)->first();
            if (empty($club)) {
                throw new Exception("该用户没有可用的社团", 10001);
            }
            if ($check == 1) {
                $club->c_status = 1;
                $club->u_is_club_verified = 1;
            } else {
                $club->c_status = 2;
                $club->u_is_club_verified = 0;
            }
            $club->remark = $remark;
            $club->censor();
            $re = Tools::reTrue('审核社团信息成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '审核社团信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function enable($id)
    {
        $status = Input::get('status', 0);
        $remark = Input::get('remark', '');

        try {
            $user = User::find($id);
            if (empty($user)) {
                throw new Exception("没有找到请求的用户", 10001);
            }
            $log = new LogUserProfileCensors();
            $log->u_id = $user->u_id;
            $log->cate = 'base';
            $log->admin_id = Tools::getAdminId();
            if ($status == 1) {
                $status = 1;
                $log->content = '用户状态修改为: 启用';
            } else {
                $status = -1;
                $log->content = '用户状态修改为: 禁用';
            }
            $log->addLog();
            $user->u_status = $status;
            $user->u_remark = $remark;
            $user->save();
            $re = Tools::reTrue('用户状态修改成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '用户状态修改失败:'.$e->getMessage());
        }
        return Response::json($re);
    }
}
