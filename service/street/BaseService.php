<?php
namespace service\street;
use common\core\F;
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

    /**
     * 根据默认的数据，将其转换成id-name格式的数组
     * 原始数据格式：$type_info = ['1' => '常规任务', '2' => '指令任务', '3' => '工作日志'];
     * @param $list
     * @return array
     */
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

    public function getIdByName($arrayParams,$name)
    {
        foreach ($arrayParams as $key => $value) {
            if ($name == $value['name']) {
                return $value['id'];
            }
        }
        return false;
    }

    /**
     * 根据多个文件字符串获取完整的的文件地址数组
     * @param $keyString 例如"1.doc,2.doc"
     * @param string $delimiter
     * @return array
     */
    public function getOssUrlByKey($keyString,$delimiter =',')
    {
        $url = [];
        if($keyString){
            $array = explode($delimiter,$keyString);
            foreach ($array as $key =>$value){
                $oss['key'] = $value;
                $oss['value'] = F::getOssImagePath($value);
                $url[] = $oss;
            }
        }
        return $url;

    }

}