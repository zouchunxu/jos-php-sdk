<?php

class JosRequest
{

    private $_apiMethod;

    public $apiParas = array();

    public function __construct($apiMethod = null)
    {
        $this->_apiMethod = $apiMethod;
    }

    public function getApiMethod()
    {
        return $this->_apiMethod;
    }

    public function getAppJsonParams()
    {
        if ($this->apiParas) {
            return json_encode($this->apiParas);
        } else { // 空对象
            return '{}';
        }
    }

    /**
     * 读取文件然后转化成base64编码以符合京东jos文件上传格式
     *
     * @param $file String
     *            示例：@/var/www/img/upload.jpg
     * @return string 转化后的文件base64字符串，或者原传入字符串
     */
    protected function fileHanlder($file)
    {
        if (substr($file, 0, 1) == '@') {
            $file = substr($file, 1);
            $file = base64_encode(file_get_contents($file));
        }
        return $file;
    }

    public function __set($key, $value)
    {
        if ($value === null) {
            unset($this->apiParas[$key]);
            return;
        }
        $this->apiParas[$key] = $value;
    }

    public function __get($key)
    {
        return isset($this->apiParas[$key]) ? $this->apiParas[$key] : null;
    }
}