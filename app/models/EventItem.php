<?php
/**
*
*/
class EventItem extends Eloquent
{
    public $primaryKey = 'e_id';
    public $timestamps = false;

    public function addEvent()
    {
        $this->created_at = Tools::getNow();
        $this->e_status = 1;
        return $this->save();
    }

    public function showInList()
    {
        $data = [];
        return $data;
    }

    public function showDetail()
    {
        $data = [];
        $data['title'] = $this->e_title;
        $data['cover_img'] = Img::toArray($this->cover_img);
        $data['url'] = $this->url;
        $data['range'] = $this->e_range;
        if (count($this->positions) > 0) {
            $positions = [];
            foreach ($this->positions as $position) {
                $positions[] = $position->showInList();
            }
            $data['positions'] = $positions;
        } else {
            $data['positions'] = null;
        }

        if (count($this->ranges) > 0) {
            $ranges = [];
            foreach ($this->ranges as $range) {
                $ranges[] = $range->showInList();
            }
            $data['ranges'] = $ranges;
        }
        $data['start_at'] = $this->e_start_at;
        $data['end_at'] = $this->e_end_at;
        return $data;
    }

    public function delEventItem()
    {
        $this->load(['ranges', 'positions']);
        if (count($this->positions) > 0) {
            foreach ($this->positions as $position) {
                $positions[] = $position->delete();
            }
        }

        if (count($this->ranges) > 0) {
            foreach ($this->ranges as $range) {
                $ranges[] = $range->delete();
            }
        }
        $this->delete();
    }

    // relations
    public function ranges()
    {
        return $this->hasMany('EventRange', 'e_id', 'e_id');
    }

    public function positions()
    {
        return $this->hasMany('EventPosition', 'e_id', 'e_id');
    }
}
