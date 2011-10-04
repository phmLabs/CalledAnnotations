<?php

// @todo add persistenz layer (als dekorator)


namespace phmLabs\Annotation\Autoload\Strategy;

use Doctrine\Common\Annotations\AnnotationReader;
use phmLabs\Autoload\Strategy\Strategy;
use ReflectionClass, ReflectionMethod;

class AnnotationStrategy implements Strategy
{
  /**
   * The classname decorator for the temporary created class
   *
   * @var string
   */
  const TMP_CLASS_POSTFIX = '_phmCalledAnnotation';
  
  /**
   * The found annotations
   *
   * @var mixed[]
   */
  private $annotations = array();
  
  /**
   * The name of the class to be autoloaded
   *
   * @var string
   */
  private $classname;
  
  /**
   * This method is registred as spl_autoload function using the phmLabs autoload class.
   *
   * @param string $classname
   */
  public function autoload($classname)
  {
    $this->classname = $classname;
    $this->createTmpClass();
    $this->extractAnnotations();
    $this->createNewClass();
  }
  
  /**
   * Returns the filename according to the given classname (autoload)
   *
   * @return string
   */
  private function getFilename()
  {
    // @todo könnte noch verfeinert werden. siehe sf2, dort werden pfade zum namespace noch registriert
    return __DIR__ . '/../../../../' . str_replace('\\', DIRECTORY_SEPARATOR, $this->classname) . '.php';
  }
  
  /**
   * Returns the tokenized class
   *
   * @return mixed[] the tokens
   */
  private function getTokenizedClass()
  {
    $classContent = file_get_contents($this->getFilename());
    return token_get_all($classContent);
  }
  
  private function createTmpClass()
  {
    $classTokenized = $this->getTokenizedClass();
    for($i = 0; $i < count($classTokenized); $i ++)
    {
      if ($classTokenized[$i][0] == T_OPEN_TAG)
      {
        unset($classTokenized[$i]);
      }
      elseif ($classTokenized[$i][0] == T_CLASS)
      {
        // @todo alle "unwichtigen" Tokens rauswerfen
        $className = $classTokenized[$i + 2][1];
        $classTokenized[$i + 2][1] = $className . self::TMP_CLASS_POSTFIX;
      }
    }
    $this->tokenToClass($classTokenized);
  }
  
  private function extractAnnotations()
  {
    $this->annotations = $this->getAnnotations($this->classname . self::TMP_CLASS_POSTFIX);
  }
  
  private function getAnnotations($classname)
  {
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
  
  private function createNewClass()
  {
    $classTokenized = $this->getTokenizedClass();
    
    if (count($this->annotations) == 0)
    {
      $this->tokenToClass($classTokenized);
      return;
    }
    
    $classContent = '';
    
    for($i = 0; $i < count($classTokenized); $i ++)
    {
      if (is_array($classTokenized[$i]))
      {
        if ($classTokenized[$i][0] == T_OPEN_TAG)
        {
          unset($classTokenized[$i]);
        }
        elseif ($classTokenized[$i][0] == T_FUNCTION)
        {
          // @todo nur methoden, die auch überschrieben werden sollen
          $classContent .= $classTokenized[$i][1];
          $classTokenized[$i + 2][1] .= '_ANNOTETED_IGNORE_SUFFIX_ON_BUGFIXING';
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
    
    foreach ($this->annotations as $functionName => $functionAnnotations)
    {
      // @todo static, public, abstract, parameter ...
      $classContent .= "\n  public function " . $functionName . "( )\n  { ";
      $classContent .= "\n    \$parameters = func_get_args();";
      foreach ($functionAnnotations['annotation'] as $functionAnnotation)
      {
        $classContent .= "\n    \\phmLabs\\Annotation\\Annotation\\AnnotationHandler::triggerHook('" . get_class($functionAnnotation) . "', \\phmLabs\\Annotation\\Annotation\\AnnotationHandler::HOOK_TYPE_PRE, \$parameters);";
      }
      
      if ($functionAnnotations['method']->isStatic())
      {
        $object = '"' . $this->classname . '"';
      }
      else
      {
        $object = '$this';
      }
      
      $classContent .= "\n    \$result = call_user_func_array( array( " . $object . ", '" . $functionName . "_ANNOTETED_IGNORE_SUFFIX_ON_BUGFIXING'), \$parameters);";
      
      foreach ($functionAnnotations['annotation'] as $functionAnnotation)
      {
        $classContent .= "\n    \\phmLabs\\Annotation\\Annotation\\AnnotationHandler::triggerHook('" . get_class($functionAnnotation) . "', \\phmLabs\\Annotation\\Annotation\\AnnotationHandler::HOOK_TYPE_POST, \$parameters);";
      }
      $classContent .= "\n    return \$result;";
      $classContent .= "\n  }";
    }
    
    $classContent .= "\n}";
    eval($classContent);
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