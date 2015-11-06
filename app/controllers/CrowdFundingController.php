<?php
/**
*
*/
class CrowdFundingController extends \BaseController
{

    public function getCate()
    {
        $data = CrowdFunding::getCrowdFundingCate();
        $re = Tools::reTrue('获取分类成功', $data);
        return Response::json($re);
    }

    public function listCrowdFunding()
    {
        $per_page = Input::get('per_page', 30);

        $cate = Input::get('cate', 0);
        $u_id = Input::get('u_id', 0);

        $range = Input::get('range', 1);
        $city = Input::get('city', 0);
        $school = Input::get('school', 0);

        try {
            if (!$u_id) {
                throw new Exception("请传入用户id", 7001);
            }
            $query = CrowdFunding::with([
                'city',
                'school',
                'user',
                'product',
                'praises' => function ($q) {
                    $q->where('praises.u_id', '=', $this->u_id);
                }
                ])->where('c_status', '>', 2);
            if ($cate) {
                $query = $query->where('c_cate', '=', $cate);
            }
            if ($city && $range == 2) {
                $query = $query->where('c_id', '=', $city);
            }
            if ($school && $range = 3) {
                $query = $query->where('s_id', '=', $school);
            }
            $list = $query->orderBy('created_at', 'DESC')->paginate($per_page);
            $data = [];
            foreach ($list as $key => $funding) {
                $tmp = $funding->showInList();
                $tmp['is_praised'] = 0;
                if (count($funding->praises) > 0) {
                    $tmp['is_praised'] = 1;
                }
                $data[] = $tmp;
            }
            $re = Tools::reTrue('获取众筹成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取众筹失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getCrowdFunding($id)
    {
        $u_id = Input::get('u_id', 0);

        try {
            $crowdfunding = CrowdFunding::find($id);
            $crowdfunding->load([
                'user',
                'replies' => function ($q) {
                    $q->take(10)->orderBy('created_at', 'DESC');
                },
                'praises' => function ($q) {
                    $q->where('praises.u_id', '=', $this->u_id);
                },
                'favorites' => function ($q) {
                    $q->where('favorites.u_id', '=', $this->u_id);
                }
                ]);
            $data = $crowdfunding->showDetail();
            $mobile = '';
            $apartment_no = '';
            if ($crowdfunding->c_open_file) {
                $mobile = $crowdfunding->user->u_mobile;
                $base = UserProfileBase::find($crowdfunding->user->u_id);
                if (!empty($base)) {
                    $apartment_no = $base->u_apartment_no;
                }
            }

            $data['mobile'] = $mobile;
            $data['apartment_no'] = $apartment_no;

            $data['participates_count'] = $crowdfunding->getParticipates(0, true);
            $data['is_praised'] = 0;
            $data['is_favorited'] = 0;
            if (count($crowdfunding->praises) > 0) {
                $data['is_praised'] = 1;
            }
            if (count($crowdfunding->favorites) > 0) {
                $data['is_favorited'] = 1;
            }
            $take = 3;
            if (count($data['replies']) > $take) {
                $data['replies'] = array_slice($data['replies'], 0, $take);
            }
            $re = Tools::reTrue('获取众筹成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取众筹失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postReply($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        $to = Input::get('to', 0);
        $to_u_id = Input::get('to_u_id', 0);
        $content = Input::get('content', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $to_user = User::find($to_u_id);
            if (empty($to_user)) {
                $to_u_id = 0;
                $to_u_name = '';
            } else {
                $to_u_name = $to_user->u_nickname;
            }
            $cf = CrowdFunding::find($id);
            $reply = [
                'to_id' => $to,
                'created_at' => Tools::getNow(),
                'content' => $content,
                'u_id' => $u_id,
                'u_name' => $user->u_nickname,
                'status' => 1,
                'to_u_id' => $to_u_id,
                'to_u_name' => $to_u_name,
            ];
            $replyObj = new Reply($reply);
            $cf->replies()->save($replyObj);
            $re = Tools::reTrue('回复成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '回复失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postOrder($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        
        $p_id = Input::get('product', 0);
        $quantity = Input::get('quantity', 0);

        $shipping_name = Input::get('shipping_name', '');
        $shipping_phone = Input::get('shipping_phone', '');
        $shipping_address = Input::get('shipping_address', '');
        $comment = Input::get('comment', '');

        DB::beginTransaction();
        try {
            $user = User::chkUserByToken($token, $u_id);
            $product = CrowdFundingProduct::find($p_id);
            $funding = CrowdFunding::find($id);

            // add cart
            $cart = new Cart();
            $cart->p_id = $p_id;
            $cart->p_name = $product->p_title;
            $cart->u_id = $u_id;
            $cart->b_id = $product->b_id;
            $cart->created_at = Tools::getNow();
            $cart->c_quantity = $quantity;
            $cart->c_price = $product->p_price;
            $cart->c_amount = $product->p_price * $quantity;
            $cart->c_discount = 100;
            $cart->c_price_origin = $product->p_price;
            $cart->c_amount_origin = $product->p_price * $quantity;
            $cart->c_status = 2;
            $cart->c_type = 2;
            $cart->save();
            $product->loadProduct($quantity);
            if (!$funding->c_shipping) {
                $shipping_address = '';
            }
            $shipping_name = $shipping_name ? $shipping_name : $user->u_name;
            $shipping_phone = $shipping_phone ? $shipping_phone : $user->u_mobile;

            $date_obj = new DateTime($funding->active_at);
            $delivery_time_obj = $date_obj->modify('+'.($funding->c_time+$funding->c_yield_time).'days');

            // add order
            $order_group_no = Order::generateOrderGroupNo($u_id);
            $rnd_str = rand(10, 99);
            $order_no = $order_group_no.$cart->b_id.$rnd_str;
            $order = new Order();
            $order->u_id = $u_id;
            $order->b_id = $cart->b_id;
            $order->o_amount_origin = $cart->c_amount_origin;
            $order->o_amount = $cart->c_amount;
            $order->o_shipping_fee = $funding->c_shipping_fee;
            $order->o_shipping_name = $shipping_name;
            $order->o_shipping_phone = $shipping_phone;
            $order->o_shipping_address = $shipping_address;
            $order->o_delivery_time = $delivery_time_obj->format('Y-m-d H:i:s');
            $order->o_shipping = $funding->c_shipping;
            $order->o_comment = $comment;
            $order->o_number = $order_no;
            $order->o_group_number = $order_group_no;
            $o_id = $order->addOrder();
            Cart::bindOrder([$order->o_id => [$cart->c_id]]);

            // push msg to seller
            $booth = Booth::find($cart->b_id);
            $msg = new PushMessage($booth->u_id);
            $msg->pushMessage('您有新的订单, 请及时发货');
            $data = ['order_no' => $order_group_no];
            $re = Tools::reTrue('提交订单成功', $data);
            DB::commit();
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '提交订单失败:'.$e->getMessage());
            DB::rollback();
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
            $funding = CrowdFunding::find($id);
            if (empty($funding)) {
                throw new Exception("请求的众筹不存在", 2001);
            }
            if ($type == 1) {
                $chk = $funding->praises()->where('praises.u_id', '=', $u_id)->first();
                if (empty($chk)) {
                    $data = [
                        'u_id' => $u_id,
                        'created_at' => Tools::getNow(),
                        'u_name' => $user->u_name
                    ];
                    $praise = new Praise($data);
                    $funding->praises()->save($praise);
                    $funding->c_praise_count++;
                    $funding->save();
                }
            } else {
                $chk = $funding->praises()->where('praises.u_id', '=', $u_id)->first();
                if (!empty($chk)) {
                    $funding->praises()->detach($chk->id);
                    $chk->delete();
                }
            }
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
        $type = Input::get('type', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $funding = CrowdFunding::find($id);
            if (empty($funding)) {
                throw new Exception("请求的众筹不存在", 2001);
            }
            if ($type == 1) {
                $chk = $funding->favorites()->where('favorites.u_id', '=', $u_id)->first();
                if (empty($chk)) {
                    $data = [
                        'u_id' => $u_id,
                        'created_at' => Tools::getNow(),
                        'u_name' => $user->u_name
                    ];
                    $favorite = new Favorite($data);
                    $funding->favorites()->save($favorite);
                }
            } else {
                $chk = $funding->favorites()->where('favorites.u_id', '=', $u_id)->first();
                if (!empty($chk)) {
                    $funding->favorites()->detach($chk->id);
                    $chk->delete();
                }
            }
            $re = Tools::reTrue('操作成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '操作失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listParticipates($id)
    {
        $per_page = Input::get('per_page');

        try {
            $funding = CrowdFunding::find($id);
            if (empty($funding) || $funding->c_status < 3) {
                throw new Exception("没有找到请求的众筹信息", 1);
            }
            $participates = $funding->getParticipates($per_page);
            $data = [];
            foreach ($participates as $key => $user) {
                $tmp = $user->showInList();
                $tmp['shipping_address'] = $user->o_shipping_address;
                $tmp['comment'] = $user->o_comment;
                $tmp['shipping_phone'] = $user->o_shipping_phone;
                $tmp['quantity'] = $user->c_quantity;
                $data[] = $tmp;
            }
            $re = Tools::reTrue('获取参与用户成功', $data, $participates);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取参与用户失败:'.$e->getMessage());
        }
        return Response::json($re);
    }
}
