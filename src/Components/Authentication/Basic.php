<?php
namespace Bread\REST\Components\Authentication;

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
use DateTime;

class Basic extends Method implements AuthenticationInterface
{

    public function authenticate(Resolver $resolver)
    {
        $domain = $this->request->headers['host'];
        list ($username, $password) = explode(":", base64_decode($this->data), 2);
        $promises = array();
        if (!$classes = (array) Configuration::get(get_class($this->controller), 'authentication.aro', $domain)) {
            return $resolver->reject(new Unauthenticated());
        }
        foreach ($classes as $class) {
            $promises[] = Authentication::driver($class, $domain)->authenticate($class, $username, $password);
        }
        return When::any($promises, function ($authenticated) use ($resolver, $domain) {
            list ($class, $username) = $authenticated;
            $property = Configuration::get($class, 'authentication.mapping.username', $domain);
            $search = array(
                $property => $username
            );
            return Storage::driver($class, $domain)->first($class, $search)->then(function ($aro) use ($class, $username, $domain) {
                $expiration = isset($this->request->headers['X-Device']) && $this->request->headers['X-Device'] === 'mobile' ? null : new DateTime('+1 day');
                $token = new Token\Model();
                $token->expire = $expiration;
                $token->aro = $aro;
                $token->data = base64_encode("{$username}:" . uniqid());
                return $token->store($domain)->then(function ($token) use ($expiration, $class, $domain) {
                    $secure = Configuration::get($class, 'location.secure', $domain);
                    $this->response->setCookie('Authorization', "Token {$token->data}", "+1 day", '/', null, $secure, false);
                    $this->response->headers['X-Token'] = $token->data;
                    return $token->aro;
                });
            });
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
