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

class Digest extends Method implements AuthenticationInterface
{

    public function authenticate(Resolver $resolver)
    {
        preg_match_all('/(\w+)=("((?:[^"\\\\]|\\\\.)+)"|([^\s,$]+))/', $this->data, $matches, PREG_SET_ORDER);
        $elements = array();
        foreach ($matches as $match) {
            if (isset($match[1]) && isset($match[3])) {
                $elements[$match[1]] = isset($match[4]) ? $match[4] : $match[3];
            }
        }
        list ($username, $password) = array($elements['username'], $elements['response']);
        return $resolver->reject(new Unauthenticated());
    }

    public function requireAuthentication($realm)
    {
        $key = Configuration::get(__CLASS__, 'key');
        $nonceTimeout = Configuration::get(__CLASS__, 'nonceTimeout') ? : 300;
        $expiryTime = microtime(true) + $nonceTimeout * 1000;
        $signatureValue = md5($expiryTime . ':' . $key);
        $nonceValue = $expiryTime . ':' . $signatureValue;
        $nonceValueBase64 = base64_encode($nonceValue);
        $authenticateHeader = sprintf('Digest realm="%s", qop="auth", nonce="%s"', $realm, $nonceValueBase64);
        $this->response->headers['WWW-Authenticate'] = $authenticateHeader;
        throw new Unauthorized();
    }
}