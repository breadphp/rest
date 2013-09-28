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
use Bread\Authentication\Manager as Authentication;
use Bread\Storage\Manager as Storage;
use Bread\Promises\When;
use Bread\REST\Components\Authentication\Token\Model;

class Basic extends Method implements AuthenticationInterface
{
    
    public function authenticate(Resolver $resolver)
    {
        list ($username, $password) = explode(":", base64_decode($this->data), 2);
        $promises = array();
        $classes = (array) Configuration::get(get_class($this->controller), 'authentication.aro');
        foreach ($classes as $class) {
            $promises[] = Authentication::driver($class)->authenticate($class, $username, $password);
        }
        return When::any($promises, function ($authenticated) use ($resolver) {
            list ($class, $username) = $authenticated;
            $property = Configuration::get($class, 'authentication.mapping.username');
            $search = array(
                $property => $username
            );
            return Storage::driver($class)->first($class, $search);
        })->then(array($resolver, 'resolve'), function ($class) use ($resolver) {
            return $resolver->reject(new Unauthenticated());
        });
    }

    public function requireAuthentication($realm)
    {
        $this->response->headers['WWW-Authenticate'] = sprintf('Basic realm="%s"', $realm);
        throw new Unauthorized();
    }
}