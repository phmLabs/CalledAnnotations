<?php

namespace phmLabs\Autoload\Strategy;

class SimpleStrategy implements Strategy
{
  public function autoload($classname)
  {
    $filename = (__DIR__ . '/../../../' . str_replace('\\', DIRECTORY_SEPARATOR, $classname) . '.php');
    include_once $filename;
  }
}