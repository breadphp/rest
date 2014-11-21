<?php
namespace Bread\REST\Exceptions;

use Exception;

class InvalidAttribute extends Exception
{

    public $class;

    public $attribute;

    public $value;

    public function __construct($class, $attribute, $value)
    {
        $this->class = $class;
        $this->attribute = $attribute;
        $this->value = $value;
        parent::__construct(sprintf("Invalid value %s for %s attribute %s", var_export($value, true), $class, $attribute));
    }
}
