<?php
/**
*
*/
class LogUserInviteCode extends Eloquent
{
    public $timestamps = false;

    public function addLog()
    {
        $now = Tools::getNow();
        $this->created_at = $now;
        return $this->save();
    }
}
