<?php

namespace phmLabs\Annotation\Annotation;

class AnnotationHandler
{
  const HOOK_TYPE_POST = 'post';
  const HOOK_TYPE_PRE = 'pre';

  private static $registeredAnnotations = array ();

  public static function registerAnnotation($annotationName)
  {
    // @todo in methode packen
    self::$registeredAnnotations[$annotationName] = '';
    $deprecatedAnnotation = CallableAnnotation::createAnnotation($annotationName);
  }

  public static function registerCallback($annotationName, $hookType, $callback)
  {
    $fullAnnotationName = __NAMESPACE__ . '\\' . $annotationName;
    self::$registeredAnnotations[$fullAnnotationName][$hookType][] = $callback;
  }

  public static function triggerHook($annotationName, $hookType, $parameter = array())
  {
    if (array_key_exists($annotationName, self::$registeredAnnotations) &&
        array_key_exists($hookType, self::$registeredAnnotations[$annotationName]))
    {
      foreach (self::$registeredAnnotations[$annotationName][$hookType] as $callback)
      {
        $callback($parameter);
      }
    }
  }
}