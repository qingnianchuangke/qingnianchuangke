<?php
/**
*
*/
class EventPosition extends Eloquent
{
    public $timestamps = false;
    protected $fillable = ['position'];

    public function showInList()
    {
        return $this->position;
    }
}
