<?php
namespace Bread\REST\Routing\URI;

use Bread\REST\Routing\URI\Interfaces;
use Bread\REST\Routing\URI\Expanders;
use Bread\REST\Routing\URI\Matchers;

class Template implements Interfaces\Template
{

    private $template;

    private $expander;

    private $matcher;

    public function __construct($template)
    {
        $this->template = $template;
        if (extension_loaded('uri_template')) {
            $this->expander = new Expanders\PECL($this->template);
        } else {
            $this->expander = new Expanders\Native($this->template);
        }
        $this->matcher = new Matchers\Native($this->template);
    }

    public function expand($variables = array())
    {
        return $this->expander->expand($variables);
    }

    public function match($uri, &$matches = array())
    {
        return $this->matcher->match($uri, $matches);
    }
}
