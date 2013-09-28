<?php
namespace Bread\REST\Components\Authentication;

use Bread\REST\Controller;
use Bread\REST\Behaviors\ARO\Unauthenticated;
use Bread\REST\Components\Interfaces\Authentication as AuthenticationInterface;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Networking\HTTP\Client\Exceptions\Unauthorized;
use Bread\Promises\Interfaces\Resolver;

class None extends Method implements AuthenticationInterface
{

    public function authenticate(Resolver $resolver)
    {
        return $resolver->reject(new Unauthenticated());
    }
    
    public function requireAuthentication($realm)
    {
        throw new Unauthorized();
    }
}