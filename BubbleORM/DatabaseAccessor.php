<?php
namespace BubbleORM;

use BubbleORM\Enums\DatabaseCreationMode;
use PDO;
use PDOStatement;

class DatabaseAccessor{
    private array $items;
    private array $itemsToDelete;
    private array $modelsToBuild;
    private readonly PDO $db;

    public string $backupPath;

    public function __construct(string $host, string $user, string $password, 
        private readonly string $dbName,
        public DatabaseCreationMode $creationMode = DatabaseCreationMode::None
    ) {
        $this->items = [];
        $this->itemsToDelete = [];
        $this->modelsToBuild = [];

        $this->backupPath = getcwd() ."\\BubbleSQLBackups";

        $this->db = new PDO("mysql:host=$host;dbname=$dbName", $user, $password);
    }

    private function clearCache() : void{
        $this->items = [];
        $this->itemsToDelete = [];
    }

    private function registerModelInformations(string $className) : void{
        if($this->creationMode != DatabaseCreationMode::None){
            $model = ModelInformationsCache::tryRegisterModelInformations($className);

            if(!is_null($model))
                $this->modelsToBuild[] = $model;
        }
    }

    private function buildModelsSQLScript() : string{
        $backupScripts = [];
        $res = "";

        if(!empty($this->modelsToBuild)){
            $modelsToBuild = [];

            foreach($this->modelsToBuild as $model)
                $modelsToBuild[] = $model;
            
            $this->modelsToBuild = [];

            foreach($modelsToBuild as $model){
                $creationScript = $model->createModel();
    
                $res .= ($this->creationMode == DatabaseCreationMode::Override || $this->creationMode == DatabaseCreationMode::OverrideAndBackup ?
                "DROP TABLE IF EXISTS `". $model->tableInfos->tableName .'`;' : "") . $creationScript;
    
                if($this->creationMode == DatabaseCreationMode::OverrideAndBackup){
                    $subBuild = "";

                    if($this->tableExist($model->className) && ModelInformationsCache::tryGetModelInformations($model->className, $modelInformations)){
                        $dbRows = $this->createQuery($model->className)->all();
                        if(!empty($dbRows)){
                            foreach($dbRows as $row){
                                $initializedProperties = array_filter($modelInformations->properties, fn($property) => $property->isInitialized($row));
                                
                                if(!empty($initializedProperties)){
                                    $subBuild .= "INSERT INTO `". $modelInformations->tableInfos->tableName ."` (";
                                    $subBuild .= implode(", ", array_map(fn($property) => "`". $property->getDbName() ."`", $initializedProperties)) .") VALUES (";
                                    $subBuild .= implode(", ", array_map(fn($property) => (is_null(($value = $property->getValue($row))) ? "NULL" : "'". $value ."'"), $initializedProperties)) .");\n";
                                }
                            }
                        }
                    }

                    $backupScripts[] = "-- CREATION OF TABLE ". $model->tableInfos->tableName ." --\n". "DROP TABLE IF EXISTS `". $model->tableInfos->tableName
                    ."`;\n". $creationScript .(empty($subBuild) ? "" : "\n-- RECORDS FOR TABLE ". $model->tableInfos->tableName ." --\n". $subBuild);
                }
            }

            if(!empty($backupScripts)){
                if(!is_dir($this->backupPath))
                    mkdir($this->backupPath, 0777, true);
                
                file_put_contents($this->backupPath . "\\" . date("Y-m-d_His") .".sql", implode("\n\n", $backupScripts));
            }
        }

        return $res;
    }

    public function execute(string $request, array $params = []) : PDOStatement{
        $preRequest = $this->buildModelsSQLScript();
        if(!empty($preRequest)){
            $req = $this->db->prepare($preRequest);
            $req->execute();
        }
        
        $req = $this->db->prepare($request);
        $req->execute($params);

        return $req;
    }

    public function fetch(string $request, array $params = []) : mixed{
        return $this->execute($request, $params)->fetch();
    }

    public function fetchAll(string $request, array $params = []) : array{
        return $this->execute($request, $params)->fetchAll();
    }

    public function createQuery(string $className) : MysqlQuery{
        $this->registerModelInformations($className);

        return new MysqlQuery($this, $className);
    }

    public function tableExist(string $className) : bool{
        $this->registerModelInformations($className);
        
        return ModelInformationsCache::tryGetModelInformations($className, $modelInformations) && $this->fetch("SELECT * 
        FROM information_schema.tables
        WHERE table_schema = ?
            AND table_name = ?
        LIMIT 1;", [$this->dbName, $modelInformations->tableInfos->tableName]);
    }

    public function add(object $item) : self{
        $this->registerModelInformations($item::class);
        $this->items[] = $item;

        return $this;
    }

    public function addRange(array $items) : self{
        foreach($items as $item)
            $this->add($item);

        return $this;
    }

    public function remove(object $item) : self{
        $this->registerModelInformations($item::class);
        $this->itemsToDelete[] = $item;

        return $this;
    }

    public function removeRange(array $items) : self{
        foreach($items as $item)
            $this->remove($item);

        return $this;
    }

    public function commit() : void{
        $request = "";

        foreach($this->itemsToDelete as $item){
            $modelInformations = null;

            if(ModelInformationsCache::tryGetModelInformations($item::class, $modelInformations)){
                $request .= "DELETE FROM `". $modelInformations->tableInfos->tableName ."` WHERE ";
                $request .= implode(" AND ", array_map(fn($key) => "`". $key->getDbName() ."` = ". (is_null(($value = $key->getValue($item))) ? "NULL" : "'". $value ."'"),
                    array_filter($modelInformations->properties, fn($dbClmn) => $dbClmn->isPrimaryKey()))) .";";
            }
        }

        foreach($this->items as $item){
            $modelInformations = null;

            if(ModelInformationsCache::tryGetModelInformations($item::class, $modelInformations)){
                $initializedProperties = array_filter($modelInformations->properties, fn($property) => $property->isInitialized($item));
                $keys = array_filter($initializedProperties, fn($dbClmn) => $dbClmn->isPrimaryKey());

                if(!empty($keys) && count(array_filter($keys, fn($key) => $key->isInitialized($item))) == count($keys) && 
                    !is_null($this->fetch("SELECT 1 FROM `". $modelInformations->tableInfos->tableName ."` WHERE ".
                        implode(" AND ", array_map(fn($key) => "`". $key->getDbName() ."` = ". $key->getValue($item), $keys)))))
                {
                    $request .= "UPDATE `". $modelInformations->tableInfos->tableName ."` SET ".
                        implode(", ", array_map(fn($property) => "`". $property->getDbName() ."` = ". (is_null(($value = $property->getValue($item))) ? "NULL" : "'". $value ."'"),
                            $initializedProperties));
                    $request .= " WHERE ". implode(" AND ", array_map(fn($key) => "`". $key->getDbName() ."` = ". (is_null(($value = $key->getValue($item))) ? "NULL" : "'". $value ."'"),
                        $keys)) .";";
                }
                else{

                    if(!empty($initializedProperties)){
                        $request .= "INSERT INTO `". $modelInformations->tableInfos->tableName ."` (";
                        $request .= implode(", ", array_map(fn($property) => "`". $property->getDbName() ."`", $initializedProperties)) .") VALUES (";
                        $request .= implode(", ", array_map(fn($property) => (is_null(($value = $property->getValue($item))) ? "NULL" : "'". $value ."'"), $initializedProperties)) .");";
                    }
                }
            }
        }
        
        $this->execute($request);
        $this->clearCache();
    }
}
