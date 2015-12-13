<?php

class MeFriendController extends \BaseController {

    /**
     * Friends list
     *
     * @return Response
     */
    public function index()
    {
        $u_id = Input::get('u_id', 0);
        $token = Input::get('token', '');
        $ver = Input::get('ver', 0);
        $gender = Input::get('gender');

        try {
            $user = User::chkUserByToken($token, $u_id);

            // check changes
            $sum1 = DB::table('users_friends')->join('users', function ($j) use ($u_id) {
                $j->on('users_friends.u_id_1', '=', 'users.u_id')->where('users_friends.u_id_2', '=', $u_id);
            })->sum('users.u_change');

            $sum2 = DB::table('users_friends')->join('users', function ($j) use ($u_id) {
                $j->on('users_friends.u_id_2', '=', 'users.u_id')->where('users_friends.u_id_1', '=', $u_id);
            })->sum('users.u_change');

            $sum = $sum1 + $sum2;

            if ($ver == $sum) {
                return Response::json(['result' => 2000, 'data' => [], 'info' => '获取我的好友列表成功', 'ver' => $ver]);
            }
            
            $data = $this->getUserList($u_id, 2, $gender);

            $re = ['result' => 2000, 'data' => $data, 'info' => '获取我的好友列表成功', 'ver' => $sum];
        } catch (Exception $e) {
            $code = 3001;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => '获取我的好友列表失败:'.$e->getMessage()];
        }
        return Response::json($re);
    }

    public function indexInvite()
    {
        $u_id = Input::get('u_id', 0);
        $token = Input::get('token', '');
        $ver = Input::get('ver', 0);
        $gender = Input::get('gender', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $data = $this->getUserList($u_id, 0, $gender);

            $list = UserFriendInviteLog::where('u_id', '=', $u_id)->get();
            $logs = [];
            foreach ($list as $key => $log) {
                $logs[$log->friend_id] = $log->id;
            }
            foreach ($data as $key => $friend) {
                if (array_key_exists($friend['id'], $logs)) {
                    $data[$key]['log_id'] = $logs[$friend['id']];
                } else {
                    unset($data[$key]);
                }
            }
            $data = array_values($data);
            $re = ['result' => 2000, 'data' => $data, 'info' => '获取好友邀请列表成功'];
        } catch (Exception $e) {
            $code = 3001;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => '获取好友邀请列表失败:'.$e->getMessage()];
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
        //
    }


    /**
     * add new friends
     *
     * @return Response
     */
    public function invite()
    {
        $u_id = Input::get('u_id', 0);
        $token = Input::get('token', '');
        $friend = Input::get('friend', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $userFriend = new UsersFriend();
            $userFriend->u_id_1 = $u_id;
            $userFriend->u_id_2 = $friend;
            $userFriend->t_inviter = $u_id;
            $userFriend->invite($u_id);
            UserFriendInviteLog::addLog($u_id, $friend);
            $re = ['result' => 2000, 'data' => [], 'info' => '邀请好友成功'];
        } catch (Exception $e) {
            $code = 3001;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => '邀请好友失败:'.$e->getMessage()];
        }

        return Response::json($re);
    }

    public function check()
    {
        $u_id = Input::get('u_id', 0);
        $token = Input::get('token', '');
        $friend = Input::get('friend', 0);

        try {
            $data = ['status' => UsersFriend::$RELATION_NONE];
            $user = User::chkUserByToken($token, $u_id);
            $friend = User::find($friend);
            if (empty($friend->u_id)) {
                throw new Exception("你查找的用户不存在", 3001);
            }
            $friendInfo = $friend->showInList();
            $userFriend = UsersFriend::findLinkById($u_id, $friend->u_id);
            if ($userFriend === UsersFriend::$RELATION_NONE) {
            } else {
                if ($userFriend->t_status == 1) {
                    $data['status'] = $userFriend->t_inviter == $u_id ? UsersFriend::$RELATION_INVITED : UsersFriend::$RELATION_PEDDING_CONFIRM;
                } else {
                    $data['status'] = UsersFriend::$RELATION_CONFIRMED;
                }
            }
            $data = array_merge($data, $friendInfo);
            $re = Tools::reTrue('好友关系检测成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '好友关系检测失败:'.$e->getMessage());
        }
        return Response::json($re);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }


    /**
     * confirm friend
     *
     * @param  int  $id
     * @return Response
     */
    public function confirm()
    {
        $u_id = Input::get('u_id', 0);
        $token = Input::get('token', '');
        $friend = Input::get('friend', 0);
        
        try {
            $user = User::chkUserByToken($token, $u_id);
            $userFriend = UsersFriend::findLinkById($u_id, $friend);
            if ($userFriend === UsersFriend::$RELATION_NONE) {
                throw new Exception("请先邀请好友", 3001);
            } else {
                if ($userFriend->t_status == 2) {
                    throw new Exception("你们已经是好友了", 3001);
                }
                if ($userFriend->t_inviter == $u_id) {
                    throw new Exception("您不能自己确认好友邀请", 3001);
                }
                $userFriend->confirm();
            }
            $re = Tools::reTrue('确认好友成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '确认好友失败:'.$e->getMessage());
        }
        return Response::json($re);
    }


    /**
     * remove friend
     *
     * @param  int  $id
     * @return Response
     */
    public function remove()
    {
        $u_id = Input::get('u_id', 0);
        $token = Input::get('token', '');
        $friend = Input::get('friend', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $userFriend = UsersFriend::findLinkById($u_id, $friend);
            if ($userFriend === UsersFriend::$RELATION_NONE) {
            } else {
                $userFriend->remove();
            }
            $re = Tools::reTrue('删除好友成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '删除好友失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function removeInvite($id)
    {
        try {
            $log = UserFriendInviteLog::find($id);
            if (empty($log)) {
                throw new Exception("好友邀请已删除", 3001);
            }
            $u_id = $log->u_id;
            $friend = $log->friend_id;

            $userFriend = UsersFriend::findLinkById($u_id, $friend);
            if ($userFriend === UsersFriend::$RELATION_NONE) {
            } elseif ($userFriend->t_status == 1) {
                $userFriend->remove();
            }
            $log->delete();
            $re = Tools::reTrue('删除好友邀请成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '删除好友邀请失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    private function getUserList($u_id, $status = 0, $gender = 0)
    {
        $query = UsersFriend::with(['user1', 'user2']);
        if ($status) {
            $query = $query->where('t_status', '=', $status);
        }
        $list = $query->where(function ($q) use ($u_id) {
            $q->where('u_id_1', '=', $u_id)->orWhere('u_id_2', '=', $u_id);
        })->get();

        $data = [];
        foreach ($list as $key => $userLink) {
            if ($userLink->t_status == 1) {
                $type = $userLink->t_inviter == $u_id ? UsersFriend::$RELATION_INVITED : UsersFriend::$RELATION_PEDDING_CONFIRM;
            } else {
                $type = UsersFriend::$RELATION_CONFIRMED;
            }
            if ($userLink->u_id_1 == $u_id) {
                $tmp = $userLink->user2->showInList();
            } else {
                $tmp = $userLink->user1->showInList();
            }
            if ($gender && $tmp['gender'] != $gender) {
                continue;
            }
            $tmp['status'] = $type;
            $data[] = $tmp;
        }
        return $data;
    }
}
