<?php

namespace phmLabs\Autoload;

include_once __DIR__ . '/Strategy/Strategy.php';
include_once __DIR__ . '/Strategy/SimpleStrategy.php';

use phmLabs\Autoload\Strategy\Strategy;

class Autoloader
{
  private $strategy;

  private $namespaces = array ();

  public function __construct()
  {
    spl_autoload_register(array ($this, 'autoload'));
  }

  public function registerNamespace($prefix, Strategy $strategy)
  {
    $this->namespaces[$prefix] = $strategy;
  }

  public function autoload($classname)
  {
    $namespaceElements = explode('\\', $classname);
    $i = 1;
    $namespaces[0] = '';
    foreach ($namespaceElements as $namespaceElement)
    {
      $namespaces[$i] = $namespaces[$i - 1] . '\\' . $namespaceElement;
      $i ++;
    }

    $namespaces = array_reverse($namespaces);

    foreach ($namespaces as $namespace)
    {
      if (array_key_exists($namespace, $this->namespaces))
      {
        $this->namespaces[$namespace]->autoload($classname);
        return;
      }
    }
  }
}