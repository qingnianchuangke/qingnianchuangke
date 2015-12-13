<?php

use \Illuminate\Support\Collection;

class TxtMessage extends Eloquent{

    public $primaryKey = 't_id';

    private $ch = '';
    private $api = '';
    private $sendUrl = '';
    private $apiKey = '';
    private $apiPass = '';
    private $apiId = '';
    private $apiAuth = [];

    const SEND_FAST = 1;
    const SEND_NORMAL = 2;
    const SEND_SLOW = 3;

    public function __construct()
    {
        parent::__construct();
        $config = Config::get('app.txtMsg');
        $this->sendUrl = $config['url'];
        $this->apiKey = $config['key'];
        $this->apiPass = $config['pass'];
        $this->apiId = $config['id'];
    }

    /**
     * validate base information
     * @author Kydz 2015-06-14
     * @return bool
     */
    private function baseValidate()
    {
        $validator = Validator::make(
            ['mobile' => $this->t_mobile, 'content' => $this->t_content],
            ['mobile' => 'required|digits:11', 'content' => 'required']
        );
        if ($validator->fails()) {
            $msg = $validator->messages();
            throw new Exception($msg->first(), 1);
        } else {
            return true;
        }
    }

    /**
     * prepare curl connection
     * @author Kydz 2015-06-14
     * @return n/a
     */
    private function initCurl()
    {
        $this->ch = curl_init();

        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_URL, $this->sendUrl);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        
        $this->apiAuth = ['userid' => $this->apiId, 'account' => $this->apiKey, 'password' => $this->apiPass];
    }
    
    /**
     * execute curl
     * @author Kydz 2015-06-14
     * @return object xml to obj
     */
    private function execCurl()
    {
        $re = curl_exec($this->ch);
        $re = new SimpleXMLElement($re);
        return $re;
    }

    /**
     * set post data
     * @author Kydz 2015-06-14
     * @param  array $data data to be posted
     */
    private function setPostData($data)
    {
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
    }

    /**
     * send text meesage and store it
     * @author Kydz 2015-06-14
     * @return bool
     */
    public function sendMessage()
    {
        $this->baseValidate();
        $this->initCurl();
        $data = new Collection();
        $send = ['mobile' => $this->t_mobile, 'content' => $this->t_content, 'action' => 'send', 'extno' => '', 'sendTime' => ''];
        $data = $data->merge($send)->merge($this->apiAuth);
        $data = $data->toArray();
        $this->setPostData($data);
        $re = $this->execCurl();
        if ($re->returnstatus == 'Success') {
            $this->save();
            return true;
        } else {
            return false;
            // if (is_object($re)) {
            //     throw new Exception($re->message, 1);
            // } else {
            //     throw new Exception("短信发送失败", 1);
            // }
        }
    }
}
