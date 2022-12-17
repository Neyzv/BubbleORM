<?php
namespace BubbleORM\Exceptions;

use Exception;
use Throwable;

class MissingColumnException extends Exception{
    public function __construct($columnName, $tableName, Throwable $previous = null)
    {
        parent::__construct("Column '$columnName' doesn't exist in table '$tableName'...", 0, $previous);
    }
}