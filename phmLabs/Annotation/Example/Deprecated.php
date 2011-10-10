<?php

namespace phmLabs\Annotation\Example;

class Deprecated implements DeprecatedInterface
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

  /**
   * @deprecated
   */
  public function deprecatedFunctionWithParameters( $param1 )
  {
  	echo $param1;
  }
}