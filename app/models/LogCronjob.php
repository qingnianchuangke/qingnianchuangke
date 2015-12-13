<?php
/**
*
*/
class LogCronjob extends Eloquent
{
    public $timestamps = false;

    public static function addLog($command, $message)
    {
        $log = new LogCronjob();
        $log->created_at = Tools::getNow();
        $log->command = $command;
        $log->message = $message;
        $log->save();
    }
}
