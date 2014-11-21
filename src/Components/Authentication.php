<?php
namespace Bread\REST\Components;

use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Networking\HTTP\Client\Exceptions\BadRequest;
use Bread\REST\Components\Authentication\Basic;
use Bread\REST\Components\Authentication\Digest;
use Bread\REST\Components\Authentication\Token;
use Bread\REST\Components\Authentication\SSL;
use Bread\REST\Components\Authentication\None;
use Bread\Configuration\Manager as Configuration;
use Bread\REST\Routing\Firewall;
use Bread\Streaming\Bucket;
use Bread\Promises\Deferred;
use Bread\Promises\Fulfilled;
use Bread\Promises\Rejected;
use Bread\Helpers\JSON;


class Authentication
{

    protected static $authorizationPattern = '/^(?<method>\w+)\s(?<data>.+)/';

    public static function factory(Firewall $controller, Request $request, Response $response)
    {
        $domain = $request->headers['host'];
        if ($data = $request->connection->getClientIdentity()) {
            return new SSL($controller, $request, $response, $data);
        } elseif (preg_match(self::$authorizationPattern, $request->headers['Authorization'], $matches) ||
            preg_match(self::$authorizationPattern, $request->cookies['Authorization'], $matches)) {
            return static::getMethod(strtolower($matches['method']), $matches['data'], $domain, $controller, $request, $response);
        } elseif($data = $request->query['Authorization']) {
            $split = split(" ", $data, 2);
            return static::getMethod(strtolower(strtolower($split[0])), isset($split[1]) ? $split[1] : "", $domain, $controller, $request, $response);
        } else {
            return new None($controller, $request, $response);
        }
    }

    private static function getMethod($method, $data, $domain, $controller, $request, $response) {
        if ($methodClass = Configuration::get(static::class, "methods.$method", $domain)) {
            return new $methodClass($controller, $request, $response, $data);
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