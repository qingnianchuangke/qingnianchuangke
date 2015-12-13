<?php
/**
*
*/
class NotificationReceiver extends Eloquent
{
    public $timestamps = false;

    public static $RECEIVER_USER = 1;
    public static $RECEIVER_CHANNEL = 2;
}
