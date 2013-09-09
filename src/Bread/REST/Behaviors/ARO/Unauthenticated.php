<?php

namespace Bread\REST\Behaviors\ARO;

use Bread\REST\Behaviors;

class Unauthenticated extends Behaviors\ARO {

  public function isMember(Behaviors\ARO $aro) {
    return false;
  }

  public function href() {
    return false;
  }

  public function getMember() {
    return false;
  }
}
