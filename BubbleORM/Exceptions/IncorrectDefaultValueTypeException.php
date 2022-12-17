<?php
namespace BubbleORM\Exceptions;

use Exception;
use Throwable;

class IncorrectDefaultValueTypeException extends Exception{
    public function __construct($propertyName, Throwable $previous = null)
    {
        parent::__construct("Property '$propertyName' have an incorrect default value...", 0, $previous);
    }
}