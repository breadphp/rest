<?php
/**
 * Bread PHP Framework (http://github.com/saiv/Bread)
 * Copyright 2010-2012, SAIV Development Team <development@saiv.it>
 *
 * Licensed under a Creative Commons Attribution 3.0 Unported License.
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright  Copyright 2010-2012, SAIV Development Team <development@saiv.it>
 * @link       http://github.com/saiv/Bread Bread PHP Framework
 * @package    Bread
 * @since      Bread PHP Framework
 * @license    http://creativecommons.org/licenses/by/3.0/
 */
namespace Bread\REST;

use Bread\REST\Interfaces\RFC2616;
use Bread\REST\Components\Authentication;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Networking\HTTP\Exception;
use Bread\Networking\HTTP\Server\Exceptions\NotImplemented;
use Bread\Networking\HTTP\Client\Exceptions\MethodNotAllowed;
use Bread\REST\Routing\Route;
use Bread\Promises\Deferred;
use Bread\Networking\HTTP\Client\Exceptions\UnsupportedMediaType;
use Bread\Helpers\JSON;
use Bread\Streaming\Bucket;
use Bread\Configuration\Manager as Configuration;

abstract class Controller implements RFC2616
{
    protected $request;
    protected $response;
    protected $route;
    protected $data;
    protected $authenticated;
    protected $authentication;
    
    public function __construct(Request $request, Response $response, Route $route)
    {
        $this->request = $request;
        $this->response = $response;
        $this->route = $route;
        $this->data = new Deferred();
        $this->authenticated = new Deferred();
        $this->authentication = Authentication::factory($this, $request, $response);
        $this->authentication->authenticate($this->authenticated->resolver());
    }
    
    public function __call($method, array $arguments = array())
    {
        throw new NotImplemented($this->request->method);
    }

    public function options($resource)
    {
        switch ($this->request->uri) {
            case '*':
            default:
                $allowedMethods = $this->allowedMethods();
                $allowHeader = implode(',', $allowedMethods);
        }
        $this->response->headers['Allow'] = $allowHeader;
    }
    
    public function get($resource)
    {
        $this->response->type('json');
        return JSON::encode($resource);
    }
    
    public function head($resource)
    {
        $this->response->once('headers', array($this->response, 'end'));
        return $this->get($resource);
    }
    
    public function post($resource)
    {
        throw new NotImplemented($this->request->method);
    }
    
    public function put($resource)
    {
        throw new NotImplemented($this->request->method);
    }
    
    public function delete($resource)
    {
        return $resource->delete()->then(function($deletedResource) {
            return $this->response->status(Response::STATUS_NO_CONTENT);
        });
    }
    
    public function trace($resource)
    {
        $this->response->type('message/http');
        return (string) $this->request;
    }
    
    public function connect($resource)
    {
        throw new MethodNotAllowed(strtoupper(__FUNCTION__));
    }
    
    abstract public function controlledResource(array $parameters = array());
    
    protected function authenticate($realm = null)
    {
        return $this->authenticated->then(null, function () use ($realm) {
            $method = Configuration::get(static::class, 'authentication.method');
            $authenticationClass = Configuration::get(Authentication::class, "methods.$method");
            $authenticationMethod = new $authenticationClass($this, $this->request, $this->response);
            return $authenticationMethod->requireAuthentication($realm);
        });
    }
    
    protected function data()
    {
        switch ($this->request->type) {
            case 'application/json':
                $bucket = new Bucket($this->request);
                $this->request->on('end', function() use ($bucket) {
                    $this->data->resolve(JSON::decode($bucket->contents()));
                });
                break;
            case 'application/x-www-form-urlencoded':
                $bucket = new Bucket($this->request);
                $this->request->on('end', function() use ($bucket) {
                    parse_str($bucket->contents(), $data);
                    $this->data->resolve($data);
                });
                break;
            case 'multipart/form-data':
                $this->request->on('parts', function ($parts) {
                    $this->data->resolve($parts);
                });
                break;
            case 'default':
                throw new UnsupportedMediaType($this->request->type);
        }
        return $this->data;
    }
    
    protected function decodeData($data)
    {
        switch ($this->request->type) {
            case 'application/json':
                return JSON::decode($data);
        }
    }
    
    protected function allowedMethods()
    {
        return array(
            'OPTIONS',
            'GET',
            'HEAD',
            'POST',
            'PUT',
            'DELETE',
            'TRACE'
        );
    }
}