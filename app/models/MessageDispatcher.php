<?php
/**
*
*/
class MessageDispatcher
{
    private $_u_id = 0;
    private $_n_id = 0;
    private $_add_push_message = 0;
    private $_add_notification = 0;

    public $title = '系统消息';
    public $brief = '';
    public $content = '';
    public $url = '';
    public $cate = 0;
    public $cate_id = 0;
    public $icon = '';
    public $type = 2;

    public $phone;

    public function __construct($u_id, $add_pm = 1, $add_not = 1, $add_sms = 0)
    {
        $this->_u_id = $u_id;
        $this->_add_notification = $add_not;
        $this->_add_push_message = $add_pm;
        $this->_add_sms = $add_sms;
    }

    public function setMessage($params)
    {
        empty($params['title']) ?: $this->title = $params['title'];
        empty($params['brief']) ?: $this->brief = $params['brief'];
        empty($params['content']) ?: $this->content = $params['content'];
        empty($params['url']) ?: $this->url = $params['url'];
        empty($params['cate']) ?: $this->cate = $params['cate'];
        empty($params['cate_id']) ?: $this->cate_id = $params['cate_id'];
        empty($params['icon']) ?: $this->icon = $params['icon'];
        empty($params['type']) ?: $this->type = $params['type'];

        empty($params['phone']) ?: $this->phone = $params['phone'];
    }

    public function pushMessage()
    {
        if (!$this->content) {
            throw new Exception("信息不能为空", 2002);
        }
        $msgObj = new PushMessage($this->_u_id);
        if ($this->cate) {
            $is_jump = 1;
        } else {
            $is_jump = 0;
        }
        $msgObj->pushMessage($this->content, $is_jump);
    }

    public function addNotification()
    {
        $not = new Notification();
        $not->n_icon = $this->icon;
        $not->n_title = $this->title;
        $not->n_brief = $this->brief;
        $not->n_content = $this->content;
        $not->n_url = $this->url;
        $not->n_type = $this->type;
        $not->n_cate = $this->cate;
        $not->n_cate_id = $this->cate_id;
        $not->addNot();
        $this->_n_id = $not->n_id;
    }

    public function addNotificationReceiver($to_id, $to_type)
    {
        if (!$this->_n_id) {
            throw new Exception("无法获取 notification", 2002);
        }
        $receiver = new NotificationReceiver();
        $receiver->n_id = $this->_n_id;
        $receiver->to_id = $to_id;
        $receiver->to_type = $to_type;
        $receiver->save();
    }

    public function sendSMS()
    {
        if (!$this->phone) {
            throw new Exception("无法获取手机号码", 2002);
        }
        $phone = new Phone($this->phone);
        $phone->sendText($this->content);
    }

    public function fire()
    {
        if ($this->_add_push_message) {
            $this->pushMessage();
        }
        if ($this->_add_notification) {
            $this->addNotification();
        }
        if ($this->_add_sms) {
            $this->sendSMS();
        }
    }

    public function fireTextToUser($text)
    {
        $params = [
            'brief' => $text,
            'content' => $text,
        ];
        $this->setMessage($params);
        $this->fire();
        $this->addNotificationReceiver($this->_n_id, NotificationReceiver::$RECEIVER_USER);
    }

    public function fireCateToUser($text, $cate, $cate_id)
    {
        $params = [
            'brief' => $text,
            'content' => $text,
            'cate' => $cate,
            'cate_id' => $cate_id,
            'type' => 3
        ];
        $this->setMessage($params);
        $this->fire();
        $this->addNotificationReceiver($this->_u_id, NotificationReceiver::$RECEIVER_USER);
    }
}
