<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/23
 * Time: 11:19
 * Desc: 活动
 */
namespace app\models;

class VtPlayer extends BaseModel
{


    public static function tableName()
    {
        return 'vt_player';
    }

    public function rules()
    {
        return [

            [['activity_id','name','code'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['id','activity_id','name'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['edit']],
            [['id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['detail','del']],
            [["id",  'activity_id', 'group_id', 'view_num', 'vote_num','create_at', 'update_at'], 'integer'],
            [['id', 'code', 'activity_id', 'group_id', 'name','img','content'], 'trim'],
            [['content'], 'string'],
            [['code','name'], 'string', "max" => 20],
            [['img'], 'string', "max" => 255],
            [["activity_id","group_id"], 'activityVerification', 'on' => ["add","edit"]], //活动验证
            [["id","activity_id","name"], 'nameVerification', 'on' => ["add","edit"]], //选手名称唯一
            [["id","activity_id","code"], 'codeVerification', 'on' => ["add","edit"]], //选手名称唯一
            [['id'], 'dataInfo', 'on' => ["edit","detail",'del']], //选手是否存在
            [["create_at", 'update_at'], "default", 'value' => time(), 'on' => ['add']],
            [["group_id"], "default", 'value' => 0, 'on' => ['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
              'id'              => '选手id',
              'code'            => '选手编号',
              'activity_id'     => '活动id',
              'group_id'        => '分组ID',
              'name'            => '选手名称',
              'img'             => '选手主图',
              'content'         => '选手内容',
              'view_num'        => '浏览量',
              'vote_num'        => '投票量',
              'create_at'       => '创建时间',
              'update_at'       => '修改时间',
        ];
    }

    /***
     * 新增
     * @return true|false
     */
    public function saveData()
    {
        return $this->save();
    }

    /***
     * 修改
     * @return bool
     */
    public function edit($param)
    {
        $param['update_at'] = time();
        return self::updateAll($param, ['id' => $param['id']]);
    }

    /*
     * 修改
     */
    public function delAll($param){
        return self::deleteAll(['id'=>$param['id']]);
    }


    /*
     * 选手是否存在
     */
    public function dataInfo($attribute){
        if(!empty($this->id)){
            $res = self::find()->select(['id'])->where(['=','id',$this->id])->asArray()->one();
            if(empty($res)){
                return $this->addError($attribute, "该选手不存在");
            }
        }
    }

    //选手名称是否存在
    public function nameVerification($attribute){
        if(!empty($this->name)&&!empty($this->activity_id)){
            $model = self::find()->select(['id'])->where(['=','name',$this->name])->andWhere(['=','activity_id',$this->activity_id]);
            if(!empty($this->id)){
                $model->andWhere(['!=','id',$this->id]);
            }
            $res = $model->asArray()->one();
            if(!empty($res)){
                return $this->addError($attribute, "该选手已存在");
            }
        }
    }

    /*
     * code 唯一
     */
    public function codeVerification($attribute){
        if(!empty($this->code)&&!empty($this->activity_id)){
            $model = self::find()->select(['id'])->where(['=','code',$this->code])->andWhere(['=','activity_id',$this->activity_id]);
            if(!empty($this->id)){
                $model->andWhere(["!=","id",$this->id]);
            }
            $res = $model->asArray()->one();
            if(!empty($res)){
                return $this->addError($attribute, "该选手编号已存在");
            }
        }
    }

    //活动验证
    public function activityVerification($attribute){
        if(!empty($this->activity_id)){
//            $nowTime = time();
            $activityRes = VtActivity::find()->select(['id','start_at'])->where(['=','id',$this->activity_id])->asArray()->one();
            if(empty($activityRes)){
                return $this->addError($attribute, "活动不存在");
            }
//            if($activityRes['start_at']<$nowTime){
//                return $this->addError($attribute, "该活动已开始或结束，不能编辑选手");
//            }
            if(!empty($this->group_id)){
                $groupRes = VtActivityGroup::find()->select(['id'])->where(['=','id',$this->group_id])->andWhere(['=','activity_id',$this->activity_id])->asArray()->one();
                if(empty($groupRes)){
                    return $this->addError($attribute, "活动对应分组不存在");
                }
            }
        }
    }

    //选手详情
    public function getDetail($params){
        $fields = ['p.id','p.code','p.activity_id','p.group_id','p.name','p.img','p.content','v.name as activity_name','IFNULL(g.name,"") as group_name'];
        $model = self::find()->alias("p")->select($fields)
                        ->leftJoin(['v'=>VtActivity::tableName()],'v.id=p.activity_id')
                        ->leftJoin(['g'=>VtActivityGroup::tableName()],'g.id=p.group_id')
                        ->where(['=','p.id',$params['id']]);
        return $model->asArray()->one();
    }

    //选手列表
    public function getList($params){
        $fields = [
                    'p.id','p.code','p.activity_id','p.group_id','p.name','p.img','p.view_num','p.vote_num','v.name as activity_name',
                    'IFNULL(g.name,"") as group_name','p.img','p.create_at'
        ];
        $model = self::find()->alias("p")->select($fields)
            ->leftJoin(['v'=>VtActivity::tableName()],'v.id=p.activity_id')
            ->leftJoin(['g'=>VtActivityGroup::tableName()],'g.id=p.group_id')
            ->where(1);

        if(!empty($params['activity_id'])){
            $model->andWhere(['=','p.activity_id',$params['activity_id']]);
        }
        if(!empty($params['group_id'])){
            $model->andWhere(['=','p.group_id',$params['group_id']]);
        }
        if(!empty($params['code'])){
            $model->andWhere(['like','p.code',$params['code']]);
        }
        if(!empty($params['name'])){
            $model->andWhere(['like','p.name',$params['name']]);
        }

        $count = $model->count();
        if(!empty($params['page'])||!empty($params['pageSize'])){
            $page = !empty($params['page'])?intval($params['page']):1;
            $pageSize = !empty($params['pageSize'])?intval($params['pageSize']):10;
            $offset = ($page-1)*$pageSize;
            $model->offset($offset)->limit($pageSize);
        }
        $model->orderBy(["p.id"=>SORT_DESC]);
        $result = $model->asArray()->all();
        return [
            'list'=>$result,
            'totals'=>$count
        ];
    }
}