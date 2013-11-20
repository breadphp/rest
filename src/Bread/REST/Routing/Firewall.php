<?php
namespace Bread\REST\Routing;

use Bread\Networking\HTTP;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Promises\Deferred;
use Bread\REST\Components\Authentication;
use Bread\REST\Components\Authorization\ACL;
use Bread\REST\Components\Authorization\ACE;
use Bread\Promises\When;
use Bread\Configuration\Manager as Configuration;
use Bread\Networking\HTTP\Client\Exceptions\Forbidden;

class Firewall
{

    const PRIVILEGE_ACCESS = 'access';

    protected $request;

    protected $response;

    protected $authenticated;

    protected $authentication;
    
    protected $defaultACL;
    
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->authenticated = new Deferred();
        $this->authentication = Authentication::factory($this, $request, $response);
        $this->authentication->authenticate($this->authenticated->resolver());
        $this->defaultACL = new ACL();
        $defaultACL = Configuration::get(static::class, 'acl.default');
        $this->defaultACL->acl = array(
            new ACE(array(
                'type' => isset($defaultACL['type']) ? (int) $defaultACL['type'] : ACE::ALL,
                'grant' => isset($defaultACL['grant']) ? (array) $defaultACL['grant'] : array(ACE::PRIVILEGE_ALL),
                'deny' => isset($defaultACL['deny']) ? (array) $defaultACL['deny'] : array()
            ))
        );
    }

    public function access(Route $route)
    {
        if ($origin = $this->request->headers['Origin']) {
            $this->response->headers['Access-Control-Allow-Origin'] = $origin;
            $this->response->headers['Access-Control-Allow-Credentials'] = 'true';
            $this->response->headers['Access-Control-Expose-Headers'] = 'Location, Content-Location, X-Token, X-Count';
        }
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->headers['Access-Control-Allow-Headers'] = $this->request->headers['Access-Control-Request-Headers'];
                $this->response->headers['Access-Control-Allow-Methods'] = $this->request->headers['Access-Control-Request-Method'];
                $this->response->headers['Access-Control-Max-Age'] = '1728000';
                break;
        }
        return $this->authenticated->then(null, function ($unauthenticated) {
            return $unauthenticated;
        })->then(function ($aro) use ($route) {
            switch ($this->request->method) {
                case 'OPTIONS':
                    return array($aro, $route);
            }
            return ACL::first(array('aco' => $route))->then(function ($acl) use ($aro) {
                return $acl->authorize($aro, 'access');
            }, function () use ($aro, $route) {
                $this->defaultACL->aco = $route;
                return $this->defaultACL->authorize($aro, 'access');
            })->then(function ($route) use ($aro) {
                return array($aro, $route);
            });
        });
    }
    
    public function authenticate($realm = null)
    {
        return $this->authenticated->then(null, function () use ($realm) {
            $method = Configuration::get(static::class, 'authentication.require');
            $authenticationClass = Configuration::get(Authentication::class, "methods.$method");
            $authenticationMethod = new $authenticationClass($this, $this->request, $this->response);
            return $authenticationMethod->requireAuthentication($realm);
        });
    }
}
