<?php
namespace BubbleORM\Exceptions;

use Exception;
use Throwable;

class InvalidUnsignedForTypeException extends Exception{
    public function __construct($propertyName, Throwable $previous = null)
    {
        parent::__construct("Type of property '$propertyName' doesn't allow an unsigned attribute...", 0, $previous);
    }
}