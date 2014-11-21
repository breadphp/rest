<?php
namespace Bread\REST\Components\Authentication\Token;

use Bread\REST;
use Bread\Configuration\Manager as Configuration;
use Bread\REST\Behaviors\ARO\Authenticated;
use DateTime;

class Model extends REST\Model
{

    protected $data;

    protected $aro;

    protected $expire;
}

Configuration::defaults(Model::class, array(
    'keys' => array(
        'data'
    ),
    'properties' => array(
        'aro' => array(
            'type' => Authenticated::class,
            'required' => true
        ),
        'expire' => array(
            'type' => DateTime::class
        )
    )
));