<?php
namespace Bread\REST\Routing\URI\Interfaces;

interface Matcher
{

    public function __construct($template);

    public function match($uri, &$matches = array());
}