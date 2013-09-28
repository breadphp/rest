<?php
namespace Bread\REST\Components\Authentication;

use Bread\REST\Controller;
use Bread\REST\Behaviors\ARO\Unauthenticated;
use Bread\REST\Components\Interfaces\Authentication as AuthenticationInterface;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Networking\HTTP\Client\Exceptions\Unauthorized;
use Bread\Promises\Interfaces\Resolver;
use Bread\Configuration\Manager as Configuration;
use DateTime;

class Token extends Method implements AuthenticationInterface
{

    public function authenticate(Resolver $resolver)
    {
        return Token\Model::first(array(
            'data' => $this->data,
            'expire' => array(
                '$gt' => new DateTime()
            )
        ))->then(function ($token) use ($resolver) {
            return $resolver->resolve($token->aro);
        }, function () use ($resolver) {
            return $resolver->reject(new Unauthenticated());
        });
    }

    public function requireAuthentication($realm)
    {
        throw new Unauthorized();
    }
}