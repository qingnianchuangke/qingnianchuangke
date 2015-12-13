<?php
/**
*
*/
class AuctionController extends \BaseController
{
    public function show()
    {
        try {
            $date = Tools::getNow(false);
            $date->modify('-10 minutes');
            $now = $date->format('Y-m-d H:i:s');
            $auction = Auction::with('eventItem')
            ->join('event_items', function ($q) {
                $q->on('event_items.e_id', '=', 'auctions.e_id');
            })->where('event_items.e_end_at', '>', $now)
            ->orderBy('event_items.e_start_at')
            ->first();
            if (empty($auction)) {
                $data = null;
            } else {
                $data = $auction->showDetail();
            }
            $re = Tools::reTrue('获取竞拍成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取竞拍失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function bid($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $price = Input::get('price', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            if (!$price) {
                throw new Exception("请输入价格", 2001);
            }

            $auction = Auction::find($id);
            $auction->load(['eventItem']);
            $now = Tools::getNow();
            if ($auction->eventItem->e_start_at > $now) {
                throw new Exception("还没开始", 2001);
            }
            if ($auction->eventItem->e_end_at < $now) {
                throw new Exception("已经结束", 2001);
            }
            if ($auction->a_cost < $price) {
                throw new Exception("竞拍价格不能超过市场价", 2001);
            }
            if (!AuctionBid::checkBlacklist($u_id)) {
                throw new Exception("现在还无法出价", 2001);
            }

            $bid = new AuctionBid();
            $bid->a_id = $id;
            $bid->u_id = $u_id;
            $bid->b_price = $price;
            $bid->addBid();
            $re = Tools::reTrue('出价成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '出价失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listAuctions()
    {
        $per_page = Input::get('per_page', 30);

        try {
            $now = Tools::getNow();

            $list = Auction::with('eventItem')
            ->join('event_items', function ($q) {
                $q->on('event_items.e_id', '=', 'auctions.e_id');
            })->where('event_items.e_end_at', '<', $now)->orderBy('event_items.e_end_at', 'DESC')->paginate($per_page);
            $data = [];
            foreach ($list as $key => $auction) {
                $data[] = $auction->showDetail();
            }
            $re = Tools::reTrue('获取往届竞拍成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取往届竞拍失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getAuction($id)
    {
        try {
            $auction = Auction::find($id);
            if (empty($auction)) {
                throw new Exception("没有找到请求的数据", 2001);
            }
            $auction->load('eventItem');
            $data = $auction->showDetail();
            $re = Tools::reTrue('获取竞拍成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取竞拍失败:'.$e->getMessage());
        }
        return Response::json($re);
    }
}
