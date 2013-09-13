<?php
namespace Bread\REST\Routing\URI\Expanders;

use Bread\REST\Routing\URI\Interfaces\Expander;

class PECL implements Expander
{
    private $template;
    
    public function __construct($template)
    {
        $this->template = $template;
    }

    public function expand($variables = array())
    {
        return uri_template($this->template, $variables);
    }
}