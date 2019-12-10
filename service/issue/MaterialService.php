<?php
/**
 * 耗材相关服务
 * User: fengwenchao
 * Date: 2019/8/13
 * Time: 16:01
 */

namespace service\issue;


use app\models\PsRepairMaterials;
use app\models\PsRepairMaterialsCate;
use common\core\PsCommon;
use service\BaseService;
use service\rbac\OperateService;
use service\property_basic\JavaService;
use yii\db\Query;
use Yii;

class MaterialService extends BaseService
{
    public static $_fee_type = [
        '1' => '材料费用',
        '2' => '人工费用'
    ];

    public static $_material_type = [
            '1' => '/米',
            '2' => '/卷',
            '3' => '/个',
            '4' => '/根',
            '5' => '/平方米',
            '6' => '/立方米'
    ];
    public static $_people_type = ['1'=>'/次','2'=>'/小时'];

    public function getCommon()
    {
        $comm = [
            'fee_type' => PsCommon::returnKeyValue(self::$_fee_type),
            'material' => PsCommon::returnKeyValue(self::$_material_type),
            'people' => PsCommon::returnKeyValue(self::$_people_type)
        ];
        return $comm;
    }

    //耗材列表
    public function getList($params)
    {
        // 获得所有小区
        $javaResult = JavaService::service()->communityNameList(['token' => $params['token']]);
        $communityIds = !empty($javaResult['list']) ? array_column($javaResult['list'], 'key') : [];
        $javaResult = !empty($javaResult['list']) ? array_column($javaResult['list'], 'name', 'key') : [];
        $communityId = !empty($params['community_id']) ? $params['community_id'] : $communityIds;
        $query = new Query();
        $query->from('ps_repair_materials A')
            ->select('A.*')
            ->where("1=1")
            ->andWhere(['A.community_id' => $communityId]);
        $cate_id = PsCommon::get($params, 'cate_id');
        $name = PsCommon::get($params, 'name');
        if ($cate_id) {
            $query->andFilterWhere(['cate_id' => $cate_id]);
        }
        if ($name) {
            $query->andFilterWhere(['like','name',$name]);
        }

        $re['totals'] = $query->count();
        $query->orderBy('A.created_at desc');
        $offset = ($params['page'] - 1) * $params['rows'];
        $query->offset($offset)->limit($params['rows']);
        $command = $query->createCommand();
        $models = $command->queryAll();
        foreach ($models as $key => $val) {
            $models[$key]["price_unit_desc"] = isset(self::$_material_type[$val['price_unit']]) ? self::$_material_type[$val['price_unit']] : '未知';
            if($val['cate_id']==2){
                $models[$key]["price_unit_desc"] = isset(self::$_people_type[$val['price_unit']]) ? self::$_people_type[$val['price_unit']] : '未知';
            }
            $models[$key]["cate_name"] = isset(self::$_fee_type[$val['cate_id']]) ? self::$_fee_type[$val['cate_id']] : '未知';
            $models[$key]["community_name"] = !empty($val['community_id']) ? $javaResult[$val['community_id']] : '';
            $models[$key]["created_at"] = $val['created_at'] ? date("Y-m-d H:i", $val['created_at']) : '';
        }
        $re['list'] = $models;
        return $re;
    }

    public function getMaterialByName($name, $communityId)
    {
        return PsRepairMaterials::find()
            ->where(['name' => $name, 'community_id' => $communityId])
            ->asArray()
            ->one();
    }

    //耗材新增
    public function add($data, $userInfo = [])
    {
        $materialArr = [];
        $params = [
            "num" => $data["num"] ? $data["num"] : 0,
            "price_unit" => $data["price_unit"],
            "price" => $data["price"],
            "name" => $data["name"],
            "cate_id" => $data["cate_id"],
            "community_id" => $data["community_id"],
            "created_at" => time(),
        ];
        array_push($materialArr, $params);
        $operate = [
            "community_id" => $params['community_id'],
            "operate_menu" => "耗材管理",
            "operate_type" => "新增耗材",
            "operate_content" => '材料名称' . $data["name"] . '-单价：' . $data['price'] . '-数量:' . $data['num'],
        ];
        OperateService::addComm($userInfo, $operate);
        return Yii::$app->db->createCommand()->batchInsert('ps_repair_materials',["num","price_unit","price","name","cate_id","community_id","created_at",],$materialArr)->execute();
    }

    //耗材编辑
    public function edit($params, $userInfo = [])
    {
        $model = PsRepairMaterials::find()
            ->where(['id' => $params['material_id']])
            ->one();
        if (!$model) {
            return "耗材不存在";
        }

        //重复判断
        $sameNameModel = PsRepairMaterials::find()
            ->where(['name' => $params['name'], 'community_id' => $params['community_id']])
            ->andWhere(['!=', 'id', $params['material_id']])
            ->asArray()
            ->one();
        if ($sameNameModel) {
            return "材料名重复";
        }
        $model->setAttributes($params);
        if (!$model->save()) {
            return "编辑失败";
        }

        $operate = [
            "community_id" => $params['community_id'],
            "operate_menu" => "耗材管理",
            "operate_type" => "编辑耗材",
            "operate_content" => '材料名称' . $params['name'] . '-单价：' . $params['name'] . '-数量:' . $params['num'],
        ];
        OperateService::addComm($userInfo, $operate);
        return $model->id;
    }

    //耗材删除
    public function delete($params, $userInfo = [])
    {
        $model = PsRepairMaterials::find()
            ->where(['id' => $params['material_id']])
            ->one();
        if (!$model) {
            return "耗材不存在";
        }
        if ($model->delete()) {
            $operate = [
                "community_id" => $model['community_id'],
                "operate_menu" => "耗材管理",
                "operate_type" => "删除耗材",
                "operate_content" => '材料名称' . $model["name"],
            ];
            OperateService::addComm($userInfo, $operate);
            return $params['material_id'];
        }
        return "删除失败";
    }

    //耗材详情
    public function show($params)
    {
        $model = PsRepairMaterials::find()
            ->where(['id' => $params['material_id']])
            ->asArray()
            ->one();
        if (!$model) {
            return "耗材不存在";
        }
        $model["price_unit_desc"] = isset(self::$_material_type[$model['price_unit']]) ? self::$_material_type[$model['price_unit']] : '未知';
        if($model['cate_id']==2){
            $model["price_unit_desc"] = isset(self::$_people_type[$model['price_unit']]) ? self::$_people_type[$model['price_unit']] : '未知';
        }
        $model["cate_name"] = isset(self::$_fee_type[$model['cate_id']]) ? self::$_fee_type[$model['cate_id']] : '未知';
        $model["created_at"] = $model['created_at'] ? date("Y-m-d H:i", $model['created_at']) : '';
        return $model;
    }

    //根据小区查询耗材分类
    public function getListByCommunityId($communityId)
    {
        $cates = PsRepairMaterialsCate::find()
            ->select(['id as material_type_id', 'name as material_type_name'])
            ->where(['cate_type' => 1])
            ->orWhere(['community_id' => $communityId])
            ->asArray()
            ->all();
        if ($cates) {
            foreach ($cates as $k => $v) {
                //查询耗材列表
                $materials = PsRepairMaterials::find()
                    ->select(['id', 'name', 'price', 'price_unit'])
                    ->where(['cate_id' => $v['material_type_id']])
                    ->andWhere(['community_id' => $communityId])
                    ->andWhere(['status' => 1])
                    ->asArray()
                    ->all();
                foreach ($materials as $kk => $vv) {
                    $models[$kk]["price_unit"] = isset(self::$_material_type[$vv['price_unit']]) ? self::$_material_type[$vv['price_unit']] : '未知';
                    if($vv['cate_id']==2){
                        $models[$kk]["price_unit"] = isset(self::$_people_type[$vv['price_unit']]) ? self::$_people_type[$vv['price_unit']] : '未知';
                    }
                }
                $cates[$k]['material_detail'] = $materials;
            }
        }
        $re['list'] = $cates;
        $re['totals'] = count($cates);
        return $re;
    }
}