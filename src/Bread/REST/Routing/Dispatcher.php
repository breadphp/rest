<?php

namespace Bread\REST\Routing;

use Bread\Networking\HTTP;
use Exception;
use Bread\Networking\HTTP\Client\Exceptions\NotFound;

class Dispatcher
{

    public function dispatch(HTTP\Request $request, HTTP\Response $response)
    {
        $router = new Router($request, $response);
        return $router->route($request->uri)->then(function ($result) use ($request, $response) {
            // TODO implement firewall
            return $result;
            $firewall = new Firewall();
            return $firewall->access();
        })->then(function ($result) use ($request, $response) {
            list ($callback, $resource, $parameters) = $result;
            return $resource->then(function($resource) use ($callback, $parameters) {
                return call_user_func($callback, $resource, $parameters);
            }, function ($class) {
                throw new NotFound($class);
            });
        })->then(function($output) use ($response) {
            return $response->flush($output);
        }, function(Exception $exception) use ($response) {
            if ($exception instanceof HTTP\Exception) {
                $response->status($exception->getCode());
            }
            else {
                $response->status(500);
            }
            return $response->flush($exception->getMessage());
        });
    }
}
