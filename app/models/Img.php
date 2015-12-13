<?php
/**
*
*/
class Img
{

    public $id;
    public $category;
    
    public function __construct($cate, $id)
    {
        $this->category = $cate;
        $this->id = $id;        // token infact
    }

    public function save($id)
    {
        $oss = new AliyunOss($this->category, $this->id, $id);
        $re = $oss->save();
        $imgs = Img::attachHost($re);
        return $imgs;
    }

    public function move($id, $from, $target)
    {
        $oss = new AliyunOss($this->category, '', $id);
        if ($oss->exsits($from)) {
            $oss->move($from, $target);
        }
        return true;
    }

    public function replace($id, $key)
    {
        $oss = new AliyunOss($this->category, $this->id, $id);
        $img = $oss->replace($key);
        $img = Img::attachHost($img);
        return $img;
    }

    public function remove($id, $obj)
    {
        $oss = new AliyunOss($this->category, '', $id);
        if ($oss->exsits($obj)) {
            $oss->remove($obj);
        }
        return true;
    }

    public function getSavedImg($newId, $string = '', $array = false)
    {
        $imgs = $this->save($newId);
        
        if ($string) {
            $o = explode(',', $string);
            $oldImgs = Img::attachKey($o);
            if (!empty($imgs)) {
                foreach ($imgs as $key => $i) {
                    if (array_key_exists($key, $oldImgs)) {
                        unset($oldImgs[$key]);
                    }
                }
            }
            if (!empty($oldImgs)) {
                foreach ($oldImgs as $key => $img) {
                    $imgs[$key] = $img;
                }
            }
        }

        if ($array) {
            return $imgs;
        } else {
            return implode(',', $imgs);
        }
    }

    public function getList($id)
    {
        $oss = new AliyunOss($this->category, '', $id);
        $re = $oss->getList();
        $imgs = Img::attachHost($re);
        return $imgs;
    }

    public function reindexImg($id, $index, $origin)
    {
        $origin = Img::trimImgHost($origin);
        $file_name = Img::getFileName($origin);
        $key = Img::getKey($file_name);
        $length = strlen($key);
        $new_key = substr($key, 0, $length-1);
        $new_key = $new_key.$index;
        $pos = strpos($origin, $key);
        $new_path = substr_replace($origin, $new_key, $pos, $length);
        $this->move($id, $origin, $new_path);
        return $new_path;
    }

    public function clearFolder($obj, $id)
    {
        $file_name = Img::getFileName($obj);
        $key = Img::getKey($file_name);
        $imgs = $this->getList($id);
        if (empty($imgs[$key])) {
            return true;
        } else {
            $obj = Img::trimImgHost($imgs[$key]);
            $this->remove($obj);
        }
        return false;
    }

    public static function getKey($filename)
    {
        if (!$tmp = explode('.', $filename)) {
            return false;
        }

        return $tmp[0];
    }

    public static function getFileName($url)
    {
        if (strpos($url, '/') === false) {
            return $url;
        }

        $tmp = explode('/', $url);
        $filename = array_pop($tmp);
        return $filename;
    }

    public static function toArray($string, $return_null = false)
    {
        $crud = explode(',', $string);
        if (empty($crud)) {
            if ($return_null) {
                return null;
            }
            return [];
        }
        $array = Img::attachKey($crud);
        if (empty($array)) {
            if ($return_null) {
                return null;
            }
            return [];
        }
        return $array;
    }

    public static function attachKey($crud = [])
    {
        if (empty($crud)) {
            return [];
        }

        $array = [];
        foreach ($crud as $img) {
            $name = Img::getFileName($img);
            $key = Img::getKey($name);
            if (!$key) {
                continue;
            }
            $array[$key] = trim($img);
        }

        return $array;
    }

    public static function filterKey($needle, $array = [], $with_key = false)
    {
        $re = [];
        if (empty($array)) {
            return [];
        }
        foreach ($array as $key => $img) {
            if (strpos($key, $needle) !== false) {
                if ($with_key) {
                    $re[$key] = $img;
                } else {
                    $re[] = $img;
                }
            }
        }
        return $re;
    }

    public static function trimImgHost($path)
    {
        $host = Config::get('app.imghost');
        $length = strlen($host);
        $obj = substr($path, $length);
        return $obj;
    }

    public static function attachHost($crud = [])
    {
        if (empty($crud)) {
            return [];
        }
        $host = Config::get('app.imghost');
        if (is_array($crud)) {
            $array = [];
            foreach ($crud as $key => $img) {
                $array[$key] = $host.$img;
            }
            return $array;
        } else {
            return $host.$crud;
        }
    }
}
