<?php
namespace Bread\REST\Components\Authorization;

use Bread\REST;
use Bread\Configuration\Manager as Configuration;

class ACE extends REST\Model
{

    const REF = 0;

    const ALL = 1;

    const AUTHENTICATED = 2;

    const UNAUTHENTICATED = 3;

    const PROPERTY = 4;

    const SELF = 5;
    
    const PRIVILEGE_ALL = 'all';
    
    const PRIVILEGE_READ = 'read';
    
    const PRIVILEGE_WRITE = 'write';
    
    protected $aro;

    protected $grant = [];

    protected $deny = [];

    protected $type;

    protected $properties = [];

    protected $invert = false;

    public function authorize($privilege)
    {
        if (in_array(self::PRIVILEGE_ALL, (array) $this->deny)) {
            return false;
        } elseif (in_array(self::PRIVILEGE_ALL, (array) $this->grant)) {
            return !in_array($privilege, (array) $this->deny);
        } else {
            return in_array($privilege, array_diff((array) $this->grant, (array) $this->deny));
        }
    }
}

Configuration::defaults(ACE::class, array(
    'properties' => array(
        'aro' => array(
            'type' => 'Bread\REST\Behaviors\ARO'
        ),
        'grant' => array(
            'multiple' => true
        ),
        'deny' => array(
            'multiple' => true
        ),
        'properties' => array(
            'multiple' => true
        ),
        'type' => array(
            'type' => 'integer'
        ),
        'invert' => array(
            'type' => 'boolean'
        )
    )
));
