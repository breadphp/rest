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

class SSL extends Method implements AuthenticationInterface
{

    public function authenticate(Resolver $resolver)
    {
        // TODO $this->data['subjectEmail']
        return $resolver->reject(new Unauthenticated());
    }
    
    public function requireAuthentication($realm)
    {
        throw new Unauthorized();
    }
}