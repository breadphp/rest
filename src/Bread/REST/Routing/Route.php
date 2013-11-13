<?php
namespace Bread\REST\Routing;

use Bread\Configuration;
use Bread\REST\Model;
use Bread\REST\Routing\URI\Template;
use Bread\REST\Behaviors\ACO;

class Route extends ACO
{

    public $uri;
    
    public $method;
    
    public $host;

    public $controller;
    
    public $weight;
    
    public function __construct($uri, $controller)
    {
        $this->uri = $uri;
        $this->controller = $controller;
    }
    
    public function expand(array $variables = array())
    {
        $template = new Template($this->uri);
        return $template->expand($variables);
    }
}

Configuration\Manager::defaults('Bread\REST\Routing\Route', array(
    'keys' => array(
        'uri',
        'host'
    ),
    'properties' => array(
        'weigth' => array(
            'type' => 'integer'
        )
    )
));