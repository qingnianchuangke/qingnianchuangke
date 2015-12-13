<?php
/**
*
*/
class Reply extends Eloquent
{
    protected $fillable = ['to_id', 'created_at', 'content', 'u_id', 'u_name', 'status', 'to_u_id', 'to_u_name'];

    public $timestamps = false;

    public static function getRepliableCate()
    {
        return [
            'product' => 'Product',
            'crowd' => 'CrowdFunding',
        ];
    }

    public function showInList()
    {
        $data = [];
        $data['id'] = $this->id;
        $data['content'] = $this->content;
        $data['u_id'] = $this->u_id;
        $data['u_name'] = $this->u_name;
        if (!empty($this->user)) {
            $data['u_head_img'] = $this->user->getHeadImg();
        }
        $data['to_u_id'] = $this->to_u_id;
        $data['to_u_name'] = $this->to_u_name;
        if (!empty($this->userTo)) {
            $data['to_u_head_img'] = $this->userTo->getHeadImg();
        }
        $dateObj = new DateTime($this->created_at);
        $data['created_at'] = $dateObj->format('Y-m-d H:i:s');


        return $data;
    }

    public static function makeTree($replies)
    {
        if (empty($replies)) {
            return [];
        }
        $tree = [];
        foreach ($replies as $key => $reply) {
            if (empty($tree[$reply->id]) && !$reply->to_id) {
                $tree[$reply->id] = ['children' => []];
            }
            $data = $reply->showInList();
            if ($reply->to_id) {
                $tree[$reply->to_id]['children'][] = $data;
            } else {
                $reply->load('user');
                $data['head_img'] = $reply->user->getHeadImg();
                $tree[$reply->id] = array_merge($data, $tree[$reply->id]);
            }
        }
        return array_values($tree);
    }

    // relations

    public function crowdFundings()
    {
        return $this->morphedByMany('CrowdFunding', 'repliable');
    }

    public function products()
    {
        return $this->morphedByMany('Product', 'repliable');
    }

    public function user()
    {
        return $this->belongsTo('User', 'u_id', 'u_id');
    }

    public function userTo()
    {
        return $this->belongsTo('User', 'to_u_id', 'u_id');
    }
}
