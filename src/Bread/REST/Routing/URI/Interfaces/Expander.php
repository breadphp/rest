<?php
namespace Bread\REST\Routing\URI\Interfaces;

interface Expander
{

    public function __construct($template);

    public function expand($variables = array());
}