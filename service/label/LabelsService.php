<?php
/**
 * 标签service
 * @author yjh
 * @date 2018-07-05
 */
namespace service\label;

use app\models\PsCommunityModel;
use app\models\PsCommunityRoominfo;
use app\models\PsLabels;
use app\models\PsRoomLabel;
use app\models\PsRoomUser;
use app\models\PsRoomUserLabel;
use common\core\F;
use service\BaseService;
use service\rbac\OperateService;
use Yii;
use yii\db\Query;

Class LabelsService extends BaseService
{

    public $user_info;

    public function __construct($user_info = null)
    {
        $this->user_info = $user_info;
    }

    /**
     * 添加和更新服务
     * @author yjg
     * @param $type 1添加 2更新
     * @date 2018-07-05
     */
    public function labelAddUpdate($data, $type = 1)
    {
        if (empty($data['community_id']) || !PsCommunityModel::find()->where(['id' => $data['community_id']])->select('id')->one()) {
            return $this->failed('小区ID错误');
        }
        $label = $this->selectLabel($data['name'], $data['label_type'], $data['community_id']);
        if (empty($label)) {
            //判断操作
            if ($type == 1) {
                $id = null;
                $error = '系统错误，添加失败';
                $action = '添加标签';
            } else {
                $id = $data['id'];
                $error = 'ID不存在，修改失败';
                $action = '修改标签';
            }
            if ($this->addUpdate($data, $id)) {
                $this->writeLog($data['community_id'], $action, '标签名:' . $data['name'] . ' 类型:' . $data['label_type']);
                return $this->success();
            } else {
                return $this->failed($error);
            }
        } else {
            if (!empty($data['id']) && $data['id'] == $label->id) {
                return $this->success();
            }
            return $this->failed('该标签已存在');
        }

    }

    /**
     * 获取标签列表
     * @author yjg
     * @date 2018-07-05
     */
    public function labelList($data)
    {
        //条件处理
        $page = !empty($data['page']) ? $data['page'] : 1;
        $row = !empty($data['row']) ? $data['row'] : 10;
        $page = ($page - 1) * $row;
        $where['community_id'] = $data['community_id'];
        $where['label_type'] = !empty($data['label_type']) ? $data['label_type'] : null;
        $where = F::searchFilter($where);
        $like = !empty($data['name']) ? ['like', 'name', $data['name']] : '1=1';
        //查询
        $query = PsLabels::find()->where($where)->andWhere($like)->orderBy(['id' => SORT_DESC]);
        $countQuery = clone $query;
        $count = $countQuery->count();
        $models = $query->offset($page)->limit($row)->asArray()->all();
        if (!empty($models)) {
            $models = PsLabels::handleData($models);
        }
        $arr = ["totals" => $count, "list" => $models];
        return $arr;
    }

    /**
     * 删除标签列表
     * @author yjg
     * @date 2018-07-05
     */
    public function labelDelete($id)
    {
        $label = PsLabels::findOne($id);
        if (empty($label)) {
            return $this->failed('该标签不存在');
        }
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            $label->delete();
            $this->deleteList($label->label_type, $label->id);
            $this->writeLog($label->community_id, '删除标签', '标签名:' . $label->name . ' 类型:' . $label->label_type);
            $trans->commit();
        } catch (\Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
        return $this->success();
    }

    //添加表关联数据 $id小区住户表/小区用户 +表 $type 1房屋表 2住户表 $field 2用关联id删除 3用code删除
    public function addRelation($condition, array $label_id, $type = 1, $field = true)
    {
        if ($this->checkLabel($label_id, $type)) {
            $trans = Yii::$app->getDb()->beginTransaction();
            try {
                switch ($type) {
                    case 1:
                        $model = 'ps_room_label';
                        $relation = 'room_id';
                        break;
                    case 2:
                        $model = 'ps_room_user_label';
                        $relation = 'room_user_id';
                        break;
                }
                if ($field) {
                    $relation_id = 2;
                } else {
                    $relation = 'code';
                    $relation_id = 3;
                }
                $field ? 2 : 3;
                $this->deleteList($type, $condition, $relation_id);
                foreach ($label_id as $v) {
                    $insert[] = ['label_id' => $v, $relation => $condition, 'created_at' => time()];
                }
                Yii::$app->db->createCommand()->batchInsert($model, ['label_id', $relation, 'created_at'], $insert)->execute();
                $trans->commit();
            } catch (\Exception $e) {
                $trans->rollBack();
                return $this->failed($e->getMessage());
            }
            return true;
        } else {
            return false;
        }
    }


    //获取分类数据
    public function getTypeList($data)
    {
        if (!empty($data['label_type'])) {
            $arr['label_type'] = $data['label_type'];
        }
        $arr['community_id'] = $data['community_id'];
        return PsLabels::find()->select(['id', 'name'])->where($arr)->asArray()->all();
    }


    //删除关联标签 $label_type 1是住户表 2是用户表  $type 1 使用label_id删除  2使用mid中间id删除 3使用code删除
    public function deleteList($label_type, $condition, $type = 1)
    {
        //条件处理
        switch ($type) {
            case 1:
                $where = ['label_id' => $condition];
                break;
            case 2:
                //区分操作哪种表
                if ($label_type == 1) {
                    $where = ['room_id' => (int)$condition];
                } else {
                    $where = ['room_user_id' => (int)$condition];
                }
                break;
            case 3:
                $where = ['code' => $condition];
                break;
        }
        switch ($label_type) {
            case 1:
                $result = PsRoomLabel::deleteAll($where);
                break;
            case 2:
                $result = PsRoomUserLabel::deleteAll($where);
                break;
        }
        return $result;
    }

    //记录日志表操作
    public function writeLog($communityId, $operate_type, $content)
    {
        //保存日志
        $operate = [
            "community_id" => $communityId,
            "operate_menu" => "标签管理",
            "operate_type" => $operate_type,
            "operate_content" => $content,
        ];
        OperateService::addComm($this->user_info, $operate);
    }

    //查询标签是否存在
    public function selectLabel($name, $type, $community_id)
    {
        return PsLabels::find()->where(['name' => $name, 'label_type' => $type, 'community_id' => $community_id])->one();
    }

    //添加或者修改标签
    public function addUpdate($data, $id = null)
    {
        try {
            $trans = Yii::$app->getDb()->beginTransaction();
            //添加
            if ($id == null) {
                $label = new PsLabels;
                $label->created_at = time();
            } else {
                //修改
                $label = $this->checkLabel($id);
                if (empty($label)) {
                    return false;
                } else {
                    if ($label->label_type != $data['label_type']) {
                        $this->deleteList($label->label_type, $label->id, 1);
                    }
                }
            }
            $label->name = $data['name'];
            $label->label_type = $data['label_type'];
            $label->community_id = $data['community_id'];
            $label->updated_at = time();
            $label->save();
            $trans->commit();
            return $label;
        } catch (\Exception $e) {
            $trans->rollBack();
            return false;
        }
    }

    //验证标签
    public function checkLabel($label_id, $type = null)
    {
        if (is_array($label_id)) {
            foreach ($label_id as $v) {
                $label = PsLabels::find()->where(['id' => $v, 'label_type' => $type])->asArray()->one();
                if (!$label) {
                    return false;
                }
                $data[] = $label;
            }
            return $data;
        } else {
            $label = PsLabels::findOne($label_id);
            if (!$label) {
                return false;
            }
            return $label;
        }
    }

    //生成一条唯一code
    public function getCode($type = 1)
    {
        $code = rand(1, 99999) . '' . time();
        switch ($type) {
            case 1:
                $result = PsCommunityRoominfo::find()->where(['label_code' => $code])->one();
                $label = PsRoomLabel::find()->where(['code' => $code])->one();
                break;
            case 2:
                $result = PsRoomUserLabel::find()->where(['code' => $code])->one();
                $label = PsRoomUser::find()->where(['label_code' => $code])->one();
                break;
        }
        if (empty($result) && empty($label)) {
            return $code;
        }
        $this->getCode($type);
    }
}