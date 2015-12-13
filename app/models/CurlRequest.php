<?php
/**
*
*/
class CurlRequest
{
    private $_ch;
    private $_get_config = [
        'Returntransfer' => true,
        'Header' => false,
        'SslVerifypeer' => false,
        // 'Sslversion' => 3,
        ];
    private $_post_config = [
        'Returntransfer' => true,
        'Post' => true,
        'SslVerifypeer' => false,
        // 'Sslversion' => 3,
        ];

    public function __construct()
    {
        $this->_ch = curl_init();
    }

    public function __destruct()
    {
        curl_close($this->_ch);
    }

    /**
     * [setRrl 设置CURL的URL]
     * @param [type] $url [description]
     * @author Kydz 2014.04.03
     */
    private function setRrl($url)
    {
        curl_setopt($this->_ch, CURLOPT_URL, $url);
    }

    /**
     * [setPostfields 设置需要POST的数据]
     * @param [type] $data [description]
     * @author Kydz 2014.04.03
     */
    private function setPostfields($data)
    {
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $data);
    }

    /**
     * [setReturntransfer 设置返回信息为文件留或是直接输出]
     * @param [type] $config [description]
     * @author Kydz 2014.04.03
     */
    private function setReturntransfer($config)
    {
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, $config);
    }

    /**
     * [setHeader 启用时会将头文件的信息作为数据流输出]
     * @param [type] $config [description]
     * @author Kydz 2014.04.03
     */
    private function setHeader($config)
    {
        curl_setopt($this->_ch, CURLOPT_HEADER, $config);
    }

    /**
     * [setSslVerifypeer 禁用后cURL将终止从服务端进行验证]
     * @param [type] $config [description]
     * @author Kydz 2014.04.03
     */
    private function setSslVerifypeer($config)
    {
        curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, $config);
    }

    /**
     * [setPost 设置是否使用POST]
     * @param [type] $config [description]
     * @author Kydz 2014.04.03
     */
    private function setPost($config)
    {
        curl_setopt($this->_ch, CURLOPT_POST, $config);
    }

    /**
     * [setFile 设置file]
     * @param [type] $file [文件句柄]
     */
    private function setFile($file)
    {
        curl_setopt($this->_ch, CURLOPT_FILE, $file);
    }

    private function setSslversion($ver)
    {
        curl_setopt($this->_ch, CURLOPT_SSLVERSION, $ver);
    }

    /**
     * [doExec 执行]
     * @return [type] [description]
     * @author Kydz 2014.04.03
     */
    private function doExec()
    {
        $content = curl_exec($this->_ch);
        $err = curl_errno($this->_ch);
        $errmsg = curl_error($this->_ch);
        $re['content'] = $content;
        $re['err'] = $err;
        $re['errmsg'] = $errmsg;
        return $re;
    }

    /**
     * [setupCurl 配置CURL]
     * @param  [type] $config [需要设置的配置按 '配置名' => '配置值' 的数组传入]
     * @author Kydz 2014.04.03
     * @return [type]         [description]
     */
    private function setupCurl($config)
    {
        foreach ($config as $k => $v) {
            $foo = 'set'.strtolower($k);
            if (method_exists($this, $foo)) {
                $this->{$foo}($v);
            }
        }
    }

    /**
     * [get 模拟get方法]
     * @param  [type] $url [目标URL]
     * @author Kydz 2014.04.03
     * @return [type]      [description]
     */
    public function get($url)
    {
        $this->setRrl($url);
        $this->setupCurl($this->_get_config);
        $re = $this->doExec();
        return $re;
    }

    public function getFile($url, $path)
    {
        $this->setRrl($url);
        $file = fopen($path, 'w');
        $config = [
            'file' => $file,
            'header' => false,
            'sslversion' => 3,
            ];
        $this->setupCurl($config);
        $re = $this->doExec();
        return $re;
    }

    /**
     * [post 模拟POST方法]
     * @param  [type] $url  [目标URL]
     * @param  [type] $data [需要POST的地址]
     * @author Kydz 2014.04.03
     * @return [type]       [description]
     */
    public function post($url, $data)
    {
        $this->setRrl($url);
        $this->setupCurl($this->_post_config);
        $this->setPostfields($data);
        $re = $this->doExec();
        return $re;
    }
}
