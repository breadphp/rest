<?php
namespace Bread\REST\Components\Interfaces;

use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Promises\Interfaces\Resolver;
use Bread\REST\Routing\Firewall;

interface Authentication
{
    public function __construct(Firewall $controller, Request $request, Response $response, $data = null);
    
    public function authenticate(Resolver $resolver);
    
    public function requireAuthentication($realm);
}