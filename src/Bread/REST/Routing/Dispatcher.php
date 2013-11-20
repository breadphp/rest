<?php

namespace Bread\REST\Routing;

use Bread\Networking\HTTP;
use Bread\Networking\HTTP\Client\Exceptions\NotFound;
use Bread\Networking\HTTP\Client\Exceptions\Unauthorized;
use Exception;

class Dispatcher
{

    public function dispatch(HTTP\Request $request, HTTP\Response $response)
    {
        $router = new Router($request, $response);
        $firewall = new Firewall($request, $response);
        return $router->route($request->uri)->then(function ($result) use ($request, $response, $firewall) {
            list ($route, $parameters) = $result;
            return $firewall->access($route)->then(function ($result) use ($request, $response, $parameters, $firewall) {
                list ($aro, $route) = $result;
                $controllerClass = $route->controller;
                $controller = new $controllerClass($request, $response, $aro, $firewall, $route);
                $method = $this->inflectMethodName($request->method, $route->method);
                $callback = array($controller, $method);
                $resource = $controller->controlledResource($parameters);
                return array($resource, $callback, $parameters);
            });
        })->then(function ($result) use ($request, $response) {
            list ($resource, $callback, $parameters) = $result;
            return $resource->then(function ($resource) use ($callback, $parameters) {
                return call_user_func($callback, $resource, $parameters);
            }, function ($class) use ($request) {
                throw new NotFound($request->uri);
            });
        })->then(function ($output) use ($response) {
            return $response->flush($output);
        }, function (Exception $exception) use ($response, $firewall) {
            if ($exception instanceof HTTP\Exception) {
                $response->status($exception->getCode());
                switch (get_class($exception)) {
                    case Unauthorized::class:
                        $firewall->authenticate('Bread');
                }
            } else {
                $response->status(500);
            }
            return $response->flush($exception->getMessage());
        });
    }
    
    protected function inflectMethodName($method, $suffix = null)
    {
        if ($method === 'OPTIONS') {
            return $method;
        }
        $parts = explode('-', $method);
        $parts = array_map('strtolower', $parts);
        $first = array_shift($parts);
        $parts = array_map('ucfirst', $parts);
        return $first . implode('', $parts) . $suffix;
    }
}
