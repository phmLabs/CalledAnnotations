<?php

namespace phmLabs\Annotation\Annotation;

use Doctrine\Common\Annotations\Annotation as DoctrineAbstractAnnotation;

class CallableAnnotation extends DoctrineAbstractAnnotation
{
  private static $callbacks;

  /**
   * @return CallableAnnotation
   */
  public static function createAnnotation($name)
  {
    $classContext = 'namespace ' . __NAMESPACE__ . '; class ' . $name . ' extends CallableAnnotation { }';
    eval($classContext);

    $className = __NAMESPACE__ . '\\' . $name;

    return new $className(array());
  }
}