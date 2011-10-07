<?php

namespace phmLabs\Annotation\Example;

class Deprecated
{
  /**
   * @deprecated
   */
  public function deprecatedFunction()
  {
    echo 'function';
  }

  public function functionCallingDeprecatedProtected()
  {
    $this->deprecatedProtectedFunction();
  }

  /**
   * @deprecated
   */
  protected function deprecatedProtectedFunction()
  {
    echo 'protectedFunction';
  }

  /**
   * @deprecated
   */
  public static function staticDeprecatedFunction()
  {
    echo 'staticFunction';
  }
}