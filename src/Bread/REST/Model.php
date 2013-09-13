<?php
namespace Bread\REST;

use Bread\Configuration\Manager as Configuration;
use Bread\Storage\Manager as Storage;
use Bread\REST\Exceptions\RequiredAttribute;
use Bread\Model\Exceptions\InvalidAttribute;
use Bread\Caching\Cache;
use Bread\Promises\When;
use JsonSerializable;
use Iterator;
use Bread\Helpers\JSON;

abstract class Model implements JsonSerializable
{

    public function __construct(array $properties = array())
    {
        foreach ($properties as $property => $value) {
            $this->__set($property, $value);
        }
    }

    public function __set($property, $value)
    {
        $class = get_class($this);
        if (Configuration::get($class, "properties.$property.multiple") && (is_array($value) || $value instanceof Iterator)) {
            foreach ($value as &$v) {
                $this->validate($property, $v);
            }
            $this->$property = $value;
        } else {
            $this->validate($property, $value);
            $this->$property = $value;
        }
    }

    public function __get($property)
    {
        return $this->$property;
    }

    public function __isset($property)
    {
        return isset($this->$property);
    }

    public function __unset($property)
    {
        $this->validate($property, $null = null);
        unset($this->$property);
    }

    public function __toString()
    {
        return (string) $this->jsonSerialize();
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    public function validate($property, $value)
    {
        $class = get_class($this);
        $required = Configuration::get($class, "properties.$property.required") ? true : false;
        if ($value === null || $value === array() || (!is_object($value) && !is_array($value) && trim($value) === '')) {
            if ($required) {
                throw new RequiredAttribute($class, $property);
            }
            return true;
        }
        switch ($type = Configuration::get($class, "properties.$property.type")) {
            case 'boolean':
                $value = !!$value;
                break;
            case 'integer':
                if (!is_int($value)) {
                    throw new InvalidAttribute($class, $property, $value);
                }
                break;
            case 'float':
                if (!is_float($value)) {
                    throw new InvalidAttribute($class, $property, $value);
                }
                break;
            default:
                if (class_exists($type)) {
                    if ($value instanceof $type) {
                        return true;
                    }
                    throw new InvalidAttribute($class, $property, $class);
                }
        }
        return true;
    }

    public function store()
    {
        $computed = array();
        $class = get_class($this);
        foreach (Configuration::get($class, "properties") as $keyProperty => $options) {
            if ($function = Configuration::get($class, "properties.$keyProperty.computed")) {
                $computed[$keyProperty] = call_user_func($function, $this);
            }
        }
        return When::all($computed, function ($computed) use ($class) {
            foreach ($computed as $property => $value) {
                $this->$property = $value;
            }
            return Storage::driver($class)->store($this);
        });
    }

    public function delete()
    {
        $class = get_class($this);
        return Storage::driver($class)->delete($this);
    }
    
    public static function fromJSON($json)
    {
        $object = new static;
        foreach(JSON::decode($json) as $property => $value) {
            $object->__set($property, $value);
        }
        return $object;
    }

    public static function count(array $search = array(), array $options = array())
    {
        return static::storage(__FUNCTION__, $search, $options);
    }

    public static function first(array $search = array(), array $options = array())
    {
        return static::storage(__FUNCTION__, $search, $options);
    }

    public static function fetch(array $search = array(), array $options = array())
    {
        return static::storage(__FUNCTION__, $search, $options);
    }

    public static function purge(array $search = array(), array $options = array())
    {
        $class = get_called_class();
        return Storage::driver($class)->purge($class, $search, $options);
    }

    protected static function storage($function, array $search = array(), array $options = array())
    {
        $class = get_called_class();
        return Storage::driver($class)->$function($class, $search, $options);
    }
}
