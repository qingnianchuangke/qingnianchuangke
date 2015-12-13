<?php

use \Illuminate\Support\Collection;
use \Illuminate\Filesystem\Filesystem;

class HomeController extends BaseController {
    
    public function v1()
    {
    }

    public function index()
    {
        return View::make('blade.index.index');
    }

    public function banner1()
    {
        $type = Input::get('share_type', '');
        $download_link = 'http://www.54qnck.com/qnck/download.html';
        return View::make('blade.index.banner1')->with('link', $download_link)->with('share_type', $type);
    }

    public function banner2()
    {
        return View::make('blade.index.banner2');
    }

    public function about()
    {
        echo "ABOUT US";
    }

    public function test()
    {
    }
}
