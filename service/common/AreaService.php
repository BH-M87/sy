<?php
namespace service\common;

use app\models\PsAreaAli;
use common\core\PsCommon;
use service\BaseService;
use Yii;

class AreaService extends BaseService
{

    /**
     * 根据code获取名称
     */
    public function getNameByCode($code)
    {
        return PsAreaAli::find()->select('areaName')
            ->where(['areaCode' => $code])
            ->scalar();
    }

    /**
     * 批量获取
     * @param $codes
     * @return array ['11'=>'xx市', '1222'=>'xxx县']
     */
    public function getNamesByCodes($codes)
    {
        $data = PsAreaAli::find()->select('areaName, areaCode')
            ->where(['areaCode' => $codes])
            ->asArray()->all();
        return array_column($data, 'areaName', 'areaCode');
    }

    /**
     * 获取一条完整的数据
     * @param $code
     */
    public function load($code)
    {
        return PsAreaAli::find()->where(['areaCode' => $code])->asArray()->one();
    }

    /**
     * 根据名称获取code
     * @param $name
     * @return false|null|string
     */
    public function getCodeByName($name, $type = null, $parentId = null)
    {
        return PsAreaAli::find()->select('areaCode')
            ->where(['areaName' => $name])
            ->andFilterWhere(['areaType' => $type, 'areaParentId' => $parentId])
            ->scalar();
    }

    /**
     * 获取所有的省市区数据
     */
    public function getCacheArea()
    {
        $redis = Yii::$app->redis;
        if ($redis->get('area_data')) {
            $areaData = $redis->get('area_data');
        } else {
            $typefile = dirname(__DIR__) . '/common/cacheFile/area.text';
            $myfile = fopen($typefile, "r") or die("Unable to open file!"); //读取文件操作
            $areaData = fread($myfile, filesize($typefile));//读取文件内容
            fclose($myfile);
            $redis->set('area_data', $areaData);
        }
        return $areaData;
    }

    /**
     * 获取所有的省市区数据-供ajax三级联动使用
     */
    public function getCacheAreaAjax()
    {
        $redis = Yii::$app->redis;
        if ($redis->get('area_ajax_data')) {
            $areaData = $redis->get('area_ajax_data');
        } else {
            $typefile = dirname(__DIR__) . '/common/cacheFile/areaAjax.text';
            $myfile = fopen($typefile, "r") or die("Unable to open file!"); //读取文件操作
            $areaData = fread($myfile, filesize($typefile));//读取文件内容
            fclose($myfile);
            $redis->set('area_data', $areaData);
        }
        return $areaData;
    }
}