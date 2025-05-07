<?php
namespace Core;

class Request
{
    private $get;
    private $post;
    private $server;
    private $files;
    private $cookie;
    private $uri;
    private $method;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookie = $_COOKIE;
        $this->uri = $this->parseUri();
        $this->method = $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    private function parseUri()
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $position = strpos($uri, '?');
        
        if ($position !== false) {
            $uri = substr($uri, 0, $position);
        }
        
        return $uri;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->get;
        }
        
        return $this->get[$key] ?? $default;
    }

    public function post($key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        
        return $this->post[$key] ?? $default;
    }

    public function file($key)
    {
        return $this->files[$key] ?? null;
    }

    public function cookie($key, $default = null)
    {
        return $this->cookie[$key] ?? $default;
    }

    public function isGet()
    {
        return $this->method === 'GET';
    }

    public function isPost()
    {
        return $this->method === 'POST';
    }

    public function isPut()
    {
        return $this->method === 'PUT';
    }

    public function isDelete()
    {
        return $this->method === 'DELETE';
    }

    public function isAjax()
    {
        return isset($this->server['HTTP_X_REQUESTED_WITH']) && 
               strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}