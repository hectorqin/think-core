<?php
namespace Think\Session\Driver;

class Redis
{
    /**
     * Wait time (1ms) after first locking attempt. It doubles
     * for every unsuccessful retry until it either reaches
     * MAX_WAIT_TIME or succeeds.
     */
    const MIN_WAIT_TIME = 1000;
    /**
     * Maximum wait time (3000ms) between locking attempts.
     */
    const MAX_WAIT_TIME = 3000000;
    //REDIS的链接对象
    private $redis;
    //REDIS的有效时间
    private $expire;
    //REDIS上次读取值
    private $last_hit;
    //写入锁的生命周期
    private $lock_ttl = 3;
    //锁列表
    private $session_locks = [];
    public function execute()
    {
        session_set_save_handler(
            array(&$this, 'open'),
            array(&$this, 'close'),
            array(&$this, 'read'),
            array(&$this, 'write'),
            array(&$this, 'destroy'),
            array(&$this, 'gc')
        );
    }
    //打开REIS
    public function open($path, $name)
    {
        $this->expire = C('SESSION_EXPIRE') ? C('SESSION_EXPIRE') : ini_get('session.gc_maxlifetime');
        $this->redis  = new \Redis();
        if (C('REDIS_TIMEOUT')) {
            $this->redis->connect(C('REDIS_HOST'), C('REDIS_PORT'), C('REDIS_TIMEOUT'));
        } else {
            $this->redis->connect(C('REDIS_HOST'), C('REDIS_PORT'));
        }
        if (C('REDIS_AUTH')) {
            $this->redis->auth(C('REDIS_AUTH'));
        }
        if (C('REDIS_SESSION_DB')) {
            $this->redis->select(C('REDIS_SESSION_DB'));
        }
        return true;
    }
    //关闭REDIS
    public function close()
    {
        $this->releaseLocks();
        return $this->redis->close();
    }
    //读取数据
    public function read($sessId)
    {
        $this->releaseLocks();
        $sessId         = C('REDIS_SESSION_PREFIX') . $sessId;
        $data           = $this->redis->get($sessId);
        $this->last_hit = $data ? $data : '';
        return $this->last_hit;
    }
    //写入数据
    public function write($sessId, $data)
    {
        $sessId = C('REDIS_SESSION_PREFIX') . $sessId;
        // session没有改变，只更新ttl
        if (md5($data) == md5($this->last_hit)) {
            return $this->redis->expire($sessId, $this->expire);
        }
        // TODO redis 锁有问题
        return $this->redis->set($sessId, $data, $this->expire);
        // session改变了，加锁同步修改以及更新
        if ($this->acquireLockOn($sessId)) {
            $newData = $this->redis->get($sessId);
            // redis 存储的session没有变化，直接更新
            if (md5($newData) == md5($this->last_hit)) {
                $result = $this->redis->set($sessId, $data, $this->expire);
                $this->releaseLocks();
                return $result;
            }
            // TODO redis 存储的session改变了，合并更新  权重？？ 以谁为准？？坑
            // if (C("REDIS_SESSION_AUTO_MERGE")) {
            //     $data = $this->autoMerge($data, $newData);
            // }
            $result = $this->redis->set($sessId, $data, $this->expire);
            $this->releaseLocks();
            return $result;
        } else {
            \Think\Log::record(L("_REDIS_WRITE_LOCK_FAIL_"));
            return false;
        }
    }
    // 合并session信息
    private function autoMerge($data, $newData)
    {
        $serialize_handler = ini_get("session.serialize_handler");
        $serialize_func    = "serialize";
        $unserialize_func  = "unserialize";
        switch ($serialize_handler) {
            case 'php':
                $serialize_func   = array($this, "serialize_php");
                $unserialize_func = array($this, "unserialize_php");
                break;
            case 'phpbinary':
                $serialize_func   = array($this, "serialize_phpbinary");
                $unserialize_func = array($this, "unserialize_phpbinary");
                break;
            case 'php_serialize':
                break;
            default:
                // TODO 没法解析
                return $data;
                break;
        }
        $prev    = call_user_func_array($unserialize_func, [$this->last_hit]);
        $data    = call_user_func_array($unserialize_func, [$data]);
        $newData = call_user_func_array($unserialize_func, [$newData]);
        foreach ($data as $key => $value) {
            if (isset($prev[$key]) && $prev[$key] == $value) {
                // 未变化或者以当前修改为准
                continue;
            }
            // 变化了咋办。。。
        }
    }
    public function serialize_php($session_array)
    {
        $session_data = "";
        foreach ($session_array as $key => $value) {
            if (strpos($key, "|")) {
                return "";
            }
            $session_data .= $key . "|" . serialize($value);
        }
        return $session_data;
    }
    public function unserialize_php($session_data)
    {
        $return_data = array();
        $offset      = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), "|")) {
                throw new \Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos     = strpos($session_data, "|", $offset);
            $num     = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data                  = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }
    public function serialize_phpbinary($session_array)
    {
        $session_data = "";
        foreach ($session_array as $key => $value) {
            $len = strlen($key);
            if ($len > 127) {
                continue;
            }
            $session_data .= chr($len) . $key . serialize($value);
        }
        return $session_data;
    }
    public function unserialize_phpbinary($session_data)
    {
        $return_data = array();
        $offset      = 0;
        while ($offset < strlen($session_data)) {
            $num = ord($session_data[$offset]);
            $offset += 1;
            $varname = substr($session_data, $offset, $num);
            $offset += $num;
            $data                  = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }
    //销毁REDIS
    public function destroy($sessId)
    {
        $sessId = C('REDIS_SESSION_PREFIX') . $sessId;
        return $this->redis->delete($sessId);
    }
    //GC
    public function gc($maxLifetime)
    {
        return true;
    }
    // 获取写入session的锁
    private function acquireLockOn($sessId)
    {
        $options = ['nx'];
        if (0 < $this->lock_ttl) {
            $options = ['nx', 'ex' => $this->lock_ttl];
        }
        $wait = self::MIN_WAIT_TIME;
        while (true) {
            if ($this->redis->set("{$sessId}_lock", '', $options)) {
                return true;
            }
            usleep($wait);
            if (self::MAX_WAIT_TIME > $wait) {
                $wait *= 2;
            } else {
                return false;
            }
        }
        $this->session_locks[] = $sessId;
    }
    private function releaseLocks()
    {
        foreach ($this->session_locks as $sessId) {
            $this->redis->delete("{$sessId}_lock");
        }
        $this->session_locks = [];
    }
}
