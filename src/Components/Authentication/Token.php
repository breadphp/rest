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
        $domain = $this->request->headers['host'];
        return Token\Model::first(array(
            'data' => $this->data,
            '$or' => array(
                array('expire' => array('$gt' => new DateTime())),
                array('expire' => null)
            )
        ), array(), $domain)->then(function ($token) use($resolver) {
            return $resolver->resolve($token->aro);
        }, function () use($resolver) {
            return $resolver->reject(new Unauthenticated());
        });
    }

    public function requireAuthentication($realm)
    {
        throw new Unauthorized();
    }
}
