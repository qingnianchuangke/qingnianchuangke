
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
        $type = Input::get('type', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $product = Product::find($id);
            if (empty($product)) {
                throw new Exception("请求的商品不存在", 2001);
            }
            $chk = $product->praises()->where('praises.u_id', '=', $u_id)->first();
            if ($type == 1) {
                if (empty($chk)) {
                    $data = [
                        'u_id' => $u_id,
                        'created_at' => Tools::getNow(),
                        'u_name' => $user->u_name
                    ];
                    $praise = new Praise($data);
                    $product->praises()->save($praise);
                    $product->p_praise_count++;
                }
            } else {
                if (!empty($chk)) {
                    $product->praises()->detach($chk->id);
                    $chk->delete();
                    $product->p_praise_count = --$product->p_praise_count <= 0 ? 0 : $product->p_praise_count;
                }
            }
            $product->save();
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
            $product = Product::find($id);
            if (empty($product)) {
                throw new Exception("请求的商品不存在", 2001);
            }
            $chk = $product->favorites()->where('favorites.u_id', '=', $u_id)->first();
            if ($type == 1) {
                if (empty($chk)) {
                    $data = [
                        'u_id' => $u_id,
                        'created_at' => Tools::getNow(),
                        'u_name' => $user->u_nickname
                    ];
                    $favorite = new Favorite($data);
                    $product->favorites()->save($favorite);
                }
            } else {
                if (!empty($chk)) {
                    $product->favorites()->detach($chk->id);
                    $chk->delete();
                }
            }
            $re = Tools::reTrue('操作成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '操作失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getProduct($id)
    {
        $u_id = Input::get('u_id');

        try {
            $product = Product::with([
                'user',
                'booth' => function ($q) {
                    $q->with(['school']);
                },
                'quantity',
                'promo',
                'replies' => function ($q) {
                    $q->with(['user'])->take(3)->orderBy('created_at', 'DESC');
                },
                'favorites' => function ($q) {
                    $q->where('favorites.u_id', '=', $this->u_id);
                },
                'praises' => function ($q) {
                    $q->where('praises.u_id', '=', $this->u_id);
                }
                ])->find($id);
            if (empty($product->p_id)) {
                throw new Exception("无法找到请求的产品", 7001);
            }
            $data = $product->showDetail();
            // delete this after several upgrade
            if (!empty($data['user']['mobile'])) {
                $data['user']['mobile'] = $data['mobile'];
            }
            if (empty($data['booth']['school'])) {
                $data['school'] = [];
            } else {
                $data['school'] = $data['booth']['school'];
                unset($data['booth']);
            }
            $data['is_praised'] = 0;
            $data['is_favorited'] = 0;
            if (count($product->praises) > 0) {
                $data['is_praised'] = 1;
            }
            if (count($product->favorites) > 0) {
                $data['is_favorited'] = 1;
            }
            $re = Tools::reTrue('获取产品成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取产品失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postReply($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $content = Input::get('content', '');
        $to_u_id = Input::get('to', 0);
        $to = Input::get('parent', 0);

        try {
            $product = Product::find($id);
            $product->p_reply_count += 1;

            $user = User::chkUserByToken($token, $u_id);

            $to_user = User::find($to_u_id);
            if (empty($to_user)) {
                $to_u_id = 0;
                $to_u_name = '';
            } else {
                $to_u_name = $to_user->u_nickname;
                if ($product->p_type == 2) {
                    $cate = Notification::$CATE_FLEA;
                } else {
                    $cate = Notification::$CATE_PRODUCT_PROMO;
                }
                $msg = new MessageDispatcher();
                $msg->fireCateToUser('您有新的用户回复', $cate, $id);
            }
            $data = [
                'to_id' => $to,
                'created_at' => Tools::getNow(),
                'content' => $content,
                'u_id' => $u_id,
                'u_name' => $user->u_nickname,
                'status' => 1,
                'to_u_id' => $to_u_id,
                'to_u_name' => $to_u_name,
            ];
            $reply = new Reply($data);
            $product->replies()->save($reply);
            $product->save();
            $re = Tools::reTrue('回复成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '回复失败:'.$e->getMessage());
        }
        return Response::json($re);
    }
}
