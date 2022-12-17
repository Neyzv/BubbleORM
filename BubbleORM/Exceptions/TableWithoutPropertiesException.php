<?php
namespace BubbleORM\Exceptions;

use Exception;
use Throwable;

class TableWithoutPropertiesException extends Exception{
    public function __construct($className, Throwable $previous = null)
    {
        parent::__construct("Class '$className' doesn't have any valid properties, must have one minimum...", 0, $previous);
    }
}