<?php

namespace Bread\REST\Behaviors;

use Bread\Authentication\Manager as Authentication;

abstract class ARO extends ACO {

  public static function authenticate($username, $password) {
    $class = get_called_class();
    return Authentication::driver($class)->authenticate($class, $username, $password);
  }

  public function isMember(ARO $aro)
  {
      return $this === $aro;
  }

}
