<?php
namespace Bread\REST\Components\Authentication;

use Bread\REST\Behaviors\ARO\Unauthenticated;
use Bread\REST\Components\Interfaces\Authentication as AuthenticationInterface;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Networking\HTTP\Client\Exceptions\Unauthorized;
use Bread\Promises\Interfaces\Resolver;
use Bread\Configuration\Manager as Configuration;
use Bread\REST\Routing\Firewall;

abstract class Method implements AuthenticationInterface
{

    protected $controller;

    protected $request;

    protected $response;

    protected $data;

    public function __construct(Firewall $controller, Request $request, Response $response, $data = null)
    {
        $this->controller = $controller;
        $this->request = $request;
        $this->response = $response;
        $this->data = $data;
    }
}