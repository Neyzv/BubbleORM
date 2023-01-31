<?php
namespace BubbleORM;

use BubbleORM\Attributes\Name;
use BubbleORM\Attributes\DefaultValue;
use BubbleORM\Attributes\Key;
use BubbleORM\Attributes\MysqlType;
use BubbleORM\Attributes\Size;
use BubbleORM\Attributes\Unsigned;
use BubbleORM\Exceptions\IncorrectDefaultValueTypeException;
use BubbleORM\Exceptions\InvalidUnsignedForTypeException;
use BubbleORM\Exceptions\NotInitializedProperty;
use ReflectionProperty;

class DatabaseColumn{
    private string $name;
    private string $dbName;
    private string $typeName;
    private ?int $size;
    private mixed $defaultValue;
    private bool $isPrimaryKey;
    private bool $isNullable;
    private bool $isUnsigned;

    private static array $typeRelation = [
        "int" => "integer",
        "float" => "double",
        "double" => "double",
        "bool" => "boolean",
        "string" => "string"
    ];

    private static array $unsignedTypes = [
        "int",
        "float",
        "double"
    ];

    public function __construct(
        private readonly ReflectionProperty $propertyInfos,
        array $attributes
    )
    {
        $type = $propertyInfos->getType();

        $this->name = $this->propertyInfos->getName();
        $this->dbName = $this->name;
        $this->isPrimaryKey = false;
        $this->isNullable = $type->allowsNull();
        $this->isUnsigned = false;
        $this->typeName = $type->getName();
        $this->defaultValue = null;
        $this->size = null;

        foreach($attributes as $attribute){
            switch($attribute->getName()){
                case Name::class:
                    $this->dbName = $attribute->newInstance()->name;
                    break;

                case MysqlType::class:
                    $this->typeName = $attribute->newInstance()->type->name;
                    break;

                case Size::class:
                    $this->size = $attribute->newInstance()->size;
                    break;
                    
                case DefaultValue::class:
                    $this->defaultValue = $attribute->newInstance()->defaultValue;
                    break;
                
                case Key::class:
                    $this->isPrimaryKey = true;
                    break;

                case Unsigned::class:
                    $this->isUnsigned = true;
                    break;
            }
        }

        if($this->isUnsigned() && !in_array($type->getName(), self::$unsignedTypes))
            throw new InvalidUnsignedForTypeException($this->name);

        if(!is_null($this->defaultValue) && self::$typeRelation[$type->getName()] !== gettype($this->defaultValue))
            throw new IncorrectDefaultValueTypeException($this->name);
    }

    public function isPrimaryKey() : bool{
        return $this->isPrimaryKey;
    }

    public function isUnsigned() : bool{
        return $this->isUnsigned;
    }

    public function isNullable() : bool{
        return $this->isNullable;
    }

    public function getDbName() : string{
        return $this->dbName;
    }

    public function getTypeName() : string{
        return $this->typeName;
    }

    public function getSize() : ?int{
        return $this->size;
    }

    public function bindValue(object $instance, mixed $value) : void{
        $this->propertyInfos->setValue($instance, $value);
    }

    public function isInitialized($instance) : bool{
        return $this->propertyInfos->isInitialized($instance);
    }

    public function getValue(object $instance) : mixed{
        if(!$this->isInitialized($instance))
            throw new NotInitializedProperty($this->name);
        
        return $this->propertyInfos->getValue($instance);
    }
}