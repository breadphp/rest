<?php
namespace Bread\REST\Interfaces;

interface RFC5789 extends RFC2616
{

    public function patch($resource);
}