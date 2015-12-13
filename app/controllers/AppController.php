<?php
/**
*
*/
class AppController extends \BaseController
{
    public function getConfig()
    {
        $base_url = Config::get('app.url');
        $init['app_ver'] = Config::get('app.app.android.ver');
        $init['api_ver'] = 'v0';
        $init['force_upgrade'] = 1;
        $init['init_img'] = '';
        $init['share_link'] = $base_url.'banner/1';
        $init['app_download_link'] = 'http://qnck001.oss-cn-hangzhou.aliyuncs.com/app/android/'.Config::get('app.app.android.file_name');
        $init['desc'] = '检测到新版本';

        $data['init'] = $init;
        $data['other'] = null;
        $re = Tools::reTrue('请求成功', $data);
        return Response::json($re);
    }

    public function postFeedback()
    {
        $u_id = Input::get('u_id', 0);
        $comment = Input::get('comment', '');
        $app_ver = Input::get('app_ver', '');

        try {
            $feedback = new AppFeedback();
            $feedback->u_id = $u_id;
            $feedback->comment = $comment;
            $feedback->app_ver = $app_ver;
            $feedback->addFeedback();
            $re = Tools::reTrue('提交成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '提交失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getDownloadLink()
    {
        $name = Config::get('app.app.android.file_name');
        header('Content-Type: application/vnd.android.package-archive');
        header('Content-Disposition:attachment;filename="qnck.apk"');
        header('Content-Length: 4000000');
        readfile('http://qnck001.oss-cn-hangzhou-internal.aliyuncs.com/app/android/'.$name);
        exit();
    }
}
