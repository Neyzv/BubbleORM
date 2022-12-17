<?php
namespace BubbleORM\Exceptions;

use Exception;
use Throwable;

class NotInitializedProperty extends Exception{
    public function __construct($propertyName, Throwable $previous = null)
    {
        parent::__construct("Trying to get property '$propertyName' value, but it isn't initialized...", 0, $previous);
    }
}