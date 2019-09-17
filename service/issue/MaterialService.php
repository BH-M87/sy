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
use yii\db\Query;
use Yii;

class MaterialService extends BaseService
{
    public static $_fee_type = [
        '1' => '材料费用',
        '2' => '人工费用'
    ];

    public static $_unit_type = [
        '1' => '/米',
        '2' => '/卷',
        '3' => '/个',
        '4' => '/次'
    ];

    public function getCommon()
    {
        $comm = [
            'fee_type' => PsCommon::returnKeyValue(self::$_fee_type),
            'unit_type' => PsCommon::returnKeyValue(self::$_unit_type)
        ];
        return $comm;
    }

    //耗材列表
    public function getList($params)
    {
        $query = new Query();
        $query->from('ps_repair_materials A')
            ->select('A.*')
            ->where("1=1");
        if ($params['community_id']) {
            $query->andWhere(['A.community_id' => $params['community_id']]);
        }
        $re['totals'] = $query->count();
        $query->orderBy('A.created_at desc');
        $offset = ($params['page'] - 1) * $params['rows'];
        $query->offset($offset)->limit($params['rows']);
        $command = $query->createCommand();
        $models = $command->queryAll();
        foreach ($models as $key => $val) {
            $models[$key]["price_unit_desc"] = isset(self::$_unit_type[$val['price_unit']]) ? self::$_unit_type[$val['price_unit']] : '未知';
            $models[$key]["cate_name"] = isset(self::$_fee_type[$val['cate_id']]) ? self::$_fee_type[$val['cate_id']] : '未知';
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
    public function add($params, $userInfo = [])
    {
        $materialArr = [];
        foreach ($params["list"] as $key => $data) {
            $params = [
                "num" => $data["num"] ? $data["num"] : 0,
                "price_unit" => $data["price_unit"],
                "price" => $data["price"],
                "name" => $data["name"],
                "cate_id" => $data["cate_id"],
                "community_id" => $params["community_id"],
                "created_at" => time(),
            ];
            array_push($materialArr, $params);
            $operate = [
                "community_id" =>$params['community_id'],
                "operate_menu" => "耗材管理",
                "operate_type" => "新增耗材",
                "operate_content" => '材料名称'.$data["name"].'-单价：'.$data['price'].'-数量:'.$data['num'],
            ];
            OperateService::addComm($userInfo, $operate);
        }
        return Yii::$app->db->createCommand()->batchInsert('ps_repair_materials',
            [
                "num",
                "price_unit",
                "price",
                "name",
                "cate_id",
                "community_id",
                "created_at",
            ],
            $materialArr
        )->execute();
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
            "operate_content" => '材料名称'.$params['name'].'-单价：'.$params['name'].'-数量:'.$params['num'],
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
                "operate_content" => '材料名称'.$model["name"],
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
        $model["price_unit_desc"] = isset(self::$_unit_type[$model['price_unit']]) ? self::$_unit_type[$model['price_unit']] : '未知';
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
                    $materials[$kk]['price_unit'] = self::$_unit_type[$vv['price_unit']];
                }
                $cates[$k]['material_detail'] = $materials;
            }
        }
        $re['list'] = $cates;
        $re['totals'] = count($cates);
        return $re;
    }
}