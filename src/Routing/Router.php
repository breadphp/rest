<?php

namespace Bread\REST\Routing;

use Bread\Networking\HTTP\Request;
use Bread\Storage\Manager as Storage;
use Bread\Configuration\Manager as Configuration;
use Bread\Networking\HTTP\Response;
use Bread\REST\Controller;
use Bread\Networking\HTTP\Client\Exceptions\NotFound;
use Bread\REST\Routing\URI\Template;
use Bread\Promises\When;

class Router
{
    protected $request;
    protected $response;
    protected $route;
    protected $routingTable;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->routingTable = Route::fetch(array(), array('sort' => array('weight' => 1)), $this->request->headers['host']);
    }

    public function route($uri)
    {
        return $this->routingTable->then(function($routes) use ($uri) {
            foreach ($routes as $route) {
                if ($this->match($uri, $route, $parameters)) {
                    return array($route, $parameters);
//                     $controllerClass = $route->controller;
//                     if (!is_subclass_of($controllerClass, Controller::class)) {
//                         throw new NotFound($uri);
//                     }
//                     $controller = new $controllerClass($this->request, $this->response, $route);
//                     $controlledResource = $controller->controlledResource($parameters);
//                     $method = $this->inflectMethodName($this->request->method, $route->method);
//                     $callback = array($controller, $method);
//                     return array($callback, $controlledResource, $parameters);
                }
            }
            return When::reject($uri);
        })->then(null, function($uri) {
            throw new NotFound($uri);
        });
    }

    protected function match($uri, Route $route, &$parameters)
    {
        $template = new Template($route->uri);
        $domain = $this->request->headers['host'];
        return $template->match($uri, $parameters) && ($domain === $route->host || is_null($route->host));
    }
}