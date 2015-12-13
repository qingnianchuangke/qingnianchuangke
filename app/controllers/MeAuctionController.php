<?php
/**
*
*/
class MeAuctionController extends BaseController
{
    public function listBids($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', '');

        $just_check = Input::get('just_check', 1);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $list = AuctionBid::where('u_id', '=', $u_id)->where('a_id', '=', $id)->get();
            $data = [];
            if (count($list) == 0) {
                $data['bought'] = 0;
            } else {
                $data['bought'] = 1;
                if (!$just_check) {
                    foreach ($list as $key => $bid) {
                        $data['bids'][] = $bid->shwoInList();
                    }
                }
            }
            $re = Tools::reTrue('获取出价记录成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取出价记录失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postOrder($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', '');

        $shipping_name = Input::get('shipping_name', '');
        $shipping_phone = Input::get('shipping_phone', '');
        $shipping_address = Input::get('shipping_address', '');
        $comment = Input::get('comment', '');

        DB::beginTransaction();

        try {
            $user = User::chkUserByToken($token, $u_id);
            $now = Tools::getNow();

            $auction = Auction::find($id);
            if (empty($auction) || $auction->a_status != 2) {
                throw new Exception("请求的竞拍不存在", 2001);
            }
            $auction->load(['eventItem']);

            $bid = AuctionBid::find($auction->a_win_id);
            if (empty($bid) || $bid->is_pay) {
                throw new Exception("出价信息有误", 2001);
            }

            $limit = new DateTime($auction->eventItem->e_end_at);
            $limit->modify('+3 days');
            $now = Tools::getNow(false);
            if ($limit < $now) {
                $blacklist = new AuctionBlacklist();
                $blacklist->u_id = $u_id;
                $blacklist->a_id = $auction->a_id;
                $blacklist->start_at = $now;
                $now->modify('+7 days');
                $blacklist->end_at = $now->format('Y-m-d H:i:s');
                $blacklist->remark = '超时未购买';
                $blacklist->save();
                $auction->a_status = 4;
                $auction->save();
                throw new Exception("竞拍已超时, 无法购买", 2);  //when exception code is 2, commit anyway after catch
            }

             // add cart
            $cart = new Cart();
            $cart->p_id = $auction->a_id;
            $cart->p_name = $auction->eventItem->e_title;
            $cart->u_id = $u_id;
            $cart->b_id = Tools::getMakerBooth();
            $cart->created_at = Tools::getNow();
            $cart->c_quantity = 1;
            $cart->c_price = $auction->a_win_price;
            $cart->c_amount = $auction->a_win_price;
            $cart->c_discount = 100;
            $cart->c_price_origin = $auction->a_cost;
            $cart->c_amount_origin = $auction->a_cost;
            $cart->c_status = 2;
            $cart->c_type = 4;
            $re = $cart->save();
            if (!$re) {
                throw new Exception("提交库存失败", 7006);
            }

            $shipping_name = $shipping_name ? $shipping_name : $user->u_name;
            $shipping_phone = $shipping_phone ? $shipping_phone : $user->u_mobile;

            // add order
            $order_group_no = Order::generateOrderGroupNo($u_id);
            $rnd_str = rand(10, 99);
            $order_no = $order_group_no.$cart->b_id.$rnd_str;
            $order = new Order();
            $order->u_id = $u_id;
            $order->b_id = $cart->b_id;
            $order->o_amount_origin = $cart->c_amount_origin;
            $order->o_amount = $cart->c_amount;
            $order->o_shipping_fee = 0.00;
            $order->o_shipping_name = $shipping_name;
            $order->o_shipping_phone = $shipping_phone;
            $order->o_shipping_address = $shipping_address;
            $order->o_delivery_time = Tools::getNow();
            $order->o_shipping = 1;
            $order->o_comment = $comment;
            $order->o_number = $order_no;
            $order->o_group_number = $order_group_no;
            $o_id = $order->addOrder();

            Cart::bindOrder([$order->o_id => [$cart->c_id]]);

            $data = ['order_no' => $order_group_no];
            $re = Tools::reTrue('提交订单成功', $data);
            DB::commit();
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '提交订单失败:'.$e->getMessage());
            if ($e->getCode() == 2) {
                DB::commit();
            } else {
                DB::rollback();
            }
        }
        return Response::json($re);
    }

    public function listAuctions()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', '');

        $won = Input::get('won');

        $per_page = Input::get('per_page', 30);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $query = Auction::with('eventItem')
            ->join('event_items', function ($q) {
                $q->on('event_items.e_id', '=', 'auctions.e_id');
            })->join('auction_bids', function ($q) {
                $q->on('auction_bids.a_id', '=', 'auctions.a_id');
            })->where('auction_bids.u_id', '=', $u_id);

            if ($won) {
                $query = $query->where('auction_bids.is_win', '=', 1);
            }
            $query = $query->groupBy('auctions.a_id')->orderBy('auctions.created_at', 'DESC');
            $list = $query->paginate($per_page);

            $data = [];
            foreach ($list as $key => $auction) {
                $data[] = $auction->showDetail();
            }
            $re = Tools::reTrue('获取竞拍成功', $data, $list);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取竞拍失败:'.$e->getMessage());
        }
        return Response::json($re);
    }
}
