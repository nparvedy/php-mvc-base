<?php
namespace Core;

abstract class Controller
{
    protected $view;
    protected $model;
    protected $request;
    protected $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->view = new View();
    }

    protected function render(string $template, array $data = [])
    {
        return $this->view->render($template, $data);
    }

    protected function redirect(string $url)
    {
        $this->response->redirect($url);
    }

    protected function json(array $data)
    {
        $this->response->setHeader('Content-Type', 'application/json');
        echo json_encode($data);
    }
}