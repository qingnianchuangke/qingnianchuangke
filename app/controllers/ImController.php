<?php
/**
*
*/
class ImController extends BaseController
{
    public function getUser($id)
    {
        $token = Input::get('token', '');

        try {
            $user = User::chkUserByToken($token);
            $friend = User::find($id);
            if (empty($friend)) {
                throw new Exception("没有找到请求的数据", 2001);
            }
            $data = $friend->showInImList();
            $re = Tools::reTrue('获取信息成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listUser()
    {
        $token = Input::get('token', '');
        $ids = Input::get('ids', '');
        $ids = explode(',', $ids);

        try {
            $user = User::chkUserByToken($token);
            $friends = User::whereIn('u_id', $ids)->get();  // return all users without any restriction case IM will use all users
            $data = [];
            foreach ($friends as $key => $friend) {
                $data[] = $friend->showInImList();
            }
            $re = Tools::reTrue('获取信息成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }
}
