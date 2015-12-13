<?php
/**
*
*/
use Illuminate\Support\Collection;

class MeController extends \BaseController
{
    /**
     * get my-info
     * @author Kydz 2015-06-26
     * @return array detailed my info
     */
    public function me()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        try {
            $user = User::chkUserByToken($token, $u_id);
            $user = User::with('bankCards.bank', 'contact', 'school')->find($user->u_id);
            $userInfo = $user->showDetail();
            $cards = $user->showBankCards();
            $contact = $user->showContact();
            $school = $user->showSchool();
            $data = ['user_info' => $userInfo, 'cards' => $cards, 'contact' => $contact, 'school' => $school];
            $re = ['result' => 2000, 'data' => $data, 'info' => '获取用户成功'];
        } catch (Exception $e) {
            $code = 2001;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => $e->getMessage()];
        }

        return Response::json($re);
    }

    /**
     * get my posts
     * @author Kydz 2015-07-04
     * @return array posts info
     */
    public function myPosts()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $keyWord = Input::get('key');
        try {
            $user = User::chkUserByToken($token, $u_id);
            $user = User::with([
                'posts' => function ($q) use ($keyWord) {
                    $q->where('p_status', '=', 1);
                    if (!empty($keyWord)) {
                        $q->where('p_title', 'LIKE', '%'.$keyWord.'%');
                    }
                },
                'posts.replys' => function ($q) {
                    $q->where('r_status', '=', 1);
                },
                'posts.replys.user',
                'posts.replys.toUser',
                'posts.praises',
                ])->find($user->u_id);
            $posts = $user->getPosts();
            $re = ['result' => 2000, 'data' => $posts, 'info' => '获取用户帖子成功'];
        } catch (Exception $e) {
            $code = 2001;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => $e->getMessage()];
        }
        return Response::json($re);
    }

    /**
     * get my followers
     * @author Kydz 2015-07-04
     * @return json followers list
     */
    public function myFollowers()
    {
        $u_id = Input::get('u_id');
        $token = Input::get('token');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $data = $this->getUserFollowers($user->u_id);
            $re = ['result' => 2000, 'data' => $data, 'info'=> '获取我的粉丝成功'];
        } catch (Exception $e) {
            $code = 2001;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => $e->getMessage()];
        }
        return Response::json($re);
    }

    /**
     * get my followings
     * @author Kydz 2015-07-04
     * @return json followings list
     */
    public function myFollowings()
    {
        $u_id = Input::get('u_id');
        $token = Input::get('token');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $data = $this->getUserFollowings($user->u_id);
            $re = ['result' => 2000, 'data' => $data, 'info' => '获取我关注的人成功'];
        } catch (Exception $e) {
            $code = 2001;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => $e->getMessage()];
        }
        return Response::json($re);
    }

    /**
     * reset pass word
     * @author Kydz 2015-07-04
     * @return json n/a
     */
    public function resetPass()
    {
        $mobile = Input::get('mobile');
        $vcode = Input::get('vcode');
        $newPass = Input::get('pass');

        try {
            // AES crypt
            $newPass = Tools::qnckDecrytp($newPass);
            if (!$newPass) {
                throw new Exception("密码错误", 2001);
            }

            $user = User::where('u_mobile', '=', $mobile)->first();

            // chcek if mobile exsits
            if (!isset($user->u_id)) {
                throw new Exception("没有查找到与该手机号码绑定的用户", 2001);
            }
            $phone = new Phone($mobile);

            if ($phone->authVCode($vcode)) {
                $user->u_password = $newPass;
                $user->updateUser();
            }
            $re = Tools::reTrue('重置密码成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '重置密码失败:'.$e->getMessage());
        }

        return Response::json($re);
    }

    public function resetPassForWechat()
    {
        $mobile = Input::get('mobile');
        $vcode = Input::get('vcode');
        $newPass = Input::get('pass');

        try {
            $user = User::where('u_mobile', '=', $mobile)->first();

            // chcek if mobile exsits
            if (!isset($user->u_id)) {
                throw new Exception("没有查找到与该手机号码绑定的用户", 2001);
            }
            $phone = new Phone($mobile);

            if ($phone->authVCode($vcode)) {
                $user->u_password = $newPass;
                $user->updateUser();
            }
            $re = Tools::reTrue('重置密码成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '重置密码失败:'.$e->getMessage());
        }

        return Response::json($re);
    }

    /**
     * replies from me
     * @author Kydz
     * @return json reply list
     */
    public function myReply()
    {
        $u_id = Input::get('u_id');
        $token = Input::get('token');
        try {
            $user = User::chkUserByToken($token, $u_id);
            $data = PostsReply::with(['post', 'toUser'])->where('u_id', '=', $u_id)->where('r_status', '=', 1)->paginate(10);
            $list = [];
            foreach ($data as $key => $reply) {
                $list[] = $reply->showInList();
            }
            $re = ['result' => 2000, 'data' => $list, 'info' => '获取我的回复成功'];
        } catch (Exception $e) {
            $code = 2001;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => $e->getMessage()];
        }
        return Response::json($re);
    }

    /**
     * praised from me
     * @author Kydz
     * @return json praised list
     */
    public function myPraise()
    {
        $u_id = Input::get('u_id');
        $token = Input::get('token');
        try {
            $user = User::chkUserByToken($token, $u_id);
            $data = PostsPraise::with(['post'])->where('u_id', '=', $u_id)->paginate(10);
            $list = [];
            foreach ($data as $key => $praise) {
                $list[] = $praise->showInList();
            }
            $re = ['result' => 2000, 'data' => $list, 'info' => '获取的赞成功'];
        } catch (Exception $e) {
            $code = 2001;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => $e->getMessage()];
        }
        return Response::json($re);
    }

    public function postBooth()
    {
        // base infos
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', '');

        // booth type
        $boothType = Input::get('type');
        // product category
        $productCate = Input::get('prod_cate');
        // booth title
        $boothTitle = Input::get('title');
        // booth position
        $boothLng = Input::get('lng');
        $boothLat = Input::get('lat');
        // product source
        $productSource = Input::get('prod_source');
        // customer group
        $cusomerGroup = Input::get('cust_group');
        // promo strategy
        $promoStratege = Input::get('promo_strategy');
        // with fund
        $withFund = Input::get('fund', 0);
        $booth_cate = Input::get('cate', 0);

        // profit ratio
        $profitRate = Input::get('profit');
        // loan amount
        $loan = Input::get('loan');
        // how to drow loan
        $loanSchema = Input::get('loan_schema', '');

        DB::beginTransaction();
        try {
            $user = User::chkUserByToken($token, $u_id);
            $user->load(['school']);

            $booth = Booth::where('u_id', '=', $u_id)->first();
            if (empty($booth)) {
                $booth = new Booth();
            } else {
                if ($booth->b_status == 1) {
                    throw new Exception("您已经申请过店铺了", 7001);
                }
            }
            $school = $user->school;

            $booth->s_id = $school->t_id;
            $booth->c_id = $school->t_city;
            $booth->pv_id = $school->t_province;
            $booth->u_id = $u_id;
            $booth->b_title = $boothTitle;
            $booth->b_desc = '';
            $booth->latitude = $boothLat;
            $booth->longitude = $boothLng;
            $booth->b_product_source = $productSource;
            $booth->b_product_category = $productCate;
            $booth->b_customer_group = $cusomerGroup;
            $booth->b_promo_strategy = $promoStratege;
            $booth->b_with_fund = $withFund;
            $booth->b_type = $boothType;
            $booth->b_cate = $booth_cate;
            $b_id = $booth->register();
            
            if ($withFund == 1) {
                $fund = Fund::where('b_id', '=', $booth->b_id)->first();
                if (empty($fund)) {
                    $fund = new Fund();
                } else {
                    if ($fund->t_status > 2) {
                        throw new Exception("基金已经发放", 1);
                    }
                }
                $fund->u_id = $u_id;
                $fund->t_apply_money = $loan;
                $fund->b_id = $b_id;
                $fund->t_profit_rate = $profitRate;
                $f_id = $fund->apply();

                $schema = 0;
                $allotedAmount = 0;

                $loanSchema = json_decode($loanSchema, true);
                if (!is_array($loanSchema)) {
                    throw new Exception("请传入正确的提款计划", 7001);
                }

                // clear all exists schema
                DB::table('repayments')->where('f_id', '=', $f_id)->delete();

                foreach ($loanSchema as $key => $percentage) {
                    $percentage = $percentage / 100;
                    $schema ++;
                    if ($schema == count($loanSchema)) {
                        $amount = $loan - $allotedAmount;
                    } else {
                        $amount = $loan * $percentage;
                        $allotedAmount += $amount;
                    }
                    $repayment = new Repayment();
                    $repayment->f_id = $f_id;
                    $repayment->f_re_money = $amount;
                    $repayment->f_schema = $schema;
                    $repayment->f_percentage = $percentage * 100;
                    $repayment->apply();
                }
            } else {
                // if without fund, no need to censor
                $booth->b_status = 1;
                $booth->save();
            }
            $re = Tools::reTrue('申请成功');
            DB::commit();
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '申请失败:'.$e->getMessage());
            DB::rollback();
        }

        return Response::json($re);
    }

    public function listBooth()
    {
        // echo 123;exit;
        $u_id = Input::get('u_id', '');
        $token = Input::get('token');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $data = Booth::with(['user' => function ($q) {
                $q->with(['school']);
            }])
            ->where('u_id', '=', $u_id)->get();
            $list = [];
            foreach ($data as $key => $booth) {
                $tmp = $booth->showDetail();
                $products_count = Product::where('b_id', '=', $booth->b_id)->where('p_status', '=', 1)->count();
                $tmp['prodct_count'] = $products_count;
                $list[] = $tmp;
            }
            $re = ['result' => 2000, 'data' => $list, 'info' => '获取我的所有店铺成功'];
        } catch (Exception $e) {
            $code = 7001;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => '获取我的所有店铺失败:'.$e->getMessage()];
        }

        return Response::json($re);
    }

    public function booth($id)
    {
        $u_id = Input::get('u_id', 0);
        $token = Input::get('token', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $booth = Booth::find($id);
            if (empty($booth->b_id) || $booth->u_id != $u_id) {
                throw new Exception("无法获取到请求的店铺", 1);
            }
            if ($booth->b_status != 1) {
                throw new Exception("店铺状态不可用, 请联系客服", 1);
            }
            $booth->load('fund');
            $fund_info = null;
            if (!empty($booth->fund)) {
                $booth->fund->load('loans');
                $fund_info = $booth->fund->showDetail();
            }
            $boothInfo = $booth->showDetail();
            $boothInfo['fund_info'] = $fund_info;
            $re = ['result' => 2000, 'data' => $boothInfo, 'info' => '获取我的店铺成功'];
        } catch (Exception $e) {
            $code = 7001;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => '获取我的店铺失败:'.$e->getMessage()];
        }
        return Response::json($re);
    }

    public function getBoothStatus($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $booth = Booth::find($id);
            if ($booth->u_id != $u_id) {
                throw new Exception("没有权限操作改店铺", 7001);
            }
            $data = [];
            $data['open'] = $booth->b_open;
            $data['open_from'] = $booth->b_open_from;
            $data['open_to'] = $booth->b_open_to;
            $data['open_on'] = explode(',', $booth->b_open_on);
            $data['desc'] = $booth->b_desc;
            $data['logo'] = $booth->getLogo();
            $data['title'] = $booth->b_title;
            $re = Tools::reTrue('获取店铺状态信息成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取店铺状态信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function putBoothStatus($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $open = Input::get('open', 1);
        $openFrom = Input::get('open_from', '');
        $openTo = Input::get('open_to', '');
        $openOn = Input::get('open_on');
        $logo = Input::get('logo', '');
        $desc = Input::get('desc', '');
        $title = Input::get('title', '');

        $img_token = Input::get('img_token', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $booth = Booth::find($id);
            if (empty($booth->b_id) || $booth->u_id != $u_id) {
                throw new Exception("无法获取到请求的店铺", 7001);
            }
            $booth->b_open = $open;
            $booth->b_open_from = $openFrom;
            $booth->b_open_to = $openTo;
            $booth->b_open_on = $openOn;
            $booth->b_desc = $desc;
            $booth->b_title = $title;
            if ($booth->b_type == 2 && $img_token) {
                $imgObj = new Img('booth', $img_token);
                $imgs = $imgObj->getSavedImg($id, $booth->b_imgs, true);
                $booth->b_imgs = implode(',', $imgs);
            } elseif (!$img_token) {
                $imgs = Img::toArray($booth->b_imgs, true);
                $imgs['logo'] = 'logo.'.$logo;
                $booth->b_imgs = implode(',', $imgs);
            }
            $booth->save();


            $re = Tools::reTrue('保存店铺状态成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '保存店铺状态失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getDetail()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', '');
        
        try {
            $user = User::chkUserByToken($token, $u_id);
            $detail = TmpUsersDetails::find($u_id);
            $data = [];
            $data['name'] = $user->u_name;
            $data['bio'] = $user->u_biograph;
            $data['interests'] = $user->u_interests;
            if (!isset($detail->u_id)) {
                $data['id_num'] = '';
                $data['id_img'] = '';
                $data['home_addr'] = '';
                $data['mo_name'] = '';
                $data['mo_phone'] = '';
                $data['fa_name'] = '';
                $data['fa_phone'] = '';
            } else {
                $data['id_num'] = $detail->u_identity_number;
                $imgs = Img::toArray($detail->u_identity_img, true);
                $data['id_img'] = $imgs;
                $data['home_addr'] = $detail->u_home_adress;
                $data['mo_name'] = $detail->u_mother_name;
                $data['mo_phone'] = $detail->u_mother_telephone;
                $data['fa_name'] = $detail->u_father_name;
                $data['fa_phone'] = $detail->u_father_telephone;
            }
            $re = ['result' => 2000, 'data' => $data, 'info' => '获取用户详细成功'];
        } catch (Exception $e) {
            $code = 3002;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => '获取用户详细失败:'.$e->getMessage()];
        }
        return Response::json($re);
    }

    public function postDetail()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', '');

        $name = Input::get('name', '');

        $idNum = Input::get('id_num', '');
        // home address
        $homeAddr = Input::get('home_addr');
        // mother name
        $moName = Input::get('mo_name');
        // mother phone
        $moPhone = Input::get('mo_phone');
        // father name
        $faName = Input::get('fa_name');
        // father phone
        $faPhone = Input::get('fa_phone');

        $imgToken = Input::get('img_token');

        try {
            $user = User::chkUserByToken($token, $u_id);

            $user_detail = TmpUsersDetails::find($u_id);
            if (!isset($user_detail->u_id)) {
                $user_detail = new TmpUsersDetails();
            }
            if ($user_detail->u_status == 1) {
                throw new Exception("您的审核已经通过", 3002);
            }

            $user->u_name = $name;
            $user->save();

            $user_detail->u_id = $u_id;
            $user_detail->u_identity_number = $idNum;
            $user_detail->u_home_adress = $homeAddr;
            $user_detail->u_father_name = $faName;
            $user_detail->u_father_telephone = $faPhone;
            $user_detail->u_mother_name = $moName;
            $user_detail->u_mother_telephone = $moPhone;
            $user_detail->register();

            if ($imgToken) {
                $imgObj = new Img('user', $imgToken);
                $imgs = $imgObj->getSavedImg($u_id, '', true);
                $id_img = [];
                foreach ($imgs as $k => $img) {
                    if ($k == 'identity_img_front' || $k == 'identity_img_back') {
                        $id_img[] = $img;
                    }
                }
                $user_detail->u_identity_img = implode(',', $id_img);
                $user_detail->save();
            }


            $re = ['result' => 2000, 'data' => [], 'info' => '提交详细信息审核成功'];
        } catch (Exception $e) {
            TmpUsersDetails::clearByUser($u_id);
            $code = 3002;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => '提交详细信息审核失败:'.$e->getMessage()];
        }
        return Response::json($re);
    }

    // abandon
    public function getContact()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', '');
        
        try {
            $user = User::chkUserByToken($token, $u_id);
            $contact = TmpUserProfileBase::find($u_id);
            $contact->load('school');
            $data = [];
            if (!isset($contact->u_id)) {
                $data['th_name'] = '';
                $data['th_phone'] = '';
                $data['fr_name_1'] = '';
                $data['fr_phone_1'] = '';
                $data['fr_name_2'] = '';
                $data['fr_phone_2'] = '';
                $data['stu_num'] = '';
                $data['stu_img'] = '';
                $data['school'] = '';
                $data['profession'] = '';
                $data['degree'] = '';
                $data['entry_year'] = '';
            } else {
                $data['th_name'] = $contact->u_teacher_name;
                $data['th_phone'] = $contact->u_teacher_telephone;
                $data['fr_name_1'] = $contact->u_frend_name1;
                $data['fr_phone_1'] = $contact->u_frend_telephone1;
                $data['fr_name_2'] = $contact->u_frend_name2;
                $data['fr_phone_2'] = $contact->u_frend_telephone2;
                $data['stu_num'] = $contact->u_student_number;
                $imgs = Img::toArray($contact->u_student_img, true);
                $data['stu_img'] = $imgs;
                $data['school'] = empty($contact->school) ? null : $contact->school->showInList();
                $data['profession'] = $contact->u_prof;
                $data['degree'] = $contact->u_degree;
                $data['entry_year'] = $contact->u_entry_year;
            }
            $re = ['result' => 2000, 'data' => $data, 'info' => '获取用户详细成功'];
        } catch (Exception $e) {
            $code = 3002;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => '获取用户详细失败:'.$e->getMessage()];
        }
        return Response::json($re);
    }

    // abandon
    public function postContact()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', '');

        // shcool id
        $school = Input::get('school');
        // shcool entry year
        $entryYear = Input::get('entry_year');
        // profession area
        $profession = Input::get('profession');
        // graduate degree
        $degree = Input::get('degree');

        // studen card number
        $studentNum = Input::get('stu_num');
        // teacher name
        $thName = Input::get('th_name');
        // teacher phone
        $thPhone = Input::get('th_phone');
        // friend name 1
        $frName1 = Input::get('fr_name_1');
        // friend phone 1
        $frPhone1 = Input::get('fr_phone_1');
        // friend name 2
        $frName2 = Input::get('fr_name_2');
        // friend phone 2
        $frPhone2 = Input::get('fr_phone_2');

        $imgToken = Input::get('img_token', '');

        try {
            $user = User::chkUserByToken($token, $u_id);

            $user_contact_people = TmpUserProfileBase::find($u_id);
            if (!isset($user_contact_people->u_id)) {
                $user_contact_people = new TmpUserProfileBase();
            }
            if ($user_contact_people->u_status == 1) {
                throw new Exception("您的审核已经通过", 3002);
            }
            $user_contact_people->u_id = $u_id;
            $user_contact_people->u_teacher_name = $thName;
            $user_contact_people->u_teacher_telephone = $thPhone;
            $user_contact_people->u_frend_name1 = $frName1;
            $user_contact_people->u_frend_telephone1 = $frPhone1;
            $user_contact_people->u_frend_name2 = $frName2;
            $user_contact_people->u_frend_telephone2 = $frPhone2;
            $user_contact_people->u_student_number = $studentNum;
            $user_contact_people->u_school_id = $school;
            $user_contact_people->u_prof = $profession;
            $user_contact_people->u_degree = $degree;
            $user_contact_people->u_entry_year = $entryYear;
            $user_contact_people->register();

            if ($imgToken) {
                $imgObj = new Img('user', $imgToken);
                $imgs = $imgObj->getSavedImg($u_id, '', true);
                $student_img = [];
                foreach ($imgs as $k => $img) {
                    if ($k == 'student_img_front' || $k == 'student_img_back') {
                        $student_img[] = $img;
                    }
                }
                $user_contact_people->u_student_img = implode(',', $student_img);
                $user_contact_people->save();
            }

            $re = Tools::reTrue('提交学校信息成功');
        } catch (Exception $e) {
            TmpUserProfileBase::clearByUser($u_id);
            $re = Tools::reFalse($e->getCode(), '提交学校信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getProduct($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $product = Product::find($id);
            if ($product->p_status == 2) {
                throw new Exception("该商品已下架", 7002);
            }
            $product->load('quantity', 'promo');
            $data = $product->showDetail();
            $re = ['result' => 2000, 'data' => $data, 'info' => '获取商品成功'];
        } catch (Exception $e) {
            $code = 7001;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => '获取商品失败:'.$e->getMessage()];
        }
        return Response::json($re);
    }

    public function getProducts()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $b_id = Input::get('b_id');
        $per_page = Input::get('per_page', 30);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $products = Product::with(['quantity', 'promo'])->where('u_id', '=', $u_id)->where('b_id', '=', $b_id)->where('p_status', '=', 1)->orderBy('sort', 'DESC')->orderBy('created_at', 'DESC')->paginate($per_page);
            $pagination = ['total_record' => $products->getTotal(), 'total_page' => $products->getLastPage(), 'per_page' => $products->getPerPage(), 'current_page' => $products->getCurrentPage()];
            $data = [];
            foreach ($products as $key => $product) {
                $data[] = $product->showInList();
            }
            $re = ['result' => 2000, 'data' => $data, 'info' => '获取商品成功', 'pagination' => $pagination];
        } catch (Exception $e) {
            $code = 7001;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => '获取商品失败:'.$e->getMessage()];
        }
        return Response::json($re);
    }

    public function postProduct()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $b_id = Input::get('b_id', '');
        
        $prodName = Input::get('prod_name', '');
        $prodDesc = Input::get('content', '');
        $prodBrief = Input::get('prod_brief', '');
        $prodCost = Input::get('prod_cost', 0);
        $prodPriceOri = Input::get('prod_price', 0);
        $prodDiscount = Input::get('prod_discount', 100);
        $prodStock = Input::get('prod_stock', 0);
        $publish = Input::get('publish', 1);
        $product_cate = Input::get('cate');
        if (!$product_cate) {
            $product_cate = 7;
        }
        $prodDesc = urldecode($prodDesc);
        $promoDesc = Input::get('promo', '');
        $promoRange = Input::get('promo_range', 1);

        $imgToken = Input::get('img_token', '');

        try {
            $user = User::chkUserByToken($token, $u_id);

            if ($prodDiscount > 0) {
                $prodPrice = $prodPriceOri * $prodDiscount / 100;
            } else {
                $prodPrice = $prodPriceOri;
            }

            $product = new Product();
            $product->b_id = $b_id;
            $product->p_title = $prodName;
            $product->u_id = $u_id;
            $product->p_cost = $prodCost;
            $product->p_price_origin = $prodPriceOri;
            $product->p_price = $prodPrice;
            $product->p_discount = $prodDiscount;
            $product->p_desc = $prodDesc;
            $product->p_brief = $prodBrief;
            $product->p_status = $publish == 1 ? 1 : 2;
            $product->p_cate = $product_cate;
            $product->p_type = 1;
            $p_id = $product->addProduct();
            $quantity = new ProductQuantity();
            $quantity->p_id = $p_id;
            $quantity->b_id = $b_id;
            $quantity->u_id = $u_id;
            $quantity->q_total = $prodStock;

            $quantity->addQuantity();

            if ($promoDesc) {
                $user->load('school');
                $promo = new PromotionInfo();
                $promo->p_id = $p_id;
                $promo->p_content = $prodName;
                $promo->c_id = $user->school->t_city;
                $promo->s_id = $user->school->t_id;
                $promo->b_id = $b_id;
                $promo->p_status = 1;
                $promo->p_range = $promoRange;
                $promo->addPromo();
            }

            if ($imgToken) {
                $imgObj = new Img('product', $imgToken);
                $imgs = $imgObj->getSavedImg($p_id, '', true);
                $product->p_imgs = implode(',', $imgs);
                $product->save();
            }

            $re = Tools::reTrue('添加产品成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '添加产品失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function putProduct($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        $prodName = Input::get('prod_name', '');
        $prodBrief = Input::get('prod_brief', '');
        $prodDesc = Input::get('prod_desc', '');
        $prodCost = Input::get('prod_cost', 0);
        $prodPriceOri = Input::get('prod_price', 0);
        $prodDiscount = Input::get('prod_discount', 0);
        $prodStock = Input::get('prod_stock', 0);
        $publish = Input::get('publish', 1);

        $promoDesc = Input::get('promo', '');
        $promoRange = Input::get('promo_range', 0);
        $product_cate = Input::get('cate', 7);

        $imgToken = Input::get('img_token', '');

        try {
            $user = User::chkUserByToken($token, $u_id);

            $product = Product::find($id);

            if (!isset($product->p_id) || $product->u_id != $u_id) {
                throw new Exception("没有找到请求的产品", 1);
            }

            if ($prodDiscount > 0) {
                $prodPrice = $prodPriceOri * $prodDiscount / 100;
            } else {
                $prodPrice = $prodPriceOri;
            }

            $product->p_title = $prodName;
            $product->p_cost = $prodCost;
            $product->p_price_origin = $prodPriceOri;
            $product->p_price = $prodPrice;
            $product->p_discount = $prodDiscount;
            $product->p_desc = $prodDesc;
            $product->sort = 1;
            $product->p_cate = $product_cate;
            $product->p_brief = $prodBrief;
            $product->p_status = $publish == 1 ? 1 : 2;
            $product->saveProduct($prodStock);

            if ($promoDesc) {
                $user->load('school');

                $promo = PromotionInfo::find($id);
                if (!isset($promo->p_id)) {
                    $promo = new PromotionInfo();
                    $promo->p_id = $id;
                    $promo->p_content = $promoDesc;
                    $promo->c_id = $user->school->t_city;
                    $promo->s_id = $user->school->t_id;
                    $promo->pv_id = $user->school->t_province;
                    $promo->b_id = $product->b_id;
                    $promo->p_range = $promoRange;
                    $promo->addPromo();
                }
                $promo->p_status = 1;
                $promo->save();
            }

            if ($imgToken) {
                $imgObj = new Img('product', $imgToken);
                $imgs = $imgObj->getSavedImg($id, $product->p_imgs, true);
                $product->p_imgs = implode(',', $imgs);
                $product->save();
            }

            $re = Tools::reTrue('更新产品成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '更新产品失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function updateProductSort()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $sort = Input::get('sort', '');

        try {
            $user = User::chkUserByToken($token, $u_id);

            $sortArray = json_decode($sort, true);
            if (!is_array($sortArray)) {
                throw new Exception("请传入正确的排序数据", 1);
            }
            $re = Product::updateSort($sortArray);
            $re = Tools::reTrue('更新排序成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '更新排序失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function updateProductDiscount()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $discount = Input::get('discount', '');

        try {
            $user = User::chkUserByToken($token, $u_id);

            $discountArray = json_decode($discount, true);
            if (!is_array($discountArray)) {
                throw new Exception("请传入正确的排序数据", 1);
            }
            $re = Product::updateDiscount($discountArray);
            $re = Tools::reTrue('更新折扣成功');
        } catch (Exception $e) {
            $re = Tools::reTrue('更新折扣失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function productOn($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $on = Input::get('on', 1);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $product = Product::find($id);
            if (!isset($product->p_id)) {
                throw new Exception("您请求的商品不存在", 1);
            }
            $product->p_status = $on == 1 ? 1 : 2;
            $product->save();
            $re = Tools::reTrue('产品操作成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '产品操作失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function countOrders()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $count_nonshipping = Order::where('u_id', '=', $u_id)->where('o_shipping_status', '=', 1)->count();
            $count_shipped = Order::where('u_id', '=', $u_id)->where('o_shipping_status', '=', 5)->count();
            $count_nonpay = Order::where('u_id', '=', $u_id)->where('o_status', '=', 1)->count();
            $count_paied = Order::where('u_id', '=', $u_id)->where('o_status', '=', 2)->count();
            $count_finished = Order::where('u_id', '=', $u_id)->where('o_shipping_status', '=', 10)->count();
            $count_nonfinished = $count_nonshipping + $count_shipped;
            $data = ['nonshipping' => $count_nonshipping, 'shipped' => $count_shipped, 'nonpay' => $count_nonpay, 'paied' => $count_paied, 'nonfinished' => $count_nonfinished, 'finished' => $count_finished];
            $re = Tools::reTrue('获取订单统计成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取订单统计失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listOrders()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        $status = Input::get('status') ? Input::get('status') : '1,2';
        $key_word = Input::get('key', '');
        $finish = Input::get('finish', 0);
        $from = Input::get('from', '');
        $to = Input::get('to', '');

        $per_page = Input::get('per_page', 30);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $query = Order::select('orders.*')->with(['carts'])->where('orders.u_id', '=', $u_id)->leftJoin('carts', function ($j) {
                $j->on('orders.o_id', '=', 'carts.o_id');
            });
            if ($key_word) {
                $query = $query->where(function ($q) use ($key_word) {
                    $q->where('carts.p_name', 'LIKE', '%'.$key_word.'%')->orWhere('orders.o_number', 'LIKE', '%'.$key_word.'%');
                });
            }
            if ($from) {
                $query = $query->where('orders.created_at', '>', $from);
            }
            if ($to) {
                $query = $query->where('orders.created_at', '<', $to);
            }

            $status = explode(',', $status);

            $query = $query->where(function ($q) use ($status) {
                if (in_array(Order::$STATUS_UNFINISHED, $status)) {
                    $q = $q->orWhere(function ($qq) {
                        $qq->where('orders.o_shipping_status', '<>', 10)->orWhere('orders.o_status', '<>', 2);
                    });
                }
                if (in_array(Order::$STATUS_FINISHED, $status)) {
                    $q = $q->orWhere(function ($qq) {
                        $qq->orWhere('orders.o_shipping_status', '=', 10)->where('orders.o_status', '=', 2);
                    });
                }
                if (in_array(Order::$STATUS_PACKED, $status)) {
                    $q = $q->orWhere('orders.o_shipping_status', '=', 1);
                }
                if (in_array(Order::$STATUS_SHIPPED, $status)) {
                    $q = $q->orWhere('orders.o_shipping_status', '=', 5);
                }
                if (in_array(Order::$STATUS_ORDERED, $status)) {
                    $q = $q->orWhere('orders.o_status', '=', 1);
                }
                if (in_array(Order::$STATUS_PAIED, $status)) {
                    $q = $q->orWhere(function ($qq) {
                        $qq->where('orders.o_status', '=', 2)->where('orders.o_shipping_status', '<>', 10);
                    });
                }
            });

            // filter out invalide orders
            $query = $query->where('orders.o_status', '<>', 0)->where('orders.o_status', '<>', 3);
            $list = $query->groupBy('carts.o_id')->orderBy('orders.created_at', 'DESC')->paginate($per_page);
            $data = [];
            if (array_intersect($status, [Order::$STATUS_UNFINISHED, Order::$STATUS_FINISHED])) {
                $mask = 'all';
            } elseif (array_intersect($status, [Order::$STATUS_PACKED, Order::$STATUS_SHIPPED])) {
                $mask = 'shipping';
            } elseif (array_intersect($status, [Order::$STATUS_ORDERED, Order::$STATUS_PAIED])) {
                $mask = 'order';
            }
            foreach ($list as $key => $order) {
                $tmp = $order->showDetail(true);
                if ($status) {
                    $tmp['status'] = $order->mapOrderStatus($mask);
                }
                $data[] = $tmp;
            }
            $re = Tools::reTrue('获取订单成功', $data, $list);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取订单失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getOrder($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $order = Order::find($id);
            if (empty($order)) {
                throw new Exception("没有找到该订单", 9002);
            }
            if ($order->u_id != $u_id) {
                throw new Exception("没有权限操作该订单", 9006);
            }
            $order->load(['carts']);
            $data = $order->showDetail(true);
            $re = Tools::reTrue('获取订单成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取订单失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listSellOrders()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        $status = Input::get('status') ? Input::get('status') : '1,2';
        $key_word = Input::get('key', '');
        $finish = Input::get('finish', 0);
        $from = Input::get('from', '');
        $to = Input::get('to', '');

        $per_page = Input::get('per_page', 30);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $booths = Booth::where('u_id', '=', $u_id)->lists('b_id');
            if (empty($booths)) {
                throw new Exception("您还没有任何店铺", 7001);
            }
            $query = Order::select('orders.*')->with(['carts'])->whereIn('carts.b_id', $booths)->leftJoin('carts', function ($j) {
                $j->on('orders.o_id', '=', 'carts.o_id');
            });
            if ($key_word) {
                $query = $query->where(function ($q) use ($key_word) {
                    $q->where('carts.p_name', 'LIKE', '%'.$key_word.'%')->orWhere('orders.o_number', 'LIKE', '%'.$key_word.'%');
                });
            }
            if ($from) {
                $query = $query->where('orders.created_at', '>', $from);
            }
            if ($to) {
                $query = $query->where('orders.created_at', '<', $to);
            }

            $status = explode(',', $status);

            $query = $query->where(function ($q) use ($status) {
                if (in_array(Order::$STATUS_UNFINISHED, $status)) {
                    $q = $q->orWhere(function ($qq) {
                        $qq->where('orders.o_shipping_status', '<>', 10)->orWhere('orders.o_status', '<>', 2);
                    });
                }
                if (in_array(Order::$STATUS_FINISHED, $status)) {
                    $q = $q->orWhere(function ($qq) {
                        $qq->orWhere('orders.o_shipping_status', '=', 10)->where('orders.o_status', '=', 2);
                    });
                }
                if (in_array(Order::$STATUS_PACKED, $status)) {
                    $q = $q->orWhere('orders.o_shipping_status', '=', 1);
                }
                if (in_array(Order::$STATUS_SHIPPED, $status)) {
                    $q = $q->orWhere('orders.o_shipping_status', '=', 5);
                }
                if (in_array(Order::$STATUS_ORDERED, $status)) {
                    $q = $q->orWhere('orders.o_status', '=', 1);
                }
                if (in_array(Order::$STATUS_PAIED, $status)) {
                    $q = $q->orWhere(function ($qq) {
                        $qq->where('orders.o_status', '=', 2)->where('orders.o_shipping_status', '<>', 10);
                    });
                }
            });

            // filter out invalide orders
            $query = $query->where('orders.o_status', '<>', 0)->where('orders.o_status', '<>', 3);
            $list = $query->groupBy('carts.o_id')->orderBy('orders.created_at', 'DESC')->paginate($per_page);
            $data = [];
            if (array_intersect($status, [Order::$STATUS_UNFINISHED, Order::$STATUS_FINISHED])) {
                $mask = 'all';
            } elseif (array_intersect($status, [Order::$STATUS_PACKED, Order::$STATUS_SHIPPED])) {
                $mask = 'shipping';
            } elseif (array_intersect($status, [Order::$STATUS_ORDERED, Order::$STATUS_PAIED])) {
                $mask = 'order';
            }
            foreach ($list as $key => $order) {
                $tmp = $order->showDetail(true);
                if ($status) {
                    $tmp['status'] = $order->mapOrderStatus($mask);
                }
                $data[] = $tmp;
            }
            $re = Tools::reTrue('获取订单成功', $data, $list);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取订单失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function cancelOrder($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $remark = Input::get('remark', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $order = Order::find($id);
            if (empty($order)) {
                throw new Exception("没有找到该订单", 9002);
            }
            if ($order->u_id != $user->u_id) {
                throw new Exception("没有权限操作该订单", 9001);
            }
            if ($order->o_status != 1) {
                throw new Exception("不能在该状态下取消订单", 9001);
            }
            $order->cancelOrder($remark);
            $re = Tools::reTrue('取消订单成功.');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '取消订单失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getSellOrder($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $order = Order::find($id);
            if (empty($order)) {
                throw new Exception("没有找到该订单", 9002);
            }
            $order->load(['carts']);
            $data = $order->showDetail(true);
            $re = Tools::reTrue('获取订单成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取订单失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function cancelSellOrder()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $remark = Input::get('remark', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $order = Order::find($id);
            if (empty($order)) {
                throw new Exception("没有找到该订单", 9002);
            }
            if ($order->u_id != $user->u_id) {
                throw new Exception("没有权限操作该订单", 9001);
            }
            if ($order->o_status != 1) {
                throw new Exception("不能在该状态下取消订单", 9001);
            }
            $order->cancelOrder($remark);
            $re = Tools::reTrue('取消订单成功.');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '取消订单失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function countSellOrders()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $booths = Booth::where('u_id', '=', $u_id)->lists('b_id');
            if (empty($booths)) {
                throw new Exception("您还没有任何店铺", 7001);
            }
            $count_nonshipping = Order::where('orders.o_shipping_status', '=', 1)->whereIn('carts.b_id', $booths)->leftJoin('carts', function ($q) {
                $q->on('carts.o_id', '=', 'orders.o_id');
            })->groupBy('carts.o_id')->count();
            $count_shipped = Order::where('orders.o_shipping_status', '=', 5)->whereIn('carts.b_id', $booths)->leftJoin('carts', function ($q) {
                $q->on('carts.o_id', '=', 'orders.o_id');
            })->groupBy('carts.o_id')->count();
            $count_nonpay = Order::where('orders.o_status', '=', 1)->whereIn('carts.b_id', $booths)->leftJoin('carts', function ($q) {
                $q->on('carts.o_id', '=', 'orders.o_id');
            })->groupBy('carts.o_id')->count();
            $count_paied = Order::where('orders.o_status', '=', 2)->whereIn('carts.b_id', $booths)->leftJoin('carts', function ($q) {
                $q->on('carts.o_id', '=', 'orders.o_id');
            })->groupBy('carts.o_id')->count();
            $count_finished = Order::where('orders.o_shipping_status', '=', 10)->whereIn('carts.b_id', $booths)->leftJoin('carts', function ($q) {
                $q->on('carts.o_id', '=', 'orders.o_id');
            })->groupBy('carts.o_id')->count();
            $count_nonfinished = $count_nonshipping + $count_shipped;
            $data = ['nonshipping' => $count_nonshipping, 'shipped' => $count_shipped, 'nonpay' => $count_nonpay, 'paied' => $count_paied, 'nonfinished' => $count_nonfinished, 'finished' => $count_finished];
            $re = Tools::reTrue('获取订单统计成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取订单统计失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function deliverOrder()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $orders = Input::get('orders', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $orders = explode(',', $orders);
            if (empty($orders)) {
                throw new Exception("无效的订单数据", 7001);
            }
            Order::updateShippingStatus($orders, Order::$SHIPPING_STATUS_DELIVERING);
            $re = Tools::reTrue('发货成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '发货失败:'.$e->getMessage());
        }
        return $re;
    }

    public function confirmOrder()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $orders = Input::get('orders', '');

        DB::beginTransaction();
        try {
            $user = User::chkUserByToken($token, $u_id);
            $orders = explode(',', $orders);
            if (empty($orders)) {
                throw new Exception("无效的订单数据", 7001);
            }
            $orders = Order::whereIn('o_id', $orders)->get();
            foreach ($orders as $key => $order) {
                $order->confirm();
            }
            $re = Tools::reTrue('确认成功');
            DB::commit();
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '确认失败:'.$e->getMessage());
            DB::rollback();
        }
        return $re;
    }

    public function listPraisePromo()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $list = DB::table('promotion_praises')->where('u_id', '=', $u_id)->lists('prom_id');
            $re = Tools::reTrue('获取我赞的产品成功', $list);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取我赞的产品失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listFollowingBooth()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $type = Input::get('type', 0);

        $per_page = Input::get('per_page');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $query = BoothFollow::select('booth_follows.*')->with(['booth'])->where('booth_follows.u_id', '=', $u_id)->leftJoin('booths', function ($q) use ($type) {
                $q->on('booths.b_id', '=', 'booth_follows.b_id');
            });
            if ($type) {
                $query->where('booths.b_type', '=', $type);
            }
            $list = $query->paginate($per_page);
            $data = [];
            foreach ($list as $key => $follow) {
                $data[] = $follow->showInList();
            }
            $re = Tools::reTrue('获取我收藏的店铺成功', $data, $list);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取我收藏的店铺失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function showWallet()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $bank = UserProfileBankcard::find($u_id);
            $alipay = UsersAlipayPayment::find($u_id);
            $wechat = UsersWechatPayment::find($u_id);
            if (empty($bank)) {
                $data['bank'] = null;
            } else {
                $bank->load('bank');
                $data['bank'] = $bank->showInList();
            }
            if (empty($alipay)) {
                $data['alipay'] = null;
            } else {
                $data['alipay'] = $alipay->showInList();
            }
            if (empty($wechat)) {
                $data['wechat'] = null;
            } else {
                $data['wechat'] = $wechat->showInList();
            }
            $balance = UsersWalletBalances::find($u_id);
            if (empty($balance)) {
                $amount = '0.00';
                $freez = '0.00';
            } else {
                $amount = $balance->w_balance;
                $freez = $balance->w_freez;
            }
            $data['balance'] = $amount;
            $data['freez'] = $freez;
            $re = Tools::reTrue('获取钱包信息成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取钱包信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postPaymentWechat()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        $account = Input::get('account', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $wechat = UsersWechatPayment::where('t_account', '=', $account)->first();
            if (!empty($wechat) && $wechat->u_id != $u_id) {
                throw new Exception("该微信号码已被绑定", 9007);
            }
            $wechat = UsersWechatPayment::find($u_id);
            if (empty($wechat)) {
                $wechat = new UsersWechatPayment();
                $wechat->u_id = $u_id;
            }
            $wechat->t_account = $account;
            $wechat->save();
            $re = Tools::reTrue('绑定成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '绑定失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postPaymentAlipay()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        $account = Input::get('account', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $alipay = UsersAlipayPayment::where('t_account', '=', $account)->first();
            if (!empty($alipay) && $alipay->u_id != $u_id) {
                throw new Exception("该微信号码已被绑定", 9007);
            }
            $alipay = UsersAlipayPayment::find($u_id);
            if (empty($alipay)) {
                $alipay = new UsersAlipayPayment();
                $alipay->u_id = $u_id;
            }
            $alipay->t_account = $account;
            $alipay->save();
            $re = Tools::reTrue('绑定成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '绑定失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postPaymentBank()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        $card_num = Input::get('card_num', '');
        $holder = Input::get('holder', '');
        $bank = Input::get('bank', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $card = UserProfileBankcard::where('b_card_number', '=', $card_num)->first();
            if (!empty($card) && $card->u_id != $u_id) {
                throw new Exception("该卡号不可用", 9007);
            }
            $card = UserProfileBankcard::find($u_id);
            if (empty($card)) {
                $card = new UserProfileBankcard();
                $card->u_id = $u_id;
            }
            $card->b_card_number = $card_num;
            $card->b_holder_name = $holder;
            $card->b_id = $bank;
            $card->save();
            $re = Tools::reTrue('绑定成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '绑定失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function financialReport()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        $day = Input::get('day', 0);
        $month = Input::get('month', 0);
        $year = Input::get('year', 0);
        $b_id = Input::get('b_id', 0);

        try {
            // $user = User::chkUserByToken($token, $u_id);
            $booth = Booth::find($b_id);
            if (empty($booth)) {
                throw new Exception("请求的店铺无效", 7001);
            }
            $query = DB::table('products')->leftJoin('carts', function ($q) {
                $q->on('products.p_id', '=', 'carts.p_id');
            })->select('products.p_id as id', 'products.p_title as title', 'products.p_cost as cost', 'carts.c_quantity as quantity', 'carts.c_amount as amount', 'carts.c_id');
            
            $query = $query->where('carts.b_id', '=', $b_id);

            $date_obj = new DateTime();
            $today = $date_obj->format('Y-m-d');

            if ($day) {
                $date_obj->modify('+1 day');
                $tomorrow = $date_obj->format('Y-m-d');
                $query = $query->where('carts.checkout_at', '>', $today)->where('carts.checkout_at', '<', $tomorrow);
            }
            if ($month) {
                $date_obj->modify('-1 month');
                $one_month_ago = $date_obj->format('Y-m-d');
                $query = $query->where('carts.checkout_at', '>', $one_month_ago)->where('carts.checkout_at', '<', $today);
            }
            if ($year) {
                $date_obj->modify('-1 year');
                $one_year_ago = $date_obj->format('Y-m-d');
                $query = $query->where('carts.checkout_at', '>', $one_year_ago)->where('carts.checkout_at', '<', $today);
            }
            $list = $query->get();
            $report = [];
            $total_quantity = 0;
            $total_cost = 0;
            $total_amount = 0;
            $total_profit = 0;
            $cart_ids = [];
            foreach ($list as $key => $product) {
                if (empty($report[$product->id])) {
                    $report[$product->id]['title'] ='';
                    $report[$product->id]['quantity'] ='';
                    $report[$product->id]['cost'] ='';
                    $report[$product->id]['amount'] ='';
                }
                $report[$product->id]['title'] = $product->title;
                $report[$product->id]['quantity'] += $product->quantity;
                $report[$product->id]['cost'] += ($product->cost * $product->quantity);
                $report[$product->id]['amount'] += $product->amount;
                $cart_ids[] = $product->c_id;
            }
            foreach ($report as $key => $product) {
                $report[$key]['id'] = $key;
                $report[$key]['profit'] = $product['amount'] - $product['cost'];
                $total_quantity += $product['quantity'];
                $total_cost += $product['cost'];
                $total_amount += $product['amount'];
                $total_profit += $report[$key]['profit'];
            }
            $report = array_values($report);
            $data = ['report' => $report, 'carts' => implode(',', $cart_ids), 'total_quantity' => $total_quantity, 'total_cost' => $total_cost, 'total_amount' => $total_amount, 'total_profit' => $total_profit];
            $re = Tools::reTrue('获取报表成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), $e->getMessage());
        }
        return Response::json($re);
    }

    public function confirmFinancialReport()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        $carts = Input::get('carts', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            Cart::whereIn('c_id', explode(',', $carts))->update(['c_comfirmed' => 1]);
            $re = Tools::reTrue('确认交易记录成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '确认交易记录失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postWalletDraw()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        $amount = Input::get('amount');
        $payment = Input::get('payment', '');
        $account = Input::get('account', '');

        $b_id = Input::get('b_id', 0);
        $holder = Input::get('holder', '');

        DB::beginTransaction();
        try {
            if ($payment == 1 && (!$b_id || !$holder)) {
                throw new Exception("提现到银行卡需要填写持卡人姓名并选择银行", 9008);
            }
            $user = User::chkUserByToken($token, $u_id);
            $draw = new UsersDraw();
            $draw->u_id = $u_id;
            $draw->d_payment = $payment;
            $draw->d_account = $account;
            $draw->d_amount = $amount;
            $draw->b_id = $b_id;
            $draw->b_holder_name = $holder;
            $d_id = $draw->addDraw();
            $wallet = UsersWalletBalances::find($u_id);
            $wallet->freez($amount);
            $data['id'] = $d_id;
            $re = Tools::reTrue('提现申请成功', $data);
            DB::commit();
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '提现申请失败:'.$e->getMessage());
            DB::rollback();
        }
        return Response::json($re);
    }

    public function listWalletDraw()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        $per_page = Input::get('per_page', 30);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $list = UsersDraw::with(['bank'])->where('u_id', '=', $u_id)->paginate($per_page);
            $data = [];
            foreach ($list as $key => $draw) {
                $data[] = $draw->showInList();
            }
            $re = Tools::reTrue('获取提现记录成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取提现记录失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getWalletDraw($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $draw = UsersDraw::find($id);
            if (empty($draw)) {
                throw new Exception("请求的记录不存在", 9008);
            }
            $draw->load('bank');
            $data = $draw->showInList();
            $re = Tools::reTrue('获取提现记录成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取提现记录失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function delHomeImg()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id');
        $obj = Input::get('obj', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $home_imgs = explode(',', $user->u_home_img);
            foreach ($home_imgs as $key => $img) {
                if ($obj == $img) {
                    unset($home_imgs[$key]);
                    break;
                }
            }
            $user->u_home_img = implode(',', $home_imgs);
            $user->save();

            $oss = new AliyunOss('user');
            $obj = Img::getFileName($obj);
            $obj = 'user/'.$u_id.'/'.$obj;
            $oss->remove($obj);

            $re = Tools::reTrue('删除图片成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '删除图片失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function delProductImg($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id');
        $obj = Input::get('obj', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $product = Product::find($id);
            if (empty($product)) {
                throw new Exception("没有找到请求的图片", 7001);
            }
            $imgs = explode(',', $product->p_imgs);
            foreach ($imgs as $key => $img) {
                if ($obj == $img) {
                    unset($imgs[$key]);
                    break;
                }
            }

            $product->p_imgs = implode(',', $imgs);
            $product->save();

            $oss = new AliyunOss('product');
            $obj = Img::getFileName('obj');
            $obj = 'product/'.$id.'/'.$obj;
            $oss->remove($obj);

            $re = Tools::reTrue('删除图片成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '删除图片失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listFavoriteBooth()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id');
        $per_page = Input::get('per_page', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $list = Booth::select('booths.*', 'favorites.created_at')->with([
                'user',
                'school',
                'city',
                'praises' => function ($q) {
                    $q->where('praises.u_id', '=', $this->u_id);
                }
                ])
            ->join('favoriables', function ($q) {
                $q->on('booths.b_id', '=', 'favoriables.favoriable_id')->where('favoriables.favoriable_type', '=', 'Booth');
            })->join('favorites', function ($q) {
                $q->on('favorites.id', '=', 'favoriables.favorite_id')->where('favorites.u_id', '=', $this->u_id);
            })->orderBy('favorites.created_at', 'DESC')->paginate($per_page);
            $data = [];
            foreach ($list as $key => $booth) {
                $tmp = $booth->showInList();
                $tmp['is_praised'] = 0;
                if (count($booth->praises) > 0) {
                    $tmp['is_praised'] = 1;
                }
                $data[] = $tmp;
            }
            $re = Tools::reTrue('获取店铺成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取店铺失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listFavoriteUser()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id');
        $per_page = Input::get('per_page', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $list = User::select('users.*', 'favorites.created_at')->with([
                'school',
                'praises' => function ($q) {
                    $q->where('praises.u_id', '=', $this->u_id);
                }
                ])
            ->join('favoriables', function ($q) {
                $q->on('users.u_id', '=', 'favoriables.favoriable_id')->where('favoriables.favoriable_type', '=', 'User');
            })->join('favorites', function ($q) {
                $q->on('favorites.id', '=', 'favoriables.favorite_id')->where('favorites.u_id', '=', $this->u_id);
            })->orderBy('favorites.created_at', 'DESC')->paginate($per_page);
            $data = [];
            foreach ($list as $key => $user) {
                $tmp = $user->showInList();
                $tmp['is_praised'] = 0;
                if (count($user->praises) > 0) {
                    $tmp['is_praised'] = 1;
                }
                $data[] = $tmp;
            }
            $re = Tools::reTrue('获取用户列表成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取用户列表失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listFavoriteProduct()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id');
        $per_page = Input::get('per_page', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $list = Product::select('products.*', 'favorites.created_at')
            ->with([
                'user' => function ($q) {
                    $q->with(['school']);
                },
                'praises' => function ($q) {
                    $q->where('praises.u_id', '=', $this->u_id);
                }
            ])
            ->join('favoriables', function ($q) {
                $q->on('products.p_id', '=', 'favoriables.favoriable_id')->where('favoriables.favoriable_type', '=', 'Product');
            })->join('favorites', function ($q) {
                $q->on('favorites.id', '=', 'favoriables.favorite_id')->where('favorites.u_id', '=', $this->u_id);
            })->orderBy('favorites.created_at', 'DESC')->paginate($per_page);
            $data = [];
            foreach ($list as $key => $product) {
                $tmp = $product->showInList();
                $tmp['is_praised'] = 0;
                if (count($product->praises) > 0) {
                    $tmp['is_praised'] = 1;
                }
                $data[] = $tmp;
            }
            $re = Tools::reTrue('获取收藏列表成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取收藏列表失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listFavoriteCrowd()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id');

        $per_page = Input::get('per_page', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $list = CrowdFunding::select('crowd_fundings.*', 'favorites.created_at')->with([
                'user',
                'city',
                'school',
                'eventItem',
                'praises' => function ($q) {
                    $q->where('praises.u_id', '=', $this->u_id);
                }
                ])
            ->join('favoriables', function ($q) {
                $q->on('crowd_fundings.cf_id', '=', 'favoriables.favoriable_id')->where('favoriables.favoriable_type', '=', 'CrowdFunding');
            })->join('favorites', function ($q) {
                $q->on('favorites.id', '=', 'favoriables.favorite_id')->where('favorites.u_id', '=', $this->u_id);
            })->orderBy('favorites.created_at', 'DESC')->paginate($per_page);
            $data = [];
            foreach ($list as $key => $funding) {
                $tmp = $funding->showInList();
                $tmp['is_praised'] = 0;
                if (count($funding->praises) > 0) {
                    $tmp['is_praised'] = 1;
                }
                $data[] = $tmp;
            }
            $re = Tools::reTrue('获取用户列表成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取用户列表失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function putHeadImg()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        $img_token = Input::get('img_token');

        try {
            $user = User::chkUserByToken($token, $u_id);
            if ($img_token) {
                $imgObj = new Img('user', $img_token);
                $imgs = $imgObj->getSavedImg($u_id, implode(',', [$user->u_head_img]), true);
                $head_img = Img::filterKey('head_img', $imgs);
                $user->u_head_img = implode(',', $head_img);
                $user->save();
            } else {
                throw new Exception("请上传头像", 2001);
            }
            $data['head_img'] = $head_img;
            $re = Tools::reTrue('修改头像成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '修改头像失败:'.$e->getMessage());
        }
        return Response::json($re);
    }
}
