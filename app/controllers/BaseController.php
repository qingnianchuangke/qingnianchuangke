<?php

class BaseController extends Controller
{
    protected $u_id = 0;

    public function __construct()
    {
        $u_id = Input::get('u_id', 0);
        if ($u_id) {
            $this->u_id = $u_id;
        }
    }
}
