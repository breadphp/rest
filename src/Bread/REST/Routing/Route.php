<?php
namespace Bread\REST\Routing;

use Bread\Configuration;
use Bread\REST\Model;

class Route extends Model
{

    public $uri;
    
    public $method;
    
    public $host;

    public $controller;
    
    public function __construct($uri, $controller)
    {
        $this->uri = $uri;
        $this->controller = $controller;
    }
}

Configuration\Manager::defaults('Bread\REST\Routing\Route', array(
    'keys' => array(
        'uri',
        'host'
    )
));