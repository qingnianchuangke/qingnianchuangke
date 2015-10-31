<?php
/**
*
*/
class ProductController extends \BaseController
{
    public function postPraise($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $product = Product::find($id);
            if (empty($product)) {
                throw new Exception("请求的商品不存在", 2001);
            }
            $chk = $product->praises()->where('praises.u_id', '=', $u_id)->first();
            if (!empty($chk)) {
                throw new Exception("已经赞过了", 7001);
            }
            $data = [
                'u_id' => $u_id,
                'created_at' => Tools::getNow(),
                'u_name' => $user->u_name
            ];
            $praise = new Praise($data);
            $product->praises()->save($praise);
            $product->p_praise_count++;
            $product->save();
            $re = Tools::reTrue('点赞成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '点赞失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postFavorite($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $product = Product::find($id);
            if (empty($product)) {
                throw new Exception("请求的商品不存在", 2001);
            }
            $chk = $product->favorites()->where('favorites.u_id', '=', $u_id)->first();
            if (!empty($chk)) {
                throw new Exception("已经收藏过了", 7001);
            }
            $data = [
                'u_id' => $u_id,
                'created_at' => Tools::getNow(),
                'u_name' => $user->u_name
            ];
            $favorite = new Favorite($data);
            $product->favorites()->save($favorite);
            $re = Tools::reTrue('收藏成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '收藏失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function delFavorite($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $product = Product::find($id);
            if (empty($product)) {
                throw new Exception("请求的商品不存在", 2001);
            }
            $chk = $product->favorites()->where('favorites.u_id', '=', $u_id)->first();
            if (empty($chk)) {
                throw new Exception("已经取消收藏了", 7001);
            }
            $product->favorites()->detach($chk->id);
            $chk->delete();
            $re = Tools::reTrue('取消收藏成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '取消收藏失败:'.$e->getMessage());
        }
        return Response::json($re);
    }
}
