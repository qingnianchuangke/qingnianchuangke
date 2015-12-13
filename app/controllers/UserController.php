<?php

class UserController extends \BaseController
{

    /**
     * sign in
     *
     * @return Response
     */
    public function index()
    {
        $mobile = Input::get('mobile');
        $pass = Input::get('pass');
        try {
            // AES crypt
            $pass = Tools::qnckDecrytp($pass);
            if (!$pass) {
                throw new Exception("密码错误", 2001);
            }

            $user = new User();
            $user->u_mobile = $mobile;
            $user->u_password = $pass;
            $data = $user->login();
            $re = ['data' => $data, 'result' => 2000, 'info' => '登陆成功'];
        } catch (Exception $e) {
            $re = ['data' => [], 'result' => 2001, 'info' => $e->getMessage()];
        }
        return Response::json($re);
    }

    public function loginFromWechat()
    {
        $mobile = Input::get('mobile');
        $pass = Input::get('pass');
        try {
            $user = new User();
            $user->u_mobile = $mobile;
            $user->u_password = $pass;
            $data = $user->login();
            $re = ['data' => $data, 'result' => 2000, 'info' => '登陆成功'];
        } catch (Exception $e) {
            $re = ['data' => [], 'result' => 2001, 'info' => $e->getMessage()];
        }
        return Response::json($re);
    }

    public function importLogin()
    {
        $ext_id = Input::get('ext_id', '');
        $ext_token = Input::get('ext_token', '');
        $nickname = Input::get('nickname', '');
        $gender = Input::get('gender', 3);
        $head_img = Input::get('head_img', '');
        $import_type = Input::get('import_type', '');
        $import_type = strtolower($import_type);

        try {
            if (!$ext_id || !$import_type) {
                throw new Exception("需要正确的id, type参数", 3005);
            }
            $ext = [
                'u_ext_id' => $ext_id,
                'u_ext_token' => $ext_token,
                'u_head_img' => $head_img,
                'u_gender' => $gender,
                'u_nickname' => $nickname,
            ];
            switch ($import_type) {
                case 'qq':
                    $qq_user = new UserImportQq($ext);
                    $data = $qq_user->import();
                    break;
                case 'wechat':
                    $wechat_user = new UserImportWechat($ext);
                    $data = $wechat_user->import();
                    break;
                
                default:
                    throw new Exception("无效的登录类型", 3005);
                    break;
            }
            $re = Tools::reTrue('登录成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '登录失败:'.$e->getMessage());
        }
        return Response::json($re);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
    }


    /**
     * sign up
     *
     * @return Response
     */
    public function store()
    {
        $mobile = Input::get('mobile');
        $pass = Input::get('pass');
        $school_id = Input::get('school');
        $vCode = Input::get('vcode');
        $invite_code = Input::get('invite_code');
        try {
            // AES crypt
            $pass = Tools::qnckDecrytp($pass);
            if (!$pass) {
                throw new Exception("密码错误", 2001);
            }

            $user = new User();
            $user->u_school_id = $school_id;
            $user->u_mobile = $mobile;
            $user->u_password = $pass;

            // verify vcode via phone
            $phone = new Phone($mobile);
            $phone->authVCode($vCode);
            $data = $user->register();
            // add user wallet
            $wallet = new UsersWalletBalances();
            $wallet->u_id = $user->u_id;
            $wallet->w_balance = 0.00;
            $wallet->w_freez = 0.00;
            $wallet->save();
            User::thanksForInvite($invite_code, $user->u_id);
            $re = ['data' => $data, 'result' => 2000, 'info' => '注册成功'];
        } catch (Exception $e) {
            $re = ['data' => [], 'info' => $e->getMessage(), 'result' => 2001];
        }
        return Response::json($re);
    }
    
    public function bindMobile()
    {
        $u_id = Input::get('u_id', '');
        $token = Input::get('token', '');
        $mobile = Input::get('mobile', '');
        $vcode = Input::get('vcode', '');

        try {
            $user = User::where('u_mobile', '=', $mobile)->first();
            if (!empty($user)) {
                throw new Exception("该手机号码已被使用", 1);
            }
            $user = User::chkUserByToken($token, $u_id);
            if ($user->u_mobile) {
                throw new Exception("手机号码已存在", 2001);
            }
            $phone = new Phone($mobile);
            $phone->authVCode($vcode);
            $user->u_mobile = $mobile;
            $user->save();
            $re = Tools::reTrue('绑定成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '绑定失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postUserFromWechat()
    {
        $mobile = Input::get('mobile');
        $pass = Input::get('pass');
        $school_id = Input::get('school');
        $vCode = Input::get('vcode');
        DB::beginTransaction();
        try {
            $user = new User();
            $user->u_school_id = $school_id;
            $user->u_mobile = $mobile;
            $user->u_password = $pass;

            // verify vcode via phone
            $phone = new Phone($mobile);
            $phone->authVCode($vCode);
            $data = $user->register();
            // add user wallet
            $wallet = new UsersWalletBalances();
            $wallet->u_id = $user->u_id;
            $wallet->w_balance = 0.00;
            $wallet->w_freez = 0.00;
            $wallet->save();
            $re = ['data' => $data, 'result' => 2000, 'info' => '注册成功'];
            DB::commit();
        } catch (Exception $e) {
            $re = ['data' => [], 'info' => $e->getMessage(), 'result' => 2001];
            DB::rollback();
        }
        return Response::json($re);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);

            $show_user = User::find($id);
            if (empty($show_user)) {
                throw new Exception("请求的用户不存在", 3001);
            }
            $show_user->load([
                'school',
                'favorites' => function ($q) {
                    $q->where('favorites.u_id', '=', $this->u_id);
                },
                'praises' => function ($q) {
                    $q->where('praises.u_id', '=', $this->u_id);
                }
                ]);
            $data = $show_user->showDetail();

            $is_friend = UsersFriend::$RELATION_NONE;
            $userFriend = UsersFriend::findLinkById($u_id, $show_user->u_id);
            if ($userFriend === UsersFriend::$RELATION_NONE) {
            } else {
                if ($userFriend->t_status == 1) {
                    $is_friend = $userFriend->t_inviter == $u_id ? UsersFriend::$RELATION_INVITED : UsersFriend::$RELATION_PEDDING_CONFIRM;
                } else {
                    $is_friend = UsersFriend::$RELATION_CONFIRMED;
                }
            }
            $data['is_friend'] = $is_friend;
            $data['is_praised'] = 0;
            $data['is_favorited'] = 0;
            if (count($show_user->praises) > 0) {
                $data['is_praised'] = 1;
            }
            if (count($show_user->favorites) > 0) {
                $data['is_favorited'] = 1;
            }
            $re = ['result' => 2000, 'data' => $data, 'info' => '读取用户成功'];
        } catch (Exception $e) {
            $re = ['result' => 2001, 'data' => [], 'info' => $e->getMessage()];
        }
        return Response::json($re);
    }


    /**
     * add follow or unfollow.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $type = Input::get('type');
        $token = Input::get('token');
        $u_id = Input::get('u_id');

        $user = User::chkUserByToken($token, $u_id);
        try {
            $target = User::find($id);
            if ($type == 1) {
                User::follow($user, $target);
            } else {
                User::unfollow($user, $target);
            }
            $re = Tools::reTrue('操作成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '操作失败:'.$e->getMessage());
        }
        return Response::json($re);
    }


    /**
     * update
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id)
    {
        $user = new User();
        $user->u_id = $id;
        $user->u_token = Input::get('token', null);
        $user->u_password = Input::get('pass', null);
        $user->u_age = Input::get('age', null);
        $user->u_name = Input::get('name', null);
        $user->u_sex = Input::get('sex', null);
        $user->u_school_id = Input::get('school_id', null);
        $user->u_prof = Input::get('profession', null);
        $user->u_degree = Input::get('degree', null);
        $user->u_entry_year = Input::get('in_year', null);
        $imgToken = Input::get('img_token', '');
        try {
            $user->updateUser($imgToken);
            $re = Tools::reTrue('更新成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '更新失败:'.$e->getMessage());
        }
        return Response::json($re);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * get my followers
     * @author Kydz 2015-07-04
     * @param  int $id user id
     * @return json     followers
     */
    public function followers($id)
    {
        try {
            $user = User::with([
            'followers' => function ($q) {
                $q->where('u_status', '=', 1)->paginate(10);
            },
            ])->find($id);
            $data = $user->getFollowers();
            $re = ['result' => 2000, 'data' => $data, 'info' => '获取粉丝成功'];
        } catch (Exception $e) {
            $re = ['result' => 2001, 'data' => [], 'info' => '获取粉丝失败'];
        }
        return Response::json($re);
    }

    /**
     * get my followings
     * @author Kydz 2015-07-04
     * @param  int $id user id
     * @return json     followings
     */
    public function followings($id)
    {
        try {
            $user = User::with([
            'followings' => function ($q) {
                $q->where('u_status', '=', 1)->paginate(10);
            },
            ])->find($id);
            $data = $user->getFollowings();
            $re = ['result' => 2000, 'data' => $data, 'info' => '获取关注的人成功'];
        } catch (Exception $e) {
            $re = ['result' => 2001, 'data' => [], 'info' => '获取关注的人失败'];
        }
        return Response::json($re);
    }

    /**
     * follow or unfollow other users
     * @author Kydz
     * @param  int $id target id
     * @return json     n/a
     */
    public function follow($id)
    {
        $u_id = Input::get('u_id');
        $token = Input::get('token');
        $type = Input::get('type');
        $target = User::find($id);
        if (!isset($target->u_id)) {
            return Response::json(['result' => 2001, 'data' => [], 'info' => '您关注的用户不存在']);
        }
        if ($id == $u_id) {
            return Response::json(['result' => 2001, 'data' => [], 'info' => '您不能关注自己']);
        }

        try {
            $user = User::chkUserByToken($token, $u_id);
            if ($type == 1) {
                $msg = '关注成功';
                User::follow($user, $target);
            } elseif ($type == 2) {
                $msg = '取消关注成功';
                User::unfollow($user, $target);
            }
            $re = ['result' => 2000, 'data' => [], 'info' => $msg];
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
     * search user
     * @author Kydz
     * @return json user list
     */
    public function search()
    {
        $u_id = Input::get('u_id', 0);
        $token = Input::get('token', '');
        $keyWord = Input::get('key', '');
        $school = Input::get('school', 0);
        $range = Input::get('range', 0);
        $lat = Input::get('lat', 0);
        $lng = Input::get('lng', 0);
        $perPage = Input::get('per_page', 30);
        try {
            User::chkUserByToken($token, $u_id);
            $query = User::with(['school'])->where('u_id', '<>', $u_id);

            if ($keyWord) {
                $query = $query->where(function ($q) use ($keyWord) {
                    $q->where('u_name', 'LIKE', '%'.$keyWord.'%')->orWhere('u_mobile', '=', $keyWord)->orWhere('u_nickname', 'LIKE', '%'.$keyWord.'%');
                });
            }

            if ($school > 0) {
                $query = $query->where('u_school_id', '=', $school);
            }

            if ($range) {
                if (!$lat || !$lng) {
                    throw new Exception("请传入有效的经纬度", 1);
                }
                $distance = ['0' => 0.0, '1' => 0.5, '2' => 1.0, '3' => 1.5, '4' => 3.0, '5' => 5.0];
                
                if (!array_key_exists($range, $distance)) {
                    throw new Exception("请传入有效的距离档位", 1);
                }
                $user_ids = User::filterByDistance($lat, $lng, $distance[$range]);
                $query = $query->whereIn('u_id', $user_ids);
            }

            $data = $query->paginate($perPage);
            $list = [];
            foreach ($data as $key => $user) {
                $list[] = $user->showInList();
            }
            $re = Tools::reTrue('搜索用户成功', $list, $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '搜索用户失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postPraise($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $type = Input::get('type', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $chk = $user->praises()->where('praises.u_id', '=', $u_id)->first();
            if ($type == 1) {
                if (empty($chk)) {
                    $data = [
                        'u_id' => $u_id,
                        'created_at' => Tools::getNow(),
                        'u_name' => $user->u_name
                    ];
                    $praise = new Praise($data);
                    $user->praises()->save($praise);
                    $user->u_praise_count++;
                }
            } else {
                if (!empty($chk)) {
                    $user->praises()->detach($chk->id);
                    $chk->delete();
                    $user->u_praise_count = --$user->u_praise_count <= 0 ? 0 : $user->u_praise_count;
                }
            }
            $user->save();
            $re = Tools::reTrue('操作成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '操作失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postFavorite($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $type = Input::get('type', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $chk = $user->favorites()->where('favorites.u_id', '=', $u_id)->first();
            if ($type == 1) {
                if (empty($chk)) {
                    $data = [
                        'u_id' => $u_id,
                        'created_at' => Tools::getNow(),
                        'u_name' => $user->u_nickname
                    ];
                    $favorite = new Favorite($data);
                    $user->favorites()->save($favorite);
                }
            } else {
                if (!empty($chk)) {
                    $user->favorites()->detach($chk->id);
                    $chk->delete();
                }
            }
            $re = Tools::reTrue('收藏成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '收藏失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getUserType()
    {
        $data = User::getUserType();
        $re = Tools::reTrue('获取用户类型成功', $data);
        return Response::json($re);
    }
}
