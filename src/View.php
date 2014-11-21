<?php
namespace Bread\REST;

use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\REST\Behaviors\ARO;

abstract class View
{

    protected $request;

    protected $response;
    
    protected $aro;

    public function __construct(Request $request, Response $response, ARO $aro)
    {
        $this->request = $request;
        $this->response = $response;
        $this->aro = $aro;
    }
}