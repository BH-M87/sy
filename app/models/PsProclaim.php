<?php
namespace app\models;

use common\core\F;

class PsProclaim extends BaseModel
{
    public static $proclaim_type = ['1' => '通知', '2' => '新闻', '3' => '公告', '4' => '社区公约', '5' => '三务公开', 
        '6' => '政策法规', '7' => '网上办事', '8' => '保障信息', '9' => '其他信息'];
    public static $proclaim_cate = ['1' => '文字', '2' => '新闻', '3' => '图文新闻'];

    public static function tableName()
    {
        return 'ps_proclaim';
    }

    public function rules()
    {
        return [
            [['title', 'proclaim_type', 'proclaim_cate', 'operator_id', 'operator_name'], 'required', 'message' => '{attribute}必填'],
            [['community_id', 'proclaim_type', 'proclaim_cate', 'operator_id', 'organization_type', 'organization_id', 'top_at'], 'integer', 'message'=> '{attribute}不是数字'],
            [['is_top'], 'in', 'range' => [1, 2],'message' => '{attribute}不正确'],
            [['is_show'], 'in', 'range' => [1, 2],'message' => '{attribute}不正确'],
            ['img_url', 'string', 'length' => [1, 100], 'message' => '{attribute}长度不正确'],
            [['title'], 'string', 'max' => 30],
            [['content'], 'safe'],
            [['proclaim_cate', 'img_url', 'content'], 'typeVerify'],

            [['id'], 'required', 'on' => ['edit', 'streetEdit'], 'message' => '{attribute}必填'],

            [['create_at'], 'default', 'on' => ['add', 'streetAdd'], 'value' => time()],
        ];
    }

    // 根据类型必填验证 新闻和图片新闻-图片必填 文字和图片新闻-内容必填
    public function typeVerify()
    {   
        if ($this->proclaim_cate != 1 && empty($this->img_url)) {
            $this->addError('', '图片不能为空');
        }

        if ($this->proclaim_cate != 2 && empty($this->content)) {
            $this->addError('', '内容不能为空');
        }
    }

    public function attributeLabels()
    {
        return [
            'id' => '公告ID',
            'organization_type' => '所属组织类型',
            'organization_id' => '所属组织ID',
            'community_id' => '小区ID',
            'title' => '标题',
            'content' => '内容',
            'proclaim_type' => '公告类型',
            'proclaim_cate' => '内容分类',
            'img_url' => '图片',
            'is_top' => '是否置顶',
            'top_at' => '置顶时间',
            'is_show' => '是否显示',
            'operator_id' => '操作人ID',
            'operator_name' => '操作人姓名',
            'create_at' => '添加时间'
        ];
    }

    // 获取单条数据
    public static function getOne($p)
    {
        $m = PsProclaim::find()->where(['id' => $p['id']])
            ->andFilterWhere(['=', 'community_id', $p['community_id']])->one();
        
        return $m;
    }

    // 获取列表
    public static function getList($p)
    {
        $page = $p['page'] ?? 1;
        $rows = $p['rows'] ?? 10;

        $p['start_date'] = !empty($p['start_date']) ? strtotime($p['start_date']) : null;
        $p['end_date'] = !empty($p['end_date']) ? strtotime($p['end_date'].' 23:59:59') : null;

        $m = self::find()->alias('A')->select('distinct(A.id), A.*')
            ->leftJoin('ps_proclaim_community B', 'A.id = B.proclaim_id');

        if (!empty($p['small'])) { // 小程序
            $m->filterWhere(['=', 'B.community_id', $p['community_id']])
                ->orFilterWhere(['=', 'A.community_id', $p['community_id']]);
        } else {
            $m->andFilterWhere(['=', 'A.community_id', $p['community_id']]);
        }

        $m->andFilterWhere(['=', 'proclaim_type', $p['proclaim_type']])
            ->andFilterWhere(['=', 'organization_id', $p['organization_id']])
            ->andFilterWhere(['=', 'is_show', $p['is_show']])
            ->andFilterWhere(['like', 'title', $p['title']])
            ->andFilterWhere(['>=', 'create_at', $p['start_date']])
            ->andFilterWhere(['<=', 'create_at', $p['end_date']]);

        $totals = count($m->asArray()->all());
        if ($totals > 0) {
            if (!empty($p['small'])) { // 小程序的列表
                $list = $m->orderBy('is_top desc, top_at desc, show_at desc')->offset(($page - 1) * $rows)->limit($rows)->asArray()->all();
            } else {
                $list = $m->orderBy('create_at desc')->offset(($page - 1) * $rows)->limit($rows)->asArray()->all();
            }

            self::afterList($list);
        }

        return ['list' => $list ?? [], 'totals' => $totals];
    }

    // 列表结果格式化
    public static function afterList(&$list)
    {
        foreach ($list as &$v) {
            $v['img_url'] = F::ossImagePath($v['img_url']);
            $v['show_at'] = !empty($v['show_at']) ? date('Y-m-d H:i', $v['show_at']) : '';
            $v['create_at'] = date('Y-m-d H:i', $v['create_at']);
            $v['proclaim_type_desc'] = self::$proclaim_type[$v['proclaim_type']];
            $v['proclaim_cate_desc'] = self::$proclaim_cate[$v['proclaim_cate']];
            $v['receive'] = PsProclaimCommunity::find()->Alias('A')
                ->select('B.name as xqOrgName, B.event_community_no as xqOrgCode')
                ->leftJoin('ps_community B', 'B.id = A.community_id')
                ->where(['proclaim_id' => $v['id']])->asArray()->all();
        }
    }
}
