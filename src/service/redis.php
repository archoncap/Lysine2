<?php
namespace Lysine\Service;

if (!extension_loaded('redis'))
    throw new \Lysine\Service\RuntimeError('Require redis extension!');

class Redis implements \Lysine\Service\IService {
    protected $config = array(
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 0,
        'prefix' => null,
        'persistent_id' => '',
        'unix_socket' => '',        // eg: /tmp/redis.sock
        'password' => '',
        'database' => 0,    // dbindex, the database number to switch to
    );

    protected $handler;

    public function __construct(array $config = array()) {
        if ($config)
            $this->config = array_merge($this->config, $config);
    }

    public function __destruct() {
        if (!$this->isPersistent())
            $this->disconnect();
    }

    public function __call($fn, array $args) {
        return $args
             ? call_user_func_array(array($this->connect(), $fn), $args)
             : $this->connect()->$fn();
    }

    public function connect() {
        if ($this->handler)
            return $this->handler;

        $config = $this->config;
        $handler = new \Redis;

        // 优先使用unix socket
        $conn_args = $config['unix_socket']
                   ? array($config['unix_socket'])
                   : array($config['host'], $config['port'], $config['timeout']);

        if ($this->isPersistent()) {
            $conn_args[] = $config['persistent_id'];
            $conn = call_user_func_array(array($handler, 'pconnect'), $conn_args);
        } else {
            $conn = call_user_func_array(array($handler, 'connect'), $conn_args);
        }

        if (!$conn)
            throw new \Lysine\Service\ConnectionError('Cannot connect redis');

        if ($config['password'] && !$handler->auth($config['password']))
            throw new \Lysine\Service\ConnectionError('Invalid redis password');

        if ($config['database'] && !$handler->select($config['database']))
            throw new \Lysine\Service\ConnectionError('Select redis database['.$config['database'].'] failed');

        if (isset($config['prefix']))
            $handler->setOption(\Redis::OPT_PREFIX, $config['prefix']);

        return $this->handler = $handler;
    }

    public function disconnect() {
        if ($this->handler instanceof \Redis) {
            $this->handler->close();
            $this->handler = null;
        }

        return $this;
    }

    protected function isPersistent() {
        $config = $this->config;
        return $config['persistent_id'] && !$config['unix_socket'];
    }
}
