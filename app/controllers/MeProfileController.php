<?php
/**
*
*/
class MeProfileController extends \BaseController
{
    public function profileCheck()
    {
        $u_id = Input::get('u_id', 0);
        $token = Input::get('token', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $bank = TmpUserProfileBankcard::checkProfile($u_id);
            $base = TmpUserProfileBase::checkProfile($u_id);
            $data = ['base' => $base, 'bank' => $bank];
            $re = Tools::reTrue('获取用户资料验证信息成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取用户资料验证信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getUserBase()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $data = [];
            $user = User::chkUserByToken($token, $u_id);
            $user->load('school');
            $profile = TmpUserProfileBase::find($u_id);
            if (empty($profile->u_id)) {
                $profile = new TmpUserProfileBase();
                $entry_year = '';
                $stu_imgs = null;
                $id_imgs = null;
                $profile->u_id = $user->u_id;
                $profile->save();
            } else {
                $entry_year = $profile->u_entry_year;
                $stu_imgs = Img::toArray($profile->u_student_imgs, true);
                $id_imgs = Img::toArray($profile->u_id_imgs, true);
            }

            $data['id'] = $user->u_id;
            $data['name'] = $user->u_name;
            $data['nickname'] = $user->u_nickname;
            $data['biograph'] = $user->u_biograph;
            $data['gender'] = $user->u_sex;
            $data['age'] = $user->u_age;
            $data['home_imgs'] = Img::toArray($user->u_home_img, true);
            $data['head_img'] = $user->getHeadImg();
            $data['mobile'] = $user->u_mobile;
            $data['stu_imgs'] = $stu_imgs;
            $data['id_imgs'] = $id_imgs;
            $data['entry_year'] = $entry_year;
            $data['birth'] = $user->u_birthday;
            $data['major'] = $profile->u_major;
            $data['id_verified'] = $profile->u_is_id_verified;
            $data['stu_verified'] = $profile->u_is_student_verified;
            $data['school'] = empty($user->school) ? null : $user->school->showInList();
            $data['invite_code'] = $user->u_invite_code;

            $data['id_number'] = $profile->u_id_number;
            $data['stu_number'] = $profile->u_student_number;
            $data['emergency_name'] = $profile->em_contact_name;
            $data['emergency_phone'] = $profile->em_contact_phone;
            $data['apartment_no'] = $profile->u_apartment_no;

            $card = TmpUserProfileBankcard::find($u_id);
            if (empty($card)) {
                $data['bank'] = '';
                $data['card_holder_name'] = '';
                $data['card_number'] = '';
            } else {
                $card->load('bank');
                $data['bank'] = $card->bank->showInList();
                $data['card_holder_name'] = $card->b_holder_name;
                $data['card_number'] = $card->b_card_number;
            }

            $club = Club::where('u_id', '=', $u_id)->first();
            if (empty($club)) {
                $data['club_title'] = '';
                $data['club_brief'] = '';
                $data['club_proof_img'] = null;
            } else {
                $data['club_title'] = $club->c_title;
                $data['club_brief'] = $club->c_brief;
                $imgs = Img::toArray($club->c_imgs);
                $data['club_proof_img'] = Img::filterKey('proof_img', $imgs);
                $data['club_proof_img'] = array_pop($data['club_proof_img']);
            }
            $data['is_club_verified'] = $user->u_is_club_verified;

            $data['father_name'] = $profile->u_father_name;
            $data['father_phone'] = $profile->u_father_phone;
            $data['mother_name'] = $profile->u_mother_name;
            $data['mother_phone'] = $profile->u_mother_phone;
            
            $re = Tools::reTrue('获取用户基本信息成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取用户基本信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }
    
    public function postUserBase()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        $name = Input::get('name', '');
        $id_num = Input::get('id_number', '');
        $id_school = Input::get('id_school', 0);
        $entry_year = Input::get('entry_year', '');
        $major = Input::get('major', '');
        $stu_num = Input::get('stu_num', '');
        $em_name = Input::get('emergency_name', '');
        $em_phoen = Input::get('emergency_phone', '');
        $father_name = Input::get('father_name', '');
        $age = Input::get('age', '');
        $father_phone = Input::get('father_phone', '');
        $mother_name = Input::get('mother_name', '');
        $mother_phone = Input::get('mother_phone', '');
        $apartment_no = Input::get('apartment_no', '');
        $birth = Input::get('birth', '');
        $mobile = Input::get('mobile', '');

        // club info
        $club_title = Input::get('club_title', '');
        $club_brief = Input::get('club_brief', '');

        $img_token = Input::get('img_token', '');
        $img_token_2 = Input::get('img_token_2', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $profile = TmpUserProfileBase::find($u_id);
            if (empty($profile)) {
                $profile = new TmpUserProfileBase();
                $profile->u_id = $u_id;
            }

            if ($id_school) {
                $profile->s_id = $id_school;
                $user->u_school_id = $id_school;
            }

            if ($mobile) {
                $user->u_mobile = $mobile;
            }

            $user->u_name = $name;
            $user->u_age = $age;
            $user->u_birthday = $birth;
            $profile->u_id_number = $id_num;
            $profile->u_entry_year = $entry_year;
            $profile->u_major = $major;
            $profile->u_student_number = $stu_num;
            $profile->em_contact_phone = $em_phoen;
            $profile->em_contact_name = $em_name;
            $profile->u_father_name = $father_name;
            $profile->u_father_phone = $father_phone;
            $profile->u_mother_name = $mother_name;
            $profile->u_mother_phone = $mother_phone;
            $profile->u_apartment_no = $apartment_no;
            $profile->register();
            if ($img_token) {
                $imgObj = new Img('user', $img_token);
                $imgs = $imgObj->getSavedImg($u_id, implode(',', [$profile->u_id_imgs, $profile->u_student_imgs]), true);
                $stu_imgs = Img::filterKey('student_img_', $imgs);
                $id_imgs = Img::filterKey('identity_img_', $imgs);
                $profile->u_student_imgs = implode(',', $stu_imgs);
                $profile->u_id_imgs = implode(',', $id_imgs);
            }

            $this->saveClubInfo($user);
            $user->save();
            $profile->save();
            $re = Tools::reTrue('提交信息成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '提交信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function putUserBase()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        $name = Input::get('name');
        $nickname = Input::get('nickname', '');
        $gender = Input::get('gender', '');
        $bio = Input::get('biograph', '');
        $id_number = Input::get('id_number', '');
        $id_school = Input::get('id_school', 0);
        $entry_year = Input::get('entry_year', '');
        $major = Input::get('major', '');
        $stu_num = Input::get('stu_num', '');
        $mobile = Input::get('mobile', '');
        $age = Input::get('age', '');
        $birth = Input::get('birth', '');

        $img_token = Input::get('img_token', '');

        $modified_img = Input::get('modified_img', '');
        $modified_img_index = Input::get('modified_img_index', '');

        if ($modified_img) {
            $modified_img = explode(',', $modified_img);
        }

        try {
            $user = User::chkUserByToken($token, $u_id);
            $profile = TmpUserProfileBase::find($u_id);
            if (empty($profile)) {
                $profile = new TmpUserProfileBase();
                $profile->u_id = $u_id;
            }
            $user->u_name = $name;
            $user->u_nickname = $nickname;
            $user->u_sex = $gender;
            $user->u_biograph = $bio;
            $user->u_age = $age;
            $user->u_birthday = $birth;

            if ($id_school) {
                $profile->s_id = $id_school;
                $user->u_school_id = $id_school;
            }

            if ($mobile) {
                $user->u_mobile = $mobile;
            }
            $profile->u_entry_year = $entry_year;
            $profile->u_major = $major;
            $profile->u_id_number = $id_number;
            $profile->u_student_number = $stu_num;

            if (is_numeric($modified_img_index)) {
                $imgObj = new Img('user', $img_token);
                $new_paths = [];
                if (!empty($modified_img)) {
                    foreach ($modified_img as $old_path) {
                        $new_path = $imgObj->reindexImg($u_id, $modified_img_index, $old_path);
                        $new_paths[] = $new_path;
                        $modified_img_index++;
                    }
                    $to_delete = Img::toArray($user->u_home_img);
                    foreach ($to_delete as $obj) {
                        if (!in_array($obj, $new_paths)) {
                            $imgObj->remove($u_id, $obj);
                        }
                    }
                    $new_paths = Img::attachHost($new_paths);
                    
                    $user->u_home_img = implode(',', $new_paths);
                }
            }

            if ($img_token) {
                $imgObj = new Img('user', $img_token);
                $imgs = $imgObj->getSavedImg($u_id, implode(',', [$profile->u_id_imgs, $profile->u_student_imgs, $user->u_home_img, $user->u_head_img]), true);
                $stu_imgs = Img::filterKey('student_img_', $imgs);
                $id_imgs = Img::filterKey('identity_img_', $imgs);
                $home_imgs = Img::filterKey('home_img_', $imgs);
                $head_img = Img::filterKey('head_img', $imgs);
                if (!empty($modified_img)) {
                    foreach ($modified_img as $del) {
                        if (array_key_exists($del, $home_imgs)) {
                            unset($home_imgs[$del]);
                        }
                    }
                }
                $profile->u_student_imgs = implode(',', $stu_imgs);
                $profile->u_id_imgs = implode(',', $id_imgs);
                $user->u_home_img = implode(',', $home_imgs);
                $user->u_head_img = implode(',', $head_img);
            }

            $this->saveClubInfo($user);
            $profile->save();
            $user->save();
            $re = Tools::reTrue('提交信息成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '提交信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getBank()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', '');
        
        try {
            $user = User::chkUserByToken($token, $u_id);
            $card = TmpUserProfileBankcard::find($u_id);
            if (!isset($card->u_id)) {
                $data['bank'] = null;
                $data['card_num'] = '';
                $data['card_holder'] = '';
                $data['holder_phone'] = '';
                $data['holder_ID'] = '';
            } else {
                $card->load('bank');
                $data['bank'] = $card->bank->showInList();
                $data['card_num'] = $card->b_card_number;
                $data['card_holder'] = $card->b_holder_name;
                $data['holder_phone'] = $card->b_holder_phone;
                $data['holder_ID'] = $card->b_holder_id_number;
            }
            $re = Tools::reTrue('获取用户银行卡成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取用户银行卡失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postBank()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', '');

        // id bank
        $bankId = Input::get('bank', 0);
        // bank card number
        $cardNum = Input::get('card_num', '');
        // card holder name
        $cardHolderName = Input::get('holder_name', '');
        // card holder phone
        $cardHolderPhone = Input::get('holder_phone', '');
        // card holder identy
        $cardHolderID = Input::get('holder_id', '');

        try {
            $user = User::chkUserByToken($token, $u_id);

            $card = TmpUserProfileBankcard::find($u_id);
            if (!isset($card->u_id)) {
                $card = new TmpUserProfileBankcard();
            }
            if ($card->u_status == 1) {
                throw new Exception("您的审核已经通过", 3002);
            }
            $card->u_id = $u_id;
            $card->b_id = $bankId;
            $card->b_card_number = $cardNum;
            $card->b_holder_name = $cardHolderName;
            $card->b_holder_phone = $cardHolderPhone;
            $card->b_holder_id_number = $cardHolderID;
            $card->register();
            $re = Tools::reTrue('提交银行卡信息成功');
        } catch (Exception $e) {
            // TmpUserProfileBankcard::clearByUser($u_id);
            $re = Tools::reFalse($e->getCode(), '提交银行卡信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    private function saveClubInfo($user)
    {
        $title = Input::get('club_title', '');
        $brief = Input::get('club_brief', '');
        $img_token = Input::get('img_token_2', '');
        if ($title) {
            $club = Club::where('u_id', '=', $user->u_id)->first();
            if (empty($club)) {
                $club = new Club();
                $club->u_id = $user->u_id;
                $club->s_id = $user->u_school_id;
                $club->c_title = $title;
                $club->addClub();
            }
            $club->c_title = $title;
            $club->c_brief = $brief;
            $c_id = $club->c_id;
            $imgObj = new Img('club', $img_token);
            $club->c_imgs = $imgObj->getSavedImg($c_id, $club->c_imgs);
            $club->save();
        }
        return true;
    }
}
