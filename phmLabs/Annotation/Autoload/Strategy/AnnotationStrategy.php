<?php

namespace phmLabs\Annotation\Autoload\Strategy;

use Doctrine\Common\Annotations\AnnotationReader;
use phmLabs\Autoload\Strategy\Strategy;
use ReflectionClass, ReflectionMethod;

class AnnotationStrategy implements Strategy
{
  private $annotations = array ();

  public function autoload($classname)
  {
    $filename = __DIR__ . '/../../../../' . str_replace('\\', DIRECTORY_SEPARATOR, $classname) . '.php';

    $classContent = file_get_contents($filename);
    $classTokenized = token_get_all($classContent);
    $originalClassTokenized = $classTokenized;

    for ($i = 0; $i < count($classTokenized); $i ++)
    {
      if ($classTokenized[$i][0] == T_OPEN_TAG)
      {
        unset($classTokenized[$i]);
      }
      elseif ($classTokenized[$i][0] == T_CLASS)
      {
        // alle "unwichtigen" Tokens rauswerfen
        $className = $classTokenized[$i + 2][1];
        $classTokenized[$i + 2][1] = $className . '_phmAnnotation';
      }
      elseif (($classTokenized[$i][0] == T_NAMESPACE))
      {
        $j = 2;
        $namespace = '';
        while (is_array($classTokenized[$i + $j]) && $classTokenized[$i + $j][0] != T_WHITESPACE)
        {
          $namespace .= $classTokenized[$i + $j][1];
          $j ++;
        }
      }
    }
    $this->tokenToClass($classTokenized);
    $annotations = $this->getAnnotations($namespace . '\\' . $className . '_phmAnnotation');
    $this->createNewClass($originalClassTokenized, $annotations);
  }

  private function getAnnotations($classname)
  {
    // abstrakte methoden können nicht annotiert werden
    $reflectedListener = new ReflectionClass($classname);
    $methods = $reflectedListener->getMethods();
    $this->annotationReader = new AnnotationReader();
    $this->annotationReader->setDefaultAnnotationNamespace('phmLabs\Annotation\Annotation\\');
    $this->annotationReader->setAutoloadAnnotations(false);

    foreach ($methods as $method)
    {
      $annotations[$method->name]['annotation'] = $this->annotationReader->getMethodAnnotations($method);
      $annotations[$method->name]['method'] = $method;
    }
    return $annotations;
  }

  private function createNewClass($classTokenized, $annotations)
  {
    if (count($annotations) == 0)
    {
      $this->tokenToClass($classTokenized);
    }
    else
    {
      $classContent = '';
      for ($i = 0; $i < count($classTokenized); $i ++)
      {
        if (is_array($classTokenized[$i]))
        {
          if ($classTokenized[$i][0] == T_OPEN_TAG)
          {
            unset($classTokenized[$i]);
          }
          elseif ($classTokenized[$i][0] == T_FUNCTION)
          {
            $classContent .= $classTokenized[$i][1];
            $classTokenized[$i + 2][1] .= '_ANNOTETED_IGONORE_SUFFIX_ON_BUGFIXING';
          }
          else
          {
            $classContent .= $classTokenized[$i][1];
          }
        }
        else
        {
          $classContent .= $classTokenized[$i];
        }
      }

      foreach ($annotations as $functionName => $functionAnnotations)
      {
        // static, public, abstract, parameter ...
        $classContent .= "\n  public function " . $functionName . "( )\n  { ";
        foreach ($functionAnnotations['annotation'] as $functionAnnotation)
        {
          $classContent .= "\n    \phmLabs\Annotation\Annotation\AnnotationHandler::triggerHook('" . get_class($functionAnnotation) . "', \phmLabs\Annotation\Annotation\AnnotationHandler::HOOK_TYPE_PRE);";
        }

        $classContent .= "\n    \$result = " . '$this->' . $functionName . '_ANNOTETED_IGONORE_SUFFIX_ON_BUGFIXING();';

        foreach ($functionAnnotations['annotation'] as $functionAnnotation)
        {
          $classContent .= "\n    \phmLabs\Annotation\Annotation\AnnotationHandler::triggerHook('" . get_class($functionAnnotation) . "', \phmLabs\Annotation\Annotation\AnnotationHandler::HOOK_TYPE_POST);";
        }
        $classContent .= "\n    return \$result;";
        $classContent .= "\n  }";
      }

      $classContent .= "\n}";

      eval($classContent);
    }
  }

  private function tokenToClass($tokens)
  {
    $classContent = '';

    foreach ($tokens as $token)
    {
      if (is_array($token))
      {
        $classContent .= $token[1];
      }
      else
      {
        $classContent .= $token;
      }
    }
    eval($classContent);
  }
}