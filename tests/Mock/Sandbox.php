<?php
// 浏览器沙盒，模拟访问

namespace Test\Mock;

class Sandbox {
    use \Lysine\Traits\Singleton;

    protected $path = '/';
    protected $cookie;
    protected $_server;

    protected function __construct() {
        $this->cookie = \Test\Mock\Cookie::getInstance();
        $this->_server = $_SERVER;
    }

    public function request($uri = '/', $method = 'GET', array $params = array()) {
        resp()->reset();
        $_GET = $_POST = $_REQUEST = $_SERVER = $_COOKIE = array();
        $_SESSION = \Lysine\Session::getInstance();

        $uri = parse_url($uri);

        $this->path = $uri['path'];

        $method = strtoupper($method);

        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['REQUEST_METHOD'] = $method;

        if (isset($uri['query'])) {
            $_SERVER['QUERY_STRING'] = $uri['query'];
            parse_str($uri['query'], $_GET);
        }

        if ($method == 'GET') {
            $_GET = $params;
        } else {
            $_POST = $params;
        }

        $this->cookie->apply( $this->path );

        $_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
    }

    public function requestEnd() {
        $this->cookie->apply( $this->path );
    }

    public function refresh() {
        $this->requestEnd();
    }

    public function reset() {
        $this->path = '/';
        $this->cookie->reset();

        $_GET = $_POST = $_REQUEST = $_SESSION = $_COOKIE = array();
        $_SERVER = $this->_server;
    }

    public function useAjax() {
        $this->setHeader('X-REQUESTED-WITH', 'xmlhttprequest');
    }

    public function setHeader($key, $val) {
        $key = strtoupper('http_' . str_replace('-', '_', $key));
        $_SERVER[$key] = $val;
    }
}
