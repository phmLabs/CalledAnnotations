<?php

namespace phmLabs\Annotation\Annotation;

use Doctrine\Common\Annotations\Annotation as DoctrineAbstractAnnotation;

class CallableAnnotation extends DoctrineAbstractAnnotation implements Annotation
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

  public function addCallback($callback, $hookType)
  {
    self::$callbacks[$hookType][] = $callback;
  }

  public function call($hookType)
  {
    $callbacks = self::$callbacks[$hookType];
    foreach ($callbacks as $callback)
    {
      $callback();
    }
  }
}