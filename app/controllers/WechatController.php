<?php
/**
*
*/
class WechatController extends \BaseController
{
    public function getHengdaUsers()
    {
        $date = new DateTime();
        $date_start = Input::get('start', '');
        $date_end = Input::get('end', '');
        if ($date_start) {
            $date_start = strtotime($date_start);
        }
        if ($date_end) {
            $date_end = strtotime($date_end);
        }
        try {
            $wecht = new WechatApi('hengda');
            $re = $wecht->getFollowUser();
            $re = $wecht->getUsers($re['data']['openid']);
            $data = [];
            foreach ($re as $key => $user) {
                $check = true;
                if ($date_start && ($user['subscribe_time'] < $date_start)) {
                    $check = false;
                }
                if ($date_end && ($user['subscribe_time'] > $date_end)) {
                    $check = false;
                }
                if ($check) {
                    $data[] = $user;
                }
            }
            $re = Tools::reTrue('获取恒大微信用户成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取恒大微信用户失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getSign()
    {
        $noncestr = Input::get('noncestr', '');
        $timestamp = Input::get('timestamp', '');
        $url = Input::get('url', '');

        try {
            $curl = new CurlRequest();
            $request_url = 'http://qnckwx.54qnck.com/GetjsapiInfo.ashx?noncestr='.$noncestr.'&timestamp='.$timestamp.'&url='.urlencode($url);
            $data = $curl->get($request_url);
            $data = $data['content'];
            $re = Tools::reTrue('获取签名成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getMessage(), '获取签名失败:'.$e->getMessage());
        }
        return Response::json($re);
    }
}
