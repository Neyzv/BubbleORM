<?php
namespace BubbleORM;

use BubbleORM\DatabaseAccessor;
use BubbleORM\Exceptions\MissingColumnException;

class MysqlQuery
{
    private ?array $result;
    private array $conditions;
    private array $orderFuncs;
    private ?ModelInformations $modelInfos;

    public function __construct(
        private readonly DatabaseAccessor $db,
        private readonly string $class
    ) {
        $this->result = null;
        $this->conditions = [];
        $this->orderFuncs = [];

        if(!ModelInformationsCache::tryGetModelInformations($this->class, $this->modelInfos))
            $this->modelInfos = ModelInformationsCache::tryRegisterModelInformations($this->class);
    }

    private function order(callable $func, bool $reverse) : void{
        if(!empty($this->result))
            usort($this->result, fn($x, $y) => ($func($x) >= $func($y) ? 1 : -1) * ($reverse ? -1 : 1));
    }

    private function buildResult(mixed $raw) : void{
        if(!is_null($this->result)){
            $instance = ($this->modelInfos->instanceFactory)();

            foreach($this->modelInfos->properties as $property){
                if(array_key_exists($property->getDbName(), $raw))
                    $property->bindValue($instance, $raw[$property->getDbName()]);
                else
                    throw new MissingColumnException($property->getDbName(), $this->modelInfos->tableInfos->tableName);
            }
            
            if(count(array_filter($this->conditions, fn($func) => $func($instance))) == count($this->conditions))
                $this->result[] = $instance;

            if(!empty($this->result))
                foreach($this->orderFuncs as $orderFunc)
                    if(count($orderFunc) == 2)
                        $this->order($orderFunc[0], $orderFunc[1]);
        }
    }

    public function where(callable $func) : self{
        $this->conditions[] = $func;
        
        if($this->result != null) {
            $newResult = [];

            foreach($this->result as $res)
                if($func($res))
                    $newResult[] = $res;
            
            $this->result = $newResult;
        }

        return $this;
    }

    public function orderBy(callable $orderFunc) : self{
        $this->orderFuncs[] = [$orderFunc, false];

        if($this->result != null)
            $this->order($orderFunc[0], $orderFunc[1]);

        return $this;
    }

    public function orderByDescending(callable $orderFunc) : self{
        $this->orderFuncs[] = [$orderFunc, true];

        if($this->result != null)
            $this->order($orderFunc[0], $orderFunc[1]);

        return $this;
    }

    public function all() : array{
        if(is_null($this->result)){
            $this->result = [];

            foreach($this->db->fetchAll("SELECT * FROM `". $this->modelInfos->tableInfos->tableName ."`") as $raw){
                $this->buildResult($raw);
            }
        }

        return $this->result;
    }

    public function firstOrDefault() : mixed{
        if(is_null($this->result)){
            $this->result = [];

            foreach($this->db->fetchAll("SELECT * FROM `". $this->modelInfos->tableInfos->tableName ."`") as $raw)
                $this->buildResult($raw);
        }

        return empty($this->result) ? null : $this->result[0];
    }

    public function min(callable $minFunc) : mixed{
        if(is_null($this->result))
            $this->all();
        
        $this->orderBy($minFunc);
        $result = $this->firstOrDefault();

        if(!is_null($result))
            $this->result = [ $result ];

        return $this->firstOrDefault();
    }

    public function max(callable $maxFunc) : mixed{
        if(is_null($this->result))
            $this->all();
        
        $this->orderByDescending($maxFunc);
        $result = $this->firstOrDefault();

        if(!is_null($result))
            $this->result = [ $result ];

        return $this->firstOrDefault();
    }

    public function clearResultCache() : self{
        $this->result = null;

        return $this;
    }

    public function clearWhereClauses() : self{
        $this->conditions = [];

        return $this;
    }

    public function clearOrderClauses() : self{
        $this->orderFuncs = [];

        return $this;
    }

    public function clearRequestCache() : self{
        $this->clearWhereClauses();

        return $this->clearOrderClauses();
    }

    public function close() : ?array{
        $this->clearResultCache();
        $this->clearRequestCache();

        return $this->result;
    }
}