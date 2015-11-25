<?php
/**
*
*/
class Advertisement extends Eloquent
{
    public $primaryKey = 'ad_id';
    public $timestamps = false;

    public function addAd()
    {
        $this->created_at = Tools::getNow();
        $this->ad_status = 1;
        return $this->save();
    }

    public function showInList()
    {
        $data = [];
        $data['id'] = $this->ad_id;
        $data['status'] = $this->ad_status;
        if (empty($this->eventItem)) {
            $this->load('eventItem');
        }
        if (empty($this->eventItem)) {
            $data['title'] = '';
            $data['cover_img'] = '';
            $data['url'] = '';
            $data['brief'] = '';
        } else {
            $data['title'] = $this->eventItem->e_title;
            $data['cover_img'] = $this->eventItem->cover_img;
            $data['url'] = $this->eventItem->url;
            $data['brief'] = $this->eventItem->e_brief;
        }

        return $data;
    }

    public function showDetail()
    {
        $data = [];
        $data['id'] = $this->ad_id;
        $data['status'] = $this->ad_status;
        $data['o_id'] = $this->o_id;
        if (empty($this->eventItem)) {
            $this->load('eventItem');
        }
        if (empty($this->eventItem)) {
            $data['title'] = '';
            $data['cover_img'] = '';
            $data['url'] = '';
            $data['range'] = '';
            $data['position'] = '';
            $data['start_at'] = '';
            $data['end_at'] = '';
        } else {
            $data = array_merge($data, $this->eventItem->showDetail());
        }
        return $data;
    }

    public function delAd()
    {
        $this->load(['eventItem']);
        if (!empty($this->eventItem)) {
            $this->eventItem->delEventItem();
        }
        $this->delete();
    }

    public static function fetchAd($position, $s_id = 0, $c_id = 0, $p_id = 0)
    {
        $query = Advertisement::select('advertisements.*')
        ->with(['eventItem'])
        ->join('event_positions', function ($q) use ($position) {
            $q->on('event_positions.e_id', '=', 'advertisements.e_id')
            ->where('event_positions.position', '=', $position);
        })->join('event_ranges', function ($q) use ($s_id, $c_id, $p_id) {
            $q->on('event_ranges.e_id', '=', 'advertisements.e_id');
        })->where(function ($q) {
            $q->where('event_ranges.s_id', '=', 0)
            ->where('event_ranges.c_id', '=', 0)
            ->where('event_ranges.p_id', '=', 0);
        })->orWhere(function ($q) use ($s_id) {
            $q->where('event_ranges.s_id', '=', $s_id);
        })->orWhere(function ($q) use ($c_id, $p_id) {
            $q->where('event_ranges.c_id', '=', $c_id)
            ->where('event_ranges.p_id', '=', $p_id);
        });
        $ads = $query->paginate(1);
        if (count($ads) > 0) {
            $data = [];
            foreach ($ads as $key => $ad) {
                $data = $ad->showInList();
                $data['item_type'] = 2;
            }
        } else {
            $data = null;
        }
        return $data;
    }

    // relation
    //
    public function eventItem()
    {
        return $this->hasOne('EventItem', 'e_id', 'e_id');
    }
}