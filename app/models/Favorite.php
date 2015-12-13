<?php
/**
*
*/
class Favorite extends Eloquent
{
    protected $fillable = ['u_id', 'created_at', 'u_name'];

    public $timestamps = false;

    // relations

    public function crowdFundings()
    {
        return $this->morphedByMany('CrowdFunding', 'favoriable');
    }

    public function users()
    {
        return $this->morphedByMany('User', 'favoriable');
    }

    public function booths()
    {
        return $this->morphedByMany('Booth', 'favoriable');
    }

    public function products()
    {
        return $this->morphedByMany('Product', 'favoriable');
    }
}
