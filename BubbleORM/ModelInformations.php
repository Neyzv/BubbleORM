<?php
namespace BubbleORM;

use Closure;
use BubbleORM\Attributes\Table;
use BubbleORM\Exceptions\NotHandledTypeException;

class ModelInformations{
    private static array $dbTypeRelation = [
        "string" => "VARCHAR",
        "int" => "INT",
        "float" => "FLOAT",
        "array" => "BLOB",

        "TinyInt" => "TINYINT",
        "MediumInt" => "MEDIUMINT",
        "BigInt" => "BIGINT",

        "TinyText" => "TINYTEXT",
        "MediumText" => "MEDIUMTEXT",
        "Text" => "TEXT",
        "LongText" => "LONGTEXT",

        "TinyBlob" => "TINYBLOB",
        "MediumBlob" => "MEDIUMBLOB",
        "Blob" => "BLOB",
        "LongBlob" => "LONGBLOB",

        "DateTime" => "DATETIME",
        "Date" => "DATE"
    ];

    public function __construct(
        public string $className,
        public readonly Closure $instanceFactory,
        public readonly array $properties,
        public readonly Table $tableInfos
    ){}

    public function createModel() : string{
        $request = "";
        $properties = [];
        $keys = [];

        foreach($this->properties as $property){
            if(array_key_exists($property->getTypeName(), self::$dbTypeRelation)){
                $properties[] = $property;

                if($property->isPrimaryKey())
                    $keys[] = $property->getDbName();
            }
            else
                throw new NotHandledTypeException($property->getTypeName());
        }

        if(!empty($properties)){
            $request .= "CREATE TABLE IF NOT EXISTS `". $this->tableInfos->tableName ."` (\n\t".
            implode(",\n\t", array_map(fn($property) => "`". $property->getDbName() ."` ".
                self::$dbTypeRelation[$property->getTypeName()] . (is_null($property->getSize()) ?
                    (self::$dbTypeRelation[$property->getTypeName()] === "VARCHAR" ? "(255)" : "")
                : "(". $property->getSize() .")"). ($property->isUnsigned() ? " UNSIGNED" : ""). ($property->isNullable() ? "" : " NOT NULL")
                    . (in_array($property->getDbName() , $keys) ? " AUTO_INCREMENT" : ""), $properties));
            
            if(!empty($keys))
                $request .= ",\n\tPRIMARY KEY (". implode(", ", array_map(fn($key) => "`$key`", $keys)) .")";
            
            $request .= "\n);";
        }

        return $request;
    }
}