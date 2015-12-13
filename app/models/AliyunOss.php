<?php
/**
*
*/
class AliyunOss
{
    private $_oss = null;
    private $_token = '';
    private $_cate = '';
    private $_id = '';
    private $_bucket = '';

    public function __construct($cate, $token = '', $id = '')
    {
        $this->_bucket = Config::get('app.imgbucket');
        $this->_cate = $cate;
        $basePath = base_path();
        require_once($basePath."/vendor/aliyunoss/alioss.class.php");
        require_once($basePath."/vendor/aliyunoss/thirdparty/xml2array.class.php");
        $this->_oss = new ALIOSS();
        $this->_token = $token;
        $this->_id = $id;
    }

    public function setToken($token)
    {
        $this->_token = $token;
    }

    public function setId($id)
    {
        $this->_id = $id;
    }

    public function setBucket($bucket)
    {
        $this->_bucket = $bucket;
    }

    public function upload()
    {
        $folder = $this->_cate;
        if (!$this->_token) {
            throw new Exception("需要传入token", 20001);
        } elseif (!$this->_cate) {
            throw new Exception("需要传入cate", 20001);
        } else {
            if (!isset($_FILES)) {
                throw new Exception("没有文件传入", 20001);
            } else {
                foreach ($_FILES as $key => $file) {
                    if ($file['name'] && $file['error'] == 0 && $file['size'] > 0) {
                        // todo enhance with mime type
                        $rnd = Tools::getTimeString(6);
                        $obj = '_tmp/'.$this->_cate.'/'.$this->_token.'/'.$key.'.'.$rnd.'.'.$this->getExt($file);
                        $re = $this->_oss->upload_file_by_file($this->_bucket, $obj, $file['tmp_name']);
                        if (!$re->isOK()) {
                            throw new Exception("图片:".$file['name'].'上传失败', 20001);
                            break;
                        }
                    }
                }
            }
        }
        $re = $this->scan('_tmp/'.$this->_cate.'/'.$this->_token.'/');
        $re = Img::attachHost($re);
        return $re;
    }

    public function save()
    {
        $dir = $this->_cate.'/'.$this->_id.'/';
        $tmp_dir = '_tmp/'.$this->_cate.'/'.$this->_token.'/';

        $o = $this->scan($tmp_dir);
        $origin = Img::attachKey($o);

        $t = $this->scan($dir);
        $target = Img::attachKey($t);

        $delete = array_intersect_key($target, $origin);

        foreach ($delete as $key => $value) {
            $this->remove($value);
        }
        $return = [];
        foreach ($origin as $key => $value) {
            $name = Img::getFileName($value);
            $this->move($value, $dir.$name);
            $return[$key] = $dir.$name;
        }
        return $return;
    }

    public function getList()
    {
        $dir = $this->_cate.'/'.$this->_id.'/';
        $re = $this->scan($dir);
        $imgs = Img::attachKey($re);
        return $imgs;
    }

    public function getTmpList()
    {
        $dir = '_tmp/'.$this->_cate.'/'.$this->_token.'/';
        $re = $this->scan($dir);
        $imgs = Img::attachKey($re);
        return $imgs;
    }

    public function scan($path)
    {
        $options = [
            'delimiter' => '/',
            'prefix' => $path
        ];
        $response = $this->_oss->list_object($this->_bucket, $options);
        if (!$response->isOK()) {
            throw new Exception("获取图片列表失败", 20001);
            
        }
        $response = $this->getResponseBodyArray($response);
        if (empty($response['ListBucketResult']['Contents'])) {
            return [];
        }
        $files = $response['ListBucketResult']['Contents'];
        if (empty($files['Key'])) {
            $re = [];
            foreach ($files as $key => $file) {
                $re[] = $file['Key'];
            }
        } else {
            $re[] = $files['Key'];
        }
        return $re;
    }

    public function checkDir()
    {
        $dir = '_tmp/'.$this->_cate;
        $this->makeDir($dir);
        $dir = $this->_cate;
        $this->makeDir($dir);
    }

    public function remove($obj)
    {
        $re = $this->_oss->delete_object($this->_bucket, $obj);
        if (!$re->isOK()) {
            throw new Exception("图片删除失败", 20001);
        }
    }

    public function move($from, $target)
    {
        $re = $this->_oss->copy_object($this->_bucket, $from, $this->_bucket, $target);
        if (!$re->isOK()) {
            throw new Exception("移动图片失败", 20001);
        }
    }

    public function makeDir($dir)
    {
        // chk _tmp
        $re = $this->_oss->get_object($this->_bucket, $dir.'/');
        if (!$re->isOK()) {
            $re = $this->_oss->create_object_dir($this->_bucket, $dir);
            if (!$re->isOK()) {
                throw new Exception("文件夹创建失败", 20001);
            }
        }
        return true;
    }

    public function replace($name)
    {
        $tmp_imgs = $this->getTmpList();
        $imgs = $this->getList();

        // delete
        if (array_key_exists($name, $imgs)) {
            $this->remove($imgs[$name]);
        }
        // move
        if (array_key_exists($name, $tmp_imgs)) {
            $obj = Img::getFileName($tmp_imgs[$name]);
            $dir = $this->_cate.'/'.$this->_id.'/';
            $obj = $dir.$obj;
            $this->move($tmp_imgs[$name], $obj);
        } else {
            throw new Exception("没有找到目标文件", 20001);
        }
        return $obj;
    }

    public function getResponseBodyArray($response)
    {
        return $re = XML2Array::createArray($response->body);
    }

    public function exsits($obj)
    {
        $re = $this->_oss->is_object_exist($this->_bucket, $obj);
        if (!$re->isOK()) {
            return false;
        } else {
            return true;
        }
    }

    public function getExt($file)
    {
        if (strpos($file['name'], '.') !== false) {
            $split = explode('.', $file['name']);
            $ext = array_pop($split);
        } else {
            switch ($file['type']) {
                case 'jpeg':
                case 'image/jpeg':
                    $ext = 'jpg';
                    break;
                case 'gif':
                case 'image/gif':
                    $ext = 'gif';
                    break;
                case 'png':
                case 'image/png':
                    $ext = 'png';
                    break;
                case 'zip':
                case 'application/zip':
                    $ext = 'zip';
                    break;
                case 'audio/wav':
                case 'x-wav':
                    $ext = 'wav';
                    break;
                default:
                    $ext = 'jpg';
                    break;
            }
        }
        return $ext;
    }
}
