<?php

namespace Bread\REST\Behaviors;

use Bread\REST\Authentication;

abstract class ARO extends ACO {

  public static function authenticate($username, $password) {
    $class = get_called_class();
    return Authentication::driver($class)->authenticate($class, $username, $password);
  }

  abstract public function isMember(ARO $aro);
  abstract public function href();
  abstract public function getMember();

}
