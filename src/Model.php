<?php
namespace Bread\REST;

use Bread\Configuration\Manager as Configuration;
use Bread\Storage\Manager as Storage;
use Bread\REST\Exceptions\RequiredAttribute;
use Bread\REST\Exceptions\InvalidAttribute;
use Bread\Caching\Cache;
use Bread\Promises\When;
use JsonSerializable;
use Traversable;
use Bread\Helpers\JSON;
use Bread\Promises\Interfaces\Promise;
use Bread\Storage\Reference;

abstract class Model implements JsonSerializable
{

    public function __construct(array $properties = array())
    {
        foreach ($properties as $property => $value) {
            $this->__set($property, $value);
        }
    }

    public function __destruct()
    {
    }

    public function __set($property, $value)
    {
        if ($value instanceof Promise) {
            $value->then(function ($value) use ($property) {
                $this->__set($property, $value);
            });
        } else {
            $class = get_class($this);
            if (Configuration::get($class, "properties.$property.multiple") && (is_array($value) || $value instanceof Traversable)) {
                foreach ($value as &$v) {
                    $this->validate($property, $v);
                }
            } else {
                $this->validate($property, $value);
            }
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
        return json_encode($this);
    }

    public function jsonSerialize()
    {
        $json = array();
        foreach (get_object_vars($this) as $property => $value) {
            $json[$property] = $this->__get($property);
        }
        return $json;
    }

    public function validate($property, $value)
    {
        $class = get_class($this);
        $required = Configuration::get($class, "properties.$property.required") ? true : false;
        if (!is_resource($value) && ($value === null || $value === array() || (!is_object($value) && !is_array($value) && trim($value) === ''))) {
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
            case 'text':
                if (!is_string($value)) {
                    throw new InvalidAttribute($class, $property, $value);
                }
                break;
            case 'resource':
                if (!is_resource($value)) {
                    throw new InvalidAttribute($class, $property, $value);
                }
                break;
            default:
                if (class_exists($type)) {
                    if (Reference::is($value, $type)) {
                        return true;
                    } elseif ($value instanceof $type) {
                        return true;
                    }
                    throw new InvalidAttribute($class, $property, $value);
                }
        }
        return true;
    }

    public function store($domain = '__default__')
    {
        $computed = array();
        $class = get_class($this);
        return Storage::driver($class, $domain)->store($this);
    }

    public function delete($domain = '__default__')
    {
        $class = get_class($this);
        return Storage::driver($class, $domain)->delete($this);
    }

    public static function fromJSON($json)
    {
        $object = new static;
        foreach(JSON::decode($json) as $property => $value) {
            $object->__set($property, $value);
        }
        return $object;
    }

    public static function count(array $search = array(), array $options = array(), $domain = '__default__')
    {
        return static::storage(__FUNCTION__, $search, $options, $domain);
    }

    public static function first(array $search = array(), array $options = array(), $domain = '__default__')
    {
        return static::storage(__FUNCTION__, $search, $options, $domain);
    }

    public static function fetch(array $search = array(), array $options = array(), $domain = '__default__', array $properties = array())
    {
        return static::storage(__FUNCTION__, $search, $options, $domain, $properties);
    }

    public static function purge(array $search = array(), array $options = array(), $domain = '__default__')
    {
        $class = get_called_class();
        return Storage::driver($class, $domain)->purge($class, $search, $options);
    }

    public static function getAutoincrement($domain = '__default__')
    {
        $class = get_called_class();
        return (int) Storage::driver($class, $domain)->autoincrement($class);
    }

    protected static function storage($function, array $search = array(), array $options = array(), $domain, array $properties = array())
    {
        $class = get_called_class();
        return Storage::driver($class, $domain)->$function($class, $search, $options, $properties);
    }
}
