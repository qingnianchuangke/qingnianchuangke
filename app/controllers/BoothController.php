<?php
/**
*
*/
class BoothController extends \BaseController
{
    public function postPraise($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $type = Input::get('type', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $booth = Booth::find($id);
            if (empty($booth)) {
                throw new Exception("请求的店铺不存在", 2001);
            }
            $chk = $booth->praises()->where('praises.u_id', '=', $u_id)->first();
            if ($type == 1) {
                if (empty($chk)) {
                    $data = [
                        'u_id' => $u_id,
                        'created_at' => Tools::getNow(),
                        'u_name' => $user->u_name
                    ];
                    $praise = new Praise($data);
                    $booth->praises()->save($praise);
                    $booth->b_praise_count++;
                }
            } else {
                if (!empty($chk)) {
                    $booth->praises()->detach($chk->id);
                    $chk->delete();
                    $booth->b_praise_count = --$booth->b_praise_count <= 0 ? 0 : $booth->b_praise_count;
                }
            }
            $booth->save();
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
            $booth = Booth::find($id);
            if (empty($booth)) {
                throw new Exception("请求的店铺不存在", 2001);
            }
            $chk = $booth->favorites()->where('favorites.u_id', '=', $u_id)->first();
            if ($type == 1) {
                if (empty($chk)) {
                    $data = [
                        'u_id' => $u_id,
                        'created_at' => Tools::getNow(),
                        'u_name' => $user->u_nickname
                    ];
                    $favorite = new Favorite($data);
                    $booth->favorites()->save($favorite);
                }
            } else {
                if (!empty($chk)) {
                    $booth->favorites()->detach($chk->id);
                    $chk->delete();
                }
            }
            $re = Tools::reTrue('操作成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '操作失败:'.$e->getMessage());
        }
        return Response::json($re);
    }
}
