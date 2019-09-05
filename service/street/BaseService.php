<?php
namespace service\street;
use yii\base\Model;

/**
 * User: ZQ
 * Date: 2019/9/4
 * Time: 11:27
 * For: ****
 */
class BaseService extends \service\BaseService
{
    public function returnIdName($data)
    {
        $arr = [];
        foreach ($data as $key => $value) {
            $arr[] = ["id" => $value['id'], "name" => $value['name']];
        }
        return $arr;
    }

    public function getError(Model $model)
    {
        $errors = array_values($model->getErrors());
        if(!empty($errors[0][0])) {
            return $errors[0][0];
        }
        return '网络异常';
    }

    public function returnIdNameToCommon($list)
    {
        $result = [];
        if($list){
            foreach ($list as $key =>$value){
                $a['id'] = $key;
                $a['name'] = $value;
                $result[] = $a;
            }
        }
        return $result;
    }

}