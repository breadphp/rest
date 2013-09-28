<?php
namespace Bread\REST\Components;

use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Networking\HTTP\Client\Exceptions\BadRequest;
use Bread\REST\Controller;
use Bread\REST\Components\Authentication\Basic;
use Bread\REST\Components\Authentication\Digest;
use Bread\REST\Components\Authentication\Token;
use Bread\REST\Components\Authentication\SSL;
use Bread\REST\Components\Authentication\None;
use Bread\Configuration\Manager as Configuration;

class Authentication
{

    protected static $authorizationPattern = '/^(?<method>\w+)\s(?<data>.+)/';

    public static function factory(Controller $controller, Request $request, Response $response)
    {
        if ($data = $request->connection->getClientIdentity()) {
            return new SSL($controller, $request, $response, $data);
        } elseif (preg_match(self::$authorizationPattern, $request->headers['Authorization'], $matches) ||
            preg_match(self::$authorizationPattern, $request->cookies['Authorization'], $matches)) {
            $method = strtolower($matches['method']);
            if ($methodClass = Configuration::get(static::class, "methods.$method")) {
                return new $methodClass($controller, $request, $response, $matches['data']);
            } else {
                throw new BadRequest();
            }
        } else {
            return new None($controller, $request, $response);
        }
    }
}

Configuration::defaults(Authentication::class, array(
    'methods' => array(
        'basic' => Basic::class,
        'digest' => Digest::class,
        'token' => Token::class,
        'ssl' => SSL::class
    )
));