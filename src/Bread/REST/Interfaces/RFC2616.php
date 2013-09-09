<?php
namespace Bread\REST\Interfaces;

interface RFC2616
{

    public function options($resource);

    public function get($resource);

    public function head($resource);

    public function post($resource);

    public function put($resource);

    public function delete($resource);

    public function trace($resource);

    public function connect($resource);
}