<?php
namespace Bread\REST\Exceptions;

use Exception;

class RequiredAttribute extends Exception
{

    public $class;

    public $attribute;

    public function __construct($class, $property)
    {
        $this->class = $class;
        $this->property = $property;
        parent::__construct(sprintf("%s attribute %s is required", $class, $property));
    }
}
