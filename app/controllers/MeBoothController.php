<?php
/**
*
*/
class MeBoothController extends \BaseController
{
    public function putBoothDesc($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $desc = Input::get('desc', '');
        $title = Input::get('title', '');

        $img_token = Input::get('img_token', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $booth = Booth::find($id);
            if (empty($booth->b_id) || $booth->u_id != $u_id) {
                throw new Exception("无法获取到请求的店铺", 7001);
            }
            $booth->b_desc = $desc;
            $booth->b_title = $title;
            if ($img_token) {
                $imgObj = new Img('booth', $img_token);
                $booth->b_imgs = $imgObj->getSavedImg($booth->b_id, $booth->b_imgs);
            }
            $booth->save();
            $re = ['result' => 2000, 'data' => [], 'info' => '更新店铺成功'];
        } catch (Exception $e) {
            $code = 7001;
            if ($e->getCode() > 2000) {
                $code = $e->getCode();
            }
            $re = ['result' => $code, 'data' => [], 'info' => '更新店铺失败:'.$e->getMessage()];
        }
        return Response::json($re);
    }

    public function getBooth($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $booth = Booth::find($id);
            if (empty($booth) || $booth->u_id != $u_id) {
                throw new Exception("无法获取请求的店铺", 7001);
            }
            $data = $booth->showDetail();
            $re = Tools::reTrue('获取店铺信息成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取店铺信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }
}
