<?php
/**
*
*/
class MeNotificationController extends \BaseController
{
    public function listNots()
    {
        $per_page = Input::get('per_page', 30);
        $u_id = Input::get('u_id', 0);
        $token = Input::get('token', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $list = Notification::select('notifications.*', 'notification_reads.is_read')
            ->join('notification_receivers', function ($q) {
                $q->on('notifications.n_id', '=', 'notification_receivers.n_id');
            })->leftJoin('notification_reads', function ($q) {
                $q->on('notification_reads.n_id', '=', 'notifications.n_id');
            })->where('notification_reads.u_id', '=', $u_id)
            ->where('notification_reads.is_del', '<>', 1)
            ->where('notifications.n_status', '=', 1)
            ->where('notification_receivers.to_type', '=', '2')
            ->where('notification_receivers.to_id', '=', 0)
            ->orWhere('notification_receivers.to_id', '=', $u_id)
            ->orderBy('notifications.n_id', 'DESC')
            ->paginate($per_page);
            $data = [];
            foreach ($list as $key => $not) {
                $data[] = $not->showInList();
            }
            $re = Tools::reTrue('获取消息成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取消息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getNot($id)
    {
        $u_id = Input::get('u_id', 0);
        $token = Input::get('token', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $not = Notification::find($id);
            if (empty($not)) {
                throw new Exception("没有找到请求的数据", 2001);
            }
            $data = $not->showDetail();
            $read = NotificationRead::where('u_id', '=', $u_id)->where('n_id', '=', $id)->first();
            if (empty($read)) {
                $read = new NotificationRead();
                $read->n_id = $id;
                $read->u_id = $u_id;
                $read->is_read = 1;
                $read->is_del = 0;
            } else {
                $read->is_read = 1;
            }
            $read->save();
            $re = Tools::reTrue('获取消息成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取消息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function countNots()
    {
        $u_id = Input::get('u_id', 0);
        $token = Input::get('token', '');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $list = Notification::join('notification_receivers', function ($q) {
                $q->on('notifications.n_id', '=', 'notification_receivers.n_id');
            })->leftJoin('notification_reads', function ($q) {
                $q->on('notification_reads.n_id', '=', 'notifications.n_id');
            })->where('notification_reads.u_id', '=', $u_id)
            ->where('notifications.n_status', '=', 1)
            ->where('notification_receivers.to_type', '=', '2')
            ->where('notification_receivers.to_id', '=', 0)
            ->orWhere('notification_receivers.to_id', '=', $u_id)
            ->havingRaw('(`t_notification_reads`.`is_read` <> 1 AND `t_notification_reads`.`is_del` <> 1) OR (`t_notification_reads`.`is_read` IS NULL AND `t_notification_reads`.`is_del` IS NULL )')->get();
            $count = count($list);
            $data = ['count' => $count];
            $re = Tools::reTrue('获取消息成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取消息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }
}
