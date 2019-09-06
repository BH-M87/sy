<?php
namespace service\property_basic;

use Yii;
use yii\base\Exception;

use service\BaseService;
use service\rbac\OperateService;

use common\core\PsCommon;

use app\models\PsProclaim;

class ProclaimService extends BaseService
{
    // 公告 新增
    public function add($p, $scenario = 'add')
    {
        return $this->_saveProclaim($p, $scenario);
    }

    // 公告 编辑
    public function edit($p, $scenario = 'edit')
    {
        return $this->_saveProclaim($p, $scenario);
    }

    // 新增编辑 公告
    public function _saveProclaim($p, $scenario)
    {
        $m = new PsProclaim();

        if (!empty($p['id'])) {
            $m = self::getOne($p);

            if ($m->is_show == 2) {
                return $this->failed("数据已上线不可编辑");
            }
        }
        
        $data = PsCommon::validParamArr($m, $p, $scenario);

        if (empty($data['status'])) {
            return $this->failed($data['errorMsg']);
        }

        if ($p['is_top'] == 2) {
            $m->top_at = time();
        }

        if ($m->save()) {
            return $this->success($m->id);
        }
    }

    // 公告列表
    public function list($p)
    {
        $m = PsProclaim::getList($p);

        return $this->success($m);
    }

    // 公告是否显示
    public function editShow($p)
    {
        $m = self::getOne($p);

        if ($m->is_show == 1) {
            $m->is_show = 2; // 置顶
            $m->show_at = time();
        } else {
            $m->is_show = 1; // 不置顶
            $m->show_at = 0;
        }

        if ($m->save()) {
            return $this->success();
        }
        return $this->failed();
    }

    // 公告是否置顶
    public function editTop($p)
    {
        $m = self::getOne($p);

        if ($m->is_top == 1) {
            $m->is_top = 2; // 置顶
            $m->top_at = time();
        } else {
            $m->is_top = 1; // 不置顶
            $m->top_at = 0;
        }

        if ($m->save()) {
            return $this->success();
        }
        return $this->failed();
    }

    // 公告详情
    public function show($p)
    {
        $m = self::getOne($p)->toArray();

        $m['create_at'] = !empty($m['create_at']) ? date("Y-m-d H:i", $m['create_at']) : '';
        $m['show_at'] = !empty($m['show_at']) ? date("Y-m-d H:i", $m['show_at']) : '';
        $m['top_at'] = !empty($m['top_at']) ? date("Y-m-d H:i", $m['top_at']) : '';
        $m['proclaim_type_desc'] = PsProclaim::$proclaim_type[$m['proclaim_type']];
        $m['proclaim_cate_desc'] = PsProclaim::$proclaim_cate[$m['proclaim_cate']];
        $m['is_top_desc'] = $m['is_top'] == 2 ? '是' : '否';
        $m['receive'] = [];

        return $this->success($m);
    }

    // 公告删除
    public function del($p)
    {
        $m = self::getOne($p);

        if ($m->is_show == 2) {
            return $this->failed("数据已上线不可删除");
        }

        if ($m->delete()) {
            return $this->success();
        }   
    }

    // 获取单条数据
    public function getOne($p)
    {
        $m = PsProclaim::find()->where(['id' => $p['id']])
            ->andFilterWhere(['=', 'community_id', $p['community_id']])->one();
        if (!$m) {
            return $this->failed("数据不存在");
        }
        return $m;
    }
}