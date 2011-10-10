<?php

namespace phmLabs\Base\Internal\Namespaces;

class NamespaceExtractor
{
  static public function fromClassname( $classname )
  {
    $parts = explode('\\', $classname);
    unset( $parts[count($parts)-1]);
    return implode('\\', $parts);
  }
}