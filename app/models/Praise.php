<?php
/**
*
*/
class Praise extends Eloquent
{
    protected $fillable = ['u_id', 'created_at', 'u_name'];

    public $timestamps = false;

    // relations

    public function crowdFundings()
    {
        return $this->morphedByMany('CrowdFunding', 'praisable');
    }

    public function users()
    {
        return $this->morphedByMany('User', 'praisable');
    }

    public function booths()
    {
        return $this->morphedByMany('Booth', 'praisable');
    }

    public function products()
    {
        return $this->morphedByMany('Product', 'praisable');
    }
}
