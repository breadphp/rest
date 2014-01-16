<?php
namespace Bread\REST\Components\Authorization;

use Bread\REST;
use Bread\REST\Behaviors\ACO;
use Bread\REST\Behaviors\ARO;
use Bread\REST\Behaviors\ARO\Authenticated;
use Bread\Promises\Interfaces\Promise;
use Bread\Promises\When;
use Bread\Networking\HTTP\Client\Exceptions\Forbidden;
use Bread\Networking\HTTP\Client\Exceptions\Unauthorized;
use Bread\Configuration\Manager as Configuration;

class ACL extends REST\Model
{

    protected $id;

    protected $aco;

    protected $acl = [];

    protected $inherit = [];

    public function authorize(ARO $aro, $privilege = ACE::PRIVILEGE_READ)
    {
        foreach ($this->inherit($this->inherit) as $inherited){
            $this->acl->append($inherited);
        }
        foreach ($this->acl as $ace) {
            $authorized = $ace->authorize($privilege);
            switch ($ace->type) {
                case ACE::REF:
                    if ($aro instanceof Authenticated) {
                        if ($ace->aro->isMember($aro)) {
                            if (!$ace->invert) {
                                return $authorized ? When::resolve($this->aco) : When::reject(new Forbidden());
                            }
                        } else {
                            if ($ace->invert) {
                                return $authorized ? When::resolve($this->aco) : When::reject(new Forbidden());
                            }
                        }
                    } else {
                        if ($ace->invert) {
                            return $authorized && $ace->invert ? When::resolve($this->aco) : When::reject(new Unauthorized());
                        }
                    }
                    break;
                case ACE::ALL:
                    if ($aro instanceof Authenticated) {
                        if (!$ace->invert) {
                            return $authorized ? When::resolve($this->aco) : When::reject(new Forbidden());
                        }
                    } else {
                        if (!$ace->invert) {
                            return $authorized ? When::resolve($this->aco) : When::reject(new Unauthorized());
                        }
                    }
                    break;
                case ACE::AUTHENTICATED:
                    if ($aro instanceof Authenticated) {
                        if (!$ace->invert) {
                            return $authorized ? When::resolve($this->aco) : When::reject(new Forbidden());
                        }
                    } else {
                        if ($ace->invert) {
                            return $authorized ? When::resolve($this->aco) : When::reject(new Unauthorized());
                        }
                    }
                    break;
                case ACE::UNAUTHENTICATED:
                    if ($aro instanceof Authenticated) {
                        if ($ace->invert) {
                            return $authorized && $ace->invert ? When::resolve($this->aco) : When::reject(new Forbidden());
                        }
                    } else {
                        if (!$ace->invert) {
                            return $authorized && !$ace->invert ? When::resolve($this->aco) : When::reject(new Unauthorized());
                        }
                    }
                    break;
                case ACE::PROPERTY:
                    foreach ($ace->properties as $prop) {
                        if (isset($this->aco->$prop) && ($this->aco->$prop instanceof ARO) && $this->aco->$prop->isMember($aro)) {
                            return $authorized && !$ace->invert ? When::resolve($this->aco) : When::reject(new Forbidden());
                        }
                    }
                    break;
                case ACE::SELF:
                    if ($aro instanceof Authenticated) {
                        if ($this->aco->isMember($aro)) {
                            if (!$ace->invert) {
                                return $authorized ? When::resolve($this->aco) : When::reject(new Forbidden());
                            }
                        } else {
                            if ($ace->invert) {
                                return $authorized ? When::resolve($this->aco) : When::reject(new Forbidden());
                            }
                        }
                    } else {
                        if ($ace->invert) {
                            return $authorized && $ace->invert ? When::resolve($this->aco) : When::reject(new Unauthorized());
                        }
                    }
                    break;
            }
        }
        return ($aro instanceof Authenticated) ? When::reject(new Forbidden()) : When::reject(new Unauthorized());
    }

    protected function inherit($acl)
    {
        $acls = array();
        foreach ($acl as $inheritedAcl) {
            if(is_object($inheritedAcl)){
                $inheritedAcl = $inheritedAcl->acl->getArrayCopy();
            }
            $acls = array_merge($this->inherit(isset($inheritedAcl->inherit) ? $inheritedAcl->inherit : array()), $inheritedAcl);
        }
        return $acls;
    }
}

Configuration::defaults(ACL::class, array(
    'keys' => array(
        'id'
    ),
    'properties' => array(
        'id' => array(
            'type' => 'integer',
            'required' => true,
            'indexed' => true,
            'strategy' => 'autoincrement'
        ),
        'aco' => array(
            'type' => 'Bread\REST\Behaviors\ACO'
        ),
        'acl' => array(
            'type' => 'Bread\REST\Components\Authorization\ACE',
            'multiple' => true,
            'cascade' => true
        ),
        'inherit' => array(
            'type' => 'Bread\REST\Components\Authorization\ACL',
            'multiple' => true
        )
    )
));

/*
 * Non tutte le acl sono esprimibili secondo questa struttura
 * Esempio ACL :
 * "accesso a tutti gli utenti autenticati tranne il gruppo specialisti"
 */