<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think;

/**
 * 日志处理类
 * @method static emerg($message, $level = self::INFO, $record = false) 记录严重错误: 导致系统崩溃无法使用
 * @method static alert($message, $level = self::INFO, $record = false) 记录警戒性错误: 必须被立即修改的错误
 * @method static crit($message, $level = self::INFO, $record = false) 记录临界值错误: 超过临界值的错误，例如一天24小时，而输入的是25小时这样
 * @method static err($message, $level = self::INFO, $record = false) 记录一般错误: 一般性错误
 * @method static warn($message, $level = self::INFO, $record = false) 记录警告性错误: 需要发出警告的错误
 * @method static notic($message, $level = self::INFO, $record = false) 记录通知: 程序可以运行但是还不够完美的错误
 * @method static info($message, $level = self::INFO, $record = false) 记录信息: 程序输出信息
 * @method static debug($message, $level = self::INFO, $record = false) 记录调试: 调试信息
 * @method static sql($message, $level = self::INFO, $record = false) 记录SQL：SQL语句 注意只在调试模式开启时有效
 */
class Log
{

    // 日志级别 从上到下，由低到高
    const EMERG  = 'EMERG'; // 严重错误: 导致系统崩溃无法使用
    const ALERT  = 'ALERT'; // 警戒性错误: 必须被立即修改的错误
    const CRIT   = 'CRIT'; // 临界值错误: 超过临界值的错误，例如一天24小时，而输入的是25小时这样
    const ERR    = 'ERR'; // 一般错误: 一般性错误
    const WARN   = 'WARN'; // 警告性错误: 需要发出警告的错误
    const NOTICE = 'NOTICE'; // 通知: 程序可以运行但是还不够完美的错误
    const INFO   = 'INFO'; // 信息: 程序输出信息
    const DEBUG  = 'DEBUG'; // 调试: 调试信息
    const SQL    = 'SQL'; // SQL：SQL语句 注意只在调试模式开启时有效

    // 日志信息
    protected static $log = array();
    // append log
    protected static $append = array();

    // 日志存储
    protected static $storage = null;

    // 日志初始化
    public static function init($config = array())
    {
        $type  = isset($config['type']) ? $config['type'] : 'File';
        $class = strpos($type, '\\') ? $type : 'Think\\Log\\Driver\\' . ucwords(strtolower($type));
        unset($config['type']);
        self::$storage = new $class($config);
    }

    /**
     * 记录日志 并且会过滤未经设置的级别
     * @static
     * @access public
     * @param mixed $message 日志信息
     * @param string $level  日志级别
     * @param boolean $record  是否强制记录
     * @return void
     */
    public static function record($message, $level = self::INFO, $record = false)
    {
        if ($record || false !== strpos(C('LOG_LEVEL'), $level)) {
            if (function_exists('slog')) {
                slog($message, $level);
            }

            if (!is_string($message)) {
                $message = defined('APP_STATUS') && \APP_STATUS === 'debug' ? \var_export($message, true) : \json_encode($message, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
            }

            self::$log[] = "{$level}: {$message}\r\n";
        }
    }

    /**
     * 头部插入日志
     * @static
     * @access public
     * @param mixed $message 日志
     * @param string $level 级别
     * @return void
     */
    public static function append($message, $level = self::INFO)
    {
        if (function_exists('slog')) {
            slog($message, 'log');
        }

        $now = date('[ Y-m-d H:i:s ]');
        if (!is_string($message)) {
            $message = defined('APP_STATUS') && \APP_STATUS === 'debug' ? \var_export($message, true) : \json_encode($message, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
        }

        self::$append[] = "{$now} {$level}: {$message}\r\n";
    }

    /**
     * 日志保存
     * @static
     * @access public
     * @param integer $type 日志记录方式
     * @param string $destination  写入目标
     * @return void
     */
    public static function save($type = '', $destination = '')
    {
        if (empty(self::$log) && empty(self::$append)) {
            return;
        }

        if (empty($destination)) {
            $destination = C('LOG_PATH') . date('y_m_d') . '.log';
        }
        if (!self::$storage) {
            $type          = $type ?: C('LOG_TYPE');
            $class         = 'Think\\Log\\Driver\\' . ucwords($type);
            self::$storage = new $class();
        }
        $message = implode('', self::$append) . implode('', self::$log);
        self::$storage->write($message, $destination);
        // 保存后清空日志缓存
        self::$log = array();
    }

    /**
     * 日志直接写入
     * @static
     * @access public
     * @param string $message 日志信息
     * @param string $level  日志级别
     * @param integer $type 日志记录方式
     * @param string $destination  写入目标
     * @return void
     */
    public static function write($message, $level = self::INFO, $type = '', $destination = '')
    {
        if (!self::$storage) {
            $type               = $type ?: C('LOG_TYPE');
            $class              = 'Think\\Log\\Driver\\' . ucwords($type);
            $config['log_path'] = C('LOG_PATH');
            self::$storage      = new $class($config);
        }
        if (empty($destination)) {
            $destination = C('LOG_PATH') . date('y_m_d') . '.log';
        }
        self::$storage->write("{$level}: {$message}", $destination);
    }

    /**
     * 方便各种级别日志调用
     * @param string $name 方法名
     * @param array $arguments 参数
     * @return void
     */
    public static function __callStatic($name, $arguments)
    {
        if (\in_array(strtoupper($name), [
            self::EMERG,
            self::ALERT,
            self::CRIT,
            self::ERR,
            self::WARN,
            self::NOTICE,
            self::INFO,
            self::DEBUG,
            self::SQL,
        ])) {
            if (count($arguments) > 1) {
                $arguments[1] = strtoupper($name);
            } else {
                $arguments[] = strtoupper($name);
            }
            return self::record(...$arguments);
        } else {
            return self::record("Call to undefined method Log::{$name}", self::ERR);
        }
    }
}
