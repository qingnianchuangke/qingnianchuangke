<?php
/**
*
*/
class AuctionBid extends Eloquent
{
    public $primaryKey = 'b_id';
    public $timestamps = false;

    public function showInList()
    {
        $data = [];
        $data['price'] = $this->b_price;
        $date_obj = new DateTime($this->created_at);
        $data['created_at'] = $date_obj->format('Y-m-d H:i:s');
        $data['is_win'] = $this->is_win;
        $data['is_pay'] = $this->is_pay;
        return $data;
    }

    public function addBid()
    {
        $this->created_at = Tools::getNow();
        return $this->save();
    }

    public static function checkBlacklist($u_id)
    {
        $now = Tools::getNow();
        $blacklist = AuctionBlacklist::where('u_id', '=', $u_id)->where('start_at', '<', $now)->where('end_at', '>', $now)->first();
        if (empty($blacklist)) {
            return true;
        } else {
            return false;
        }
    }
}
