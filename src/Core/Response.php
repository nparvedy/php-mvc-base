<?php
namespace Core;

class Response
{
    private $statusCode = 200;
    private $headers = [];

    public function setStatusCode($code)
    {
        $this->statusCode = $code;
        http_response_code($code);
        
        return $this;
    }

    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        header("{$name}: {$value}");
        
        return $this;
    }

    public function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }

    public function json($data, $statusCode = null)
    {
        if ($statusCode !== null) {
            $this->setStatusCode($statusCode);
        }
        
        $this->setHeader('Content-Type', 'application/json');
        echo json_encode($data);
        exit;
    }

    public function output($content, $statusCode = null)
    {
        if ($statusCode !== null) {
            $this->setStatusCode($statusCode);
        }
        
        echo $content;
        exit;
    }
}