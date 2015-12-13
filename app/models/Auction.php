<?php
/**
*
*/
class Auction extends Eloquent
{
    public $primaryKey = 'a_id';
    public $timestamps = false;

    public function showInList()
    {
        
    }

    public function showDetail()
    {
        $data = [];
        $data['id'] = $this->a_id;
        $data['sub_title'] = $this->a_sub_title;
        $data['win_username'] = $this->a_win_username;
        $data['win_price'] = $this->a_win_price;
        $data['status'] = $this->a_status;
        $data['cost'] = $this->a_cost;
        $data['current_time'] = Tools::getNow();
        if (empty($this->eventItem)) {
            $data['title'] = '';
            $data['start_at'] = '';
            $data['end_at'] = '';
            $data['url'] = '';
            $data['cover_img'] = [];
            $data['detail_content_img'] = [];
            $data['detail_header_img'] = [];
        } else {
            $data['title'] = $this->eventItem->e_title;
            $data['start_at'] = $this->eventItem->e_start_at;
            $data['end_at'] = $this->eventItem->e_end_at;
            $data['url'] = $this->eventItem->url;
            $data['cover_img'] = Img::filterKey('cover_img', Img::toArray($this->eventItem->cover_img));
            $data['detail_content_img'] = Img::filterKey('detail_content_img', Img::toArray($this->eventItem->cover_img));
            $data['detail_header_img'] = Img::filterKey('detail_header_img', Img::toArray($this->eventItem->cover_img));
        }
        return $data;
    }

    public function addAuction()
    {
        $this->created_at = Tools::getNow();
        $this->a_status = 1;
        return $this->save();
    }

    public static function cronRunTheWheel()
    {
        $now = Tools::getNow();
        $auction = Auction::join('event_items', function ($q) {
            $q->on('event_items.e_id', '=', 'auctions.e_id');
        })->where('event_items.e_end_at', '<', $now)
        ->where('auctions.a_status', '=', 1)->orderBy('event_items.e_start_at')->first();
        if (empty($auction)) {
            throw new Exception("没有需要处理的竞拍", 2000);
        }
        $auction->load(['eventItem']);
        $list = AuctionBid::where('a_id', '=', $auction->a_id)->orderBy('b_price', 'DESC')->get();

        if ($list->count() <= 0) {
            throw new Exception("无人出价", 2000);
        }
        $win = $list->first();
        if ($win->is_win) {
            throw new Exception("中奖信息已处理", 2000);
        }
        $user = User::find($win->u_id);
        $auction->a_win_username = $user->u_nickname;
        $auction->a_win_id = $win->b_id;
        $auction->a_win_price = $win->b_price;
        $auction->a_status = 2;

        foreach ($list as $key => $bid) {
            if ($bid->u_id == $win->u_id) {
                continue;
            } else {
                $msg = new MessageDispatcher($bid->u_id);
                $msg->fireTextToUser('非常抱歉您参与的'.$auction->eventItem->e_title.'没有拍到');
            }
        }

        $price = number_format($auction->a_win_price);
        $msg = new MessageDispatcher($win->u_id, 1, 1, 1);
        $msg->setMessage(['phone' => $user->u_mobile]);
        $msg->fireTextToUser('恭喜您以'.$price.'元成功拍得 '.$auction->eventItem->e_title.' 产品。请于48小时之内在我的竞拍里完成付款，逾期视为放弃，感谢您的参与');

        $win->is_win = 1;
        $win->is_pay = 0;
        $auction->save();
        $win->save();
        return true;
    }

    public static function cronYouCheater()
    {
        $date = Tools::getNow(false);
        $now = $date->format('Y-m-d H:i:s');
        $date->modify('-2 days');
        $end = $date->format('Y-m-dH:i:s');
        $auction = Auction::join('event_items', function ($q) {
            $q->on('event_items.e_id', '=', 'auctions.e_id');
        })->where('event_items.e_end_at', '<', $end)
        ->where(function ($q) {
            $q->where('auctions.a_status', '=', 2)
            ->orWhere('auctions.a_status', '=', 1);
        })->first();

        if (empty($auction)) {
            throw new Exception("没有需要处理的竞拍", 2000);
        }

        if ($auction->a_status == 2 && $auction->a_win_id) {
            $win = AuctionBid::find($auction->a_win_id);
            if (!empty($win)) {
                $date->modify('+10 days');
                $end = $date->format('Y-m-d H:i:s');
                $blacklist = new AuctionBlacklist();
                $blacklist->u_id = $win->u_id;
                $blacklist->a_id = $auction->a_id;
                $blacklist->start_at = $now;
                $blacklist->end_at = $end;
                $blacklist->remart = '超时未购买';
                $blacklist->save();
            }
        }

        $auction->a_status = 4;
        $auction->save();
        return true;
    }

    // relation
    //
    public function winBid()
    {
        return $this->hasOne('AuctionBid', 'a_win_id', 'b_id');
    }

    public function eventItem()
    {
        return $this->hasOne('EventItem', 'e_id', 'e_id');
    }
}
