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
        $date = new DateTime($this->created_at);
        $data['created_at'] = $date->format('Y-m-d H:i:s');
        $data['created_at_timestamps'] = strtotime($data['created_at']);
        if (empty($this->eventItem)) {
            $this->load('eventItem');
        }
        if (empty($this->eventItem)) {
            $data['title'] = '';
            $data['cover_img'] = null;
            $data['url'] = '';
            $data['brief'] = '';
            $data['active_at'] = '';
        } else {
            $data['title'] = $this->eventItem->e_title;
            $data['cover_img'] = Img::filterKey('cover_img', Img::toArray($this->eventItem->cover_img));
            $data['url'] = $this->eventItem->url;
            $data['brief'] = $this->eventItem->e_brief;
            $data['active_at'] = $this->eventItem->e_start_at;
        }

        return $data;
    }

    public function showDetail()
    {
        $data = [];
        $data['id'] = $this->ad_id;
        $data['status'] = $this->ad_status;
        $date = new DateTime($this->created_at);
        $data['created_at'] = $date->format('Y-m-d');
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

    public static function fetchAd($position, $start_at, $end_at, $s_id = 0, $c_id = 0, $p_id = 0, $range = 1)
    {
        $query = Advertisement::select('advertisements.*')
        ->with(['eventItem'])->where('advertisements.ad_status', '>', 0)
        ->join('event_positions', function ($q) use ($position) {
            $q->on('event_positions.e_id', '=', 'advertisements.e_id')
            ->where('event_positions.position', '=', $position);
        })->join('event_ranges', function ($q) {
            $q->on('event_ranges.e_id', '=', 'advertisements.e_id');
        });

        if ($range == 1) {
            $query = $query->where('event_ranges.s_id', '=', 0)
                ->where('event_ranges.c_id', '=', 0)
                ->where('event_ranges.p_id', '=', 0);
        }
        if ($range == 2) {
            $query = $query->where(function ($q) use ($c_id, $p_id) {
                $q->where(function ($qq) use ($c_id, $p_id) {
                    $qq->where('event_ranges.c_id', '=', $c_id)
                    ->where('event_ranges.p_id', '=', $p_id);
                })->orWhere(function ($qq) {
                    $qq->where('event_ranges.s_id', '=', 0)
                    ->where('event_ranges.c_id', '=', 0)
                    ->where('event_ranges.p_id', '=', 0);
                });
            });
        }
        if ($range == 3) {
            $query = $query->where(function ($q) use ($s_id) {
                $q->where(function ($qq) use ($s_id) {
                    $qq->where('event_ranges.s_id', '=', $s_id);
                })->orWhere(function ($qq) {
                    $qq->where('event_ranges.s_id', '=', 0)
                    ->where('event_ranges.c_id', '=', 0)
                    ->where('event_ranges.p_id', '=', 0);
                });
            });
        }
        $query = $query->join('event_items', function ($q) {
            $q->on('event_items.e_id', '=', 'advertisements.e_id');
        });
        if ($start_at) {
            $query = $query->where('advertisements.created_at', '>', $start_at);
        }
        if ($end_at) {
            $query = $query->where('advertisements.created_at', '<', $end_at);
        }

        $now = Tools::getNow();
        $query = $query->where('event_items.e_start_at', '<', $now)->where('event_items.e_end_at', '>', $now);

        $query->orderBy('advertisements.created_at', 'DESC');
        $ads = $query->get();

        if (count($ads) > 0) {
            $data = [];
            foreach ($ads as $key => $ad) {
                $tmp = $ad->showInList();
                $tmp['item_type'] = 2;
                $data[] = $tmp;
            }
        } else {
            $data = null;
        }
        return $data;
    }

    public static function mergeArray($data, $ads)
    {
        $result = [];
        foreach ($data as $row) {
            if (empty($ads)) {
                $result[] = $row;
            } else {
                foreach ($ads as $key => $ad) {
                    if ($ad['created_at_timestamps'] > $row['created_at_timestamps']) {
                        $result[] = $ad;
                        unset($ads[$key]);
                    }
                }
                $result[] = $row;
            }
        }
        if (!empty($ads)) {
            $result = array_merge($result, $ads);
        }
        return $result;
    }

    // relation
    //
    public function eventItem()
    {
        return $this->hasOne('EventItem', 'e_id', 'e_id');
    }
}
