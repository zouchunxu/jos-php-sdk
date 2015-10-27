<?php
namespace Zycx\Jd\Jos\Stream;

/**
 * http://jos.jd.com/doc/channel.htm?id=218
 */
class Connection
{

    protected $appKey;

    protected $appSecret;

    protected $rid;

    protected $runtimeDir;

    protected $pub;
    //
    public $debug = true;
    
    // 一般不需要修改
    public $serverHost = 'comet.jd.com';

    public $serverPort = 80;

    public $serverPath = '/stream';

    public $serverTimeout = 90;
    // 服务器30s发送一次心跳包
    //
    protected $fpLock;
    // 程序pid
    protected $fpLockTTL;
    // pid生存期限
    protected $streamTTL = 86400;
    // jd长连接生存周期
    public function __construct($appKey, $appSecret, Pub $pub, $rid = null)
    {
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->rid = $rid;
        $this->pub = $pub;
    }

    /**
     * 程序入口
     */
    public function run()
    {
        if (! $this->lock()) {
            $this->output('加锁失败,请检测是否有其他实例在运行');
            return false;
        }
        return $this->conn();
    }

    protected function conn()
    {
        $body = $this->getAuthBody();
        
        $fp = @fsockopen($this->serverHost, $this->serverPort, $errno, $errstr, 30);
        if (! $fp) {
            $this->output('连接失败：' . $errno . $errstr);
            return false;
        }
        
        stream_set_timeout($fp, $this->serverTimeout);
        
        $contentLength = strlen($body);
        
        $in = "POST {$this->serverPath} HTTP/1.1\r\n";
        $in .= "Host: {$this->serverHost}\r\n";
        $in .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $in .= "Content-Length: {$contentLength}\r\n";
        $in .= "Connection: Keep-Alive\r\n\r\n";
        $in .= $body;
        
        $len = fwrite($fp, $in);
        
        $this->output($in);
        
        while ($len) {
            
            $line = fgets($fp);
            
            $this->output($line);
            
            $meta = stream_get_meta_data($fp);
            
            if ($meta['timed_out']) {
                $this->output('连接超时');
                break;
            }
            if ($meta['eof']) {
                $this->output('服务器断开连接');
                fclose($fp);
                return;
            }
            if (strlen($line) == 0) {
                $this->output("没有收到任何数据！");
                break;
            }
            if ($this->checkLock()) {
                if ($line[0] == '{') {
                    $code = $this->publish($line);
                    // TODO 根据code进行重连退出等等机制
                }
            } else {
                // 没有锁了依然要运行至下一个实例接管本实体
            }
        }
        // 走出循环说明异常发生，需要重连
        fclose($fp);
        $this->output('异常重连');
        $this->conn();
    }

    /**
     * 设置运行时目录
     *
     * @param unknown $dir            
     */
    public function setRuntimeDir($dir)
    {
        $this->runtimeDir = realpath($dir);
        if (! is_writable($this->runtimeDir)) {
            throw new \Exception(sprintf('%s目录不存在或者不可写', $dir));
        }
    }

    public function getRuntimeDir()
    {
        if (null === $this->runtimeDir) {
            $this->runtimeDir = sys_get_temp_dir();
        }
        if (is_writable($this->runtimeDir)) {
            return $this->runtimeDir;
        }
        throw new \Exception(sprintf('runtime目录不存在或者不可写 %s', $this->runtimeDir));
    }

    /**
     * 得到连接认证信息
     */
    protected function getAuthBody()
    {
        $param['app_key'] = $this->appKey;
        if ($this->rid) {
            $param['user'] = $this->rid; // rid is user id
        }
        $param['id'] = $this->appKey; // what is connection id for?
        
        $param['timestamp'] = time() . '000';
        
        // and other params ,now nothing
        
        ksort($param);
        $sign = $this->appSecret;
        foreach ($param as $key => $value) {
            $sign .= $key . $value;
        }
        $sign = md5($sign . $this->appSecret, true);
        $sign = $this->bin2hex($sign);
        // sign
        $param['sign'] = $sign;
        return http_build_query($param);
    }

    private function bin2hex($string)
    {
        $hex = '';
        for ($i = 0, $l = strlen($string); $i < $l; $i ++) {
            $ord = ord($string[$i]);
            $hexCode = dechex($ord);
            $hex .= substr('0' . $hexCode, - 2);
        }
        return strtoupper($hex);
    }

    /**
     * 输出调试
     */
    protected function output($msg)
    {
        if ($this->debug) {
            echo sprintf("[%s]\n%s\n\n\n" . PHP_EOL, date('Y-m-d H:i:s'), $msg);
        }
    }

    /**
     * 得到当前连接锁文件
     *
     * @return string
     */
    protected function getLockFile()
    {
        $runtime = $this->getRuntimeDir();
        $file = 'jd_stream';
        if ($this->rid) {
            $file .= '_' . $this->rid;
        }
        
        return $runtime . DIRECTORY_SEPARATOR . $file . '.lock';
    }

    /**
     * 加锁，阻止本程序的第二个实例启动运行。
     */
    protected function lock()
    {
        return true;
        $this->fpLock = fopen($this->getLockFile(), 'w+');
        if ($this->fpLock === false) {
            return false;
        }
        if (flock($this->fpLock, LOCK_EX | LOCK_NB)) {
            $this->fpLockTTL = time() + $this->streamTTL - 1800; // 锁生存周期比连接生存周期短一些
            return true;
        }
        
        fclose($this->fpLock);
        $this->fpLock = null;
        return false;
    }

    /**
     * 检测实例锁是否有效或者过期，过期了则允许第二个实例运行来替换自己
     */
    protected function checkLock()
    {
        if (! $this->fpLock) {
            return false;
        }
        
        if (time() > $this->fpLockTTL) { // 进入死亡
            flock($this->fpLock, LOCK_UN);
            fclose($this->fpLock);
            $this->fpLock = null;
            return false;
        }
        return true;
    }

    /**
     * 分发一条消息，返回消息码，程序会根据返回的消息码进行一些操作如重连补发等
     *
     * @param unknown $raw            
     */
    protected function publish($raw)
    {
        $json = json_decode($raw);
        if (json_last_error()) { // packet msg一般返回一个json但没有转义
            $data = trim($raw);
            $start = '{"packet":{"code":202,"msg":';
            $end = '}}';
            $data = $start . substr($data, strlen($start) + 1, - (strlen($end) + 1)) . $end;
            $json = json_decode($data);
            if (json_last_error()) {
                // TODO error
            }
        }
        if (isset($json->packet)) {
            $packet = $json->packet;
            if (isset($packet->code)) {
                $handle = 'onPacket' . $packet->code;
                if (method_exists($this, $handle)) {
                    return $this->$handle($packet->msg);
                }
                return $packet->code;
            }
        }
        // TODO 理论上不应该到达这里
    }

    protected function onPacket101($msg)
    {
        // 101连接到期
    }

    protected function onPacket102($msg)
    {
        // 102积极要求重连
    }

    protected function onPacket103($msg)
    {
        // 103被迫等待重连
    }

    protected function onPacket104($msg)
    {
        // 104连接被替换
        // 虽然当前连接被替换，但仍然可能有些包会发到这个连接，所以等待返回null
    }

    protected function onPacket105($msg)
    {
        // 105服务器器端包堆积造成中断
    }

    protected function onPacket200($msg)
    {
        // connected jos
    }

    protected function onPacket201($msg)
    {
        // 201心跳包
    }

    protected function onPacket203($msg)
    {
        // 203发现丢失包
    }

    protected function onPacket202($msg)
    {
        // 业务包
        $this->pub->publishPacket(json_encode($msg));
    }
}