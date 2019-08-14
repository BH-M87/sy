<?php
/**
 * model基类
 * AR可用事件参见BaseActiveRecord常量定义
 * @author shenyang
 * @date 2017-06-03
 */
namespace app\models;

use common\MyException;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\db\Schema;

Class BaseModel extends ActiveRecord {
    private static $_models;

    //单例
    public static function model()
    {
        $name = get_called_class();
        if(!isset(self::$_models[$name]) || !is_object(self::$_models[$name])) {
            $instance = self::$_models[$name] = new static();
            return $instance;
        }
        return self::$_models[$name];
    }

    //批量插入数据整理
    private function _batchFormat($data)
    {
        $rows = [];
        foreach ($data as $kk=>$vv) {
            foreach ($vv as $k=>$v) {
                $rows[$k][] = $v;
            }
        }
        return $rows;
    }

    //笛卡尔积
    private function _dicaer($data)
    {
        foreach ($data as $v) {
            if (!$v) {
                return [];
            }
        }
        $arr1 = array();
        $result = array_shift($data);
        while(($arr2 = array_shift($data)) !== null){
            $arr1 = $result;
            $result = array();
            if (!$arr2) {
                break;
            }
            if(!is_array($arr1))$arr1 = array($arr1);
            if(!is_array($arr2))$arr2 = array($arr2);
            foreach($arr1 as $v){
                foreach($arr2 as $v2){
                    if(!is_array($v))$v = array($v);
                    if(!is_array($v2))$v2 = array($v2);
                    $result[] = array_merge_recursive($v,$v2);
                }
            }
        }
        return $result;
    }

    //yii2自带批量插入
    public function yiiBatchInsert($fields, $rows)
    {
        if(!$rows || !$fields) return false;
        $tableName = static::tableName();//AR表的表名
        //子模块model的DB属性，可能同默认的config/db.php不一样
        return static::getDb()->createCommand()->batchInsert($tableName, $fields, $rows)->execute();
    }

    //批量插入，example: ['a'=>[1, 2], 'b'=>[3, 4]] => {1, 3} + {2, 4}两条数据
    //$cross 笛卡尔积,['a'=>[1, 2], 'b'=>[1, 2, 3]], 批量插入{1,1}, {1, 2}, {1, 3}...2*3=6条记录
    public function batchInsert($data, $cross=false)
    {
        $fields = array_keys($data);
        if($cross) {
            $rows = $this->_dicaer($data);
        } else {
            $rows = $this->_batchFormat($data);
        }
        if(!$rows) return false;
        $tableName = static::tableName();//AR表的表名
        //子模块model的DB属性，可能同默认的config/db.php不一样
        return static::getDb()->createCommand()->batchInsert($tableName, $fields, $rows)->execute();
    }

    //批量插入，遇到unique key忽略错误
    public function batchInsertIgnore($data, $cross=false)
    {
        $fields = array_keys($data);
        if($cross) {
            $rows = $this->_dicaer($data);
        } else {
            $rows = $this->_batchFormat($data);
        }
        if(!$rows) return false;
        $tableName = static::tableName();//AR表的表名
        $values = [];
        $schema = static::getDb()->getSchema();
        foreach($rows as $row) {
            $vs = [];
            foreach($row as $v) {
                if (is_string($v)) {
                    $v = $schema->quoteValue($v);
                } elseif ($v === false) {
                    $v = 0;
                } elseif ($v === null) {
                    $v = 'NULL';
                }
                $vs[] = $v;
            }
            $values[] = '('.implode(',', $vs).')';
        }
        $sql  = "INSERT IGNORE INTO ".$tableName."(".implode(',', $fields).") VALUES ".implode(', ', $values);
        return static::getDb()->createCommand($sql)->execute();
    }

    //单条插入，存在则更新(mysql特有写法)
    //example: ['a'=>1, 'b'=>2], ['b'=>2]
    public function insertDuplicate($data, $updates)
    {
        //insert into table(a, b, c) values (1, 2, 3) on duplicate key update c = c + 1
        $tableName = static::tableName();
        $params = [];
        $sql = static::getDb()->queryBuilder->insert($tableName, $data, $params);
        if($updates) {
            $sql .= ' on duplicate key update ';
        }
        foreach ($updates as $column=>$value) {
            $sql .= $column.' = '.$column.' + '.$value.' ,';
        }
        $sql = rtrim($sql, ',');
        return static::getDb()->createCommand($sql)->bindValues($params)->execute();
    }

    /**
     * @api 单一条件批量更新
     * @author wyf
     * @date 2019/6/14
     * @param $argument
     * @param string $key_name
     * @return int
     * @throws Exception
     */
    public static function batchUpdate($argument, $key_name = 'id')
    {
        $sql = "UPDATE  " . static::tableName() . " SET ";
        foreach (current($argument) as $key => $value) {
            $sql .= "{$key} = CASE {$key_name} ";
            foreach ($argument as $id => $item) {
                $sql .= sprintf("WHEN %s THEN '%s' ", $id, $item[$key]);
            }
            $sql .= "END, ";
        }
        $sql = rtrim(trim($sql), ',');
        $ids = implode(',', array_keys($argument));
        $sql .= " WHERE {$key_name} IN ({$ids})";
        return static::getDb()->createCommand()->setSql($sql)->execute();
    }

    /**
     * @api 多个条件批量更新
     * @author wyf
     * @date 2019/6/14
     * @param $argument
     * @param array $key_name
     * @return int
     * @throws Exception
     */
    public static function batchUpdateValue($argument, $key_name=[])
    {
        $sql = "UPDATE  " . static::tableName() . " SET ";
        $sql = rtrim(trim($sql), ',');
        foreach ($argument as $key => $value) {
            $sql .= " {$key} = $value ,";
        }
        $sql = rtrim(trim($sql), ',');
        $where = '';
        foreach ($key_name as $k =>$v){
            $where .= '( ';
            foreach ($v as $id =>$name){
                $where .= "{$id} = $name and ";
            }
            $where = rtrim(trim($where), 'and');
            $where .= ") ";
            $where .= "or ";
        }
        $where = rtrim(trim($where), 'or');
        $sql .= " WHERE {$where}";
        return static::getDb()->createCommand()->setSql($sql)->execute();
    }

    /**
     * @param $data
     * @param $scenario
     * @return mixed
     * @throws MyException
     */
    public function validParamArr($data, $scenario)
    {
        if (!empty($data)) {
            $this->setScenario($scenario);
            $datas["data"] = $data;
            $this->load($datas, "data");
            if ($this->validate()) {
                return $data;
            } else {
                $errorMsg = array_values($this->errors);
                throw new MyException($errorMsg[0][0]);
            }
        } else {
            throw new MyException('未接受到有效数据');
        }
    }
}