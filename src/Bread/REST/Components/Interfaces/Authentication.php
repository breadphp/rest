<?php
namespace Bread\REST\Components\Interfaces;

use Bread\REST\Controller;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Promises\Interfaces\Resolver;

interface Authentication
{
    public function __construct(Controller $controller, Request $request, Response $response, $data = null);
    
    public function authenticate(Resolver $resolver);
    
    public function requireAuthentication($realm);
}