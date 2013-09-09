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
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Networking\HTTP\Exception;
use Bread\Networking\HTTP\Server\Exceptions\NotImplemented;
use Bread\Networking\HTTP\Client\Exceptions\MethodNotAllowed;
use Bread\REST\Routing\Route;

abstract class Controller implements RFC2616
{
    protected $request;
    protected $response;
    
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
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
        return json_encode($resource, JSON_PRETTY_PRINT);
    }
    
    public function head($resource)
    {
        $this->response->once('headers', array($this->response, 'end'));
        return $this->get($parameters);
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
        throw new NotImplemented($this->request->method);
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
    
    public static function controlledResource(array $parameters = array()) {
        throw new NotImplemented();
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