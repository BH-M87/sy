<?php
/**
 * 党员接口相关服务
 * User: wenchao.feng
 * Date: 2019/9/4
 * Time: 18:23
 */

namespace service\street;


use app\models\StCommunist;
use app\models\StStation;
use common\core\F;
use common\core\PsCommon;
use common\MyException;
use service\common\ExcelService;

class CommunistService extends BaseService
{
    public $_types = [
        1 => ['id' => 1, 'name' => '在职党员'],
        2 => ['id' => 2, 'name' => '在册党员'],
        3 => ['id' => 3, 'name' => '发展党员'],
        4 => ['id' => 4, 'name' => '其他'],
    ];

    public $_sex = [
        1 => ['id' => 1, 'name' => '男'],
        2 => ['id' => 2, 'name' => '女']
    ];

    public function getCommon()
    {
        $comm = [
            'type' => $this->returnIdName($this->_types)
        ];
        return $comm;
    }

    //列表
    public function getList($page, $rows, $params)
    {
        $query = StCommunist::find()
            ->alias('sc')
            ->leftJoin('st_station st', 'sc.station_id = st.id')
            ->where(['organization_type' => $params['organization_type'], 'organization_id' => $params['organization_id']]);
        if (!empty($params['name'])) {
            $query->andWhere(['like', 'sc.name', $params['name']]);
        }
        if (!empty($params['branch'])) {
            $query->andWhere(['like', 'sc.branch', $params['branch']]);
        }
        if (!empty($params['type'])) {
            $query->andWhere(['sc.type' => $params['type']]);
        }
        $re['totals'] = $query->select('id')->count();
        $communistList = $query->select('sc.id, sc.name, sc.mobile, sc.sex, sc.birth_time, sc.join_party_time,
         sc.formal_time, sc.branch, sc.job, sc.type, sc.station_id, sc.is_authentication, st.station')
            ->offset((($page - 1) * $rows))
            ->limit($rows)
            ->orderBy('id desc')
            ->asArray()
            ->all();
        foreach ($communistList as $k => $v) {
            $communistList[$k]['mobile'] = F::processMobile($v['mobile']);
            $communistList[$k]['sex_info'] = $this->_sex[$v['sex']];
            $communistList[$k]['birth_time'] = $v['birth_time'] ? date("Y-m-d", $v['birth_time']) : '';
            $communistList[$k]['join_party_time'] = $v['join_party_time'] ? date("Y-m-d", $v['join_party_time']) : '';
            $communistList[$k]['formal_time'] = $v['formal_time'] ? date("Y-m-d", $v['formal_time']) : '';
            $communistList[$k]['type_info'] = $this->_types[$v['type']];
            $communistList[$k]['station_info'] = [
                'id' => $v['station_id'],
                'name' => $v['station']
            ];
            unset($communistList[$k]['type']);
            unset($communistList[$k]['station']);
            unset($communistList[$k]['station_id']);
        }
        $re['list'] = $communistList;
        return $re;
    }

    //新增
    public function add($params, $userInfo = [])
    {
        $stationId = F::value($params, 'station_id', 0);
        if ($stationId) {
            $this->getStationData($stationId);
        }
        //查询数据是否重复
        $tmpModel = StCommunist::find()
            ->where(['mobile' => $params['mobile']])
            ->asArray()
            ->one();
        if ($tmpModel) {
            throw new MyException("党员已存在！");
        }

        $params['operator_id'] = $userInfo['id'];
        $params['operator_name'] = $userInfo['username'];
        $params['is_del'] = 1;
        $params['create_at'] = time();
        $model = new StCommunist();
        $model->load($params, '');
        if ($model->save()) {
            return $model->id;
        }
        throw new MyException($this->getError($model));
    }

    //编辑
    public function edit($params, $userInfo = [])
    {
        $stationId = F::value($params, 'station_id', 0);
        if ($stationId) {
            $this->getStationData($stationId);
        }
        $model = $this->getData($params);
        //手机号不可修改
        if ($model->mobile != $params['mobile']) {
            throw new MyException("手机号不可修改！");
        }
        //已认证的
        if ($model->is_authentication == 1) {
            if ($model->name != $params['name']) {
                throw new MyException("已认证，姓名不可修改！");
            }
            if ($model->sex != $params['sex']) {
                throw new MyException("已认证，性别不可修改！");
            }
        }
        $model->load($params, '');
        if ($model->save()) {
            return $model->id;
        }
        throw new MyException($this->getError($model));
    }

    //详情
    public function view($params)
    {
        $model = $this->getData($params);
        $info = StCommunist::find()
            ->alias('sc')
            ->select('sc.id, sc.image,sc.name, sc.mobile, sc.sex, sc.birth_time, sc.join_party_time,
         sc.formal_time, sc.branch, sc.job, sc.type, sc.station_id, sc.is_authentication, st.station')
            ->leftJoin('st_station st', 'sc.station_id = st.id')
            ->where(['sc.id' => $params['id']])
            ->asArray()
            ->one();
        $info['sex_info'] = $this->_sex[$info['sex']];
        $info['birth_time'] = $info['birth_time'] ? date("Y-m-d", $info['birth_time']) : '';
        $info['join_party_time'] = $info['join_party_time'] ? date("Y-m-d", $info['join_party_time']) : '';
        $info['formal_time'] = $info['formal_time'] ? date("Y-m-d", $info['formal_time']) : '';
        $info['type_info'] = $this->_types[$info['type']];
        $info['station_info'] = [
            'id' => $info['station_id'],
            'name' => $info['station']
        ];
        unset($info['type']);
        unset($info['station']);
        unset($info['station_id']);
        return $info;
    }

    //删除
    public function delete($params)
    {
        $model = $this->getData($params);
        $model->is_del = 2;
        if ($model->save()) {
            return true;
        } else {
            throw new MyException($this->getError($model));
        }
    }

    //导入
    public function import($params, $file, $userInfo = [])
    {
        $excel = ExcelService::service();
        $sheetConfig = $this->_getSheetConfig();
        $sheet = $excel->loadFromImport($file);
        if ($sheet === false) {
            return $this->failed($excel->errorMsg);
        }
        $totals = $sheet->getHighestRow();//总条数
        if($totals > 1002) {
            return $this->failed('表格数量太多，建议分批上传，单个文件最多1000条');
        }
        $importDatas = $sheet->toArray(null, false, false, true);
        if (empty($importDatas) || $totals < 3) {
            return $this->failed('内容为空');
        }
        //去掉非数据栏
        unset($importDatas[1]);
        unset($importDatas[2]);
        $success = [];
        $uniqueArr = [];
        foreach ($importDatas as $data) {
            //数据验证
            $row = $excel->format($data, $sheetConfig);//整行数据
            $errors = $excel->valid($row, $sheetConfig);
            if ($errors) {//验证出错
                ExcelService::service()->setError($row, implode(' ; ', $errors));
                continue;
            }
            $tmpData = $row;
            $tmpData['organization_type'] = $params['organization_type'];
            $tmpData['organization_id'] = $params['organization_id'];
            $tmpData['sex'] = $this->getIdByName($this->_sex, $row['sex']);
            $tmpData['type'] = $this->getIdByName($this->_types, $row['type']);
            $tmpData['birth_time_date'] =  F::value($row, 'birth_time', '');
            $tmpData['join_party_time_date'] =  F::value($row, 'join_party_time', '');
            $tmpData['formal_time_date'] =  F::value($row, 'formal_time', '');
            $tmpData['birth_time'] = $tmpData['birth_time_date'] ? strtotime($tmpData['birth_time_date']) : 0;
            $tmpData['join_party_time'] = $tmpData['join_party_time_date'] ? strtotime($tmpData['join_party_time_date']) : 0;
            $tmpData['formal_time'] = $tmpData['formal_time_date'] ? strtotime($tmpData['formal_time_date']) : 0;

            //党员是否已存在
            $tmpModel = StCommunist::find()
                ->where(['mobile' => $row['mobile'], 'is_del' => 1])
                ->asArray()
                ->one();
            if ($tmpModel) {
                ExcelService::service()->setError($row, '党员已经存在');
                continue;
            }
            //数据校验
            $valid = PsCommon::validParamArr(new StCommunist(), $tmpData, 'add');
            if (!$valid["status"]) {
                ExcelService::service()->setError($row, $valid["errorMsg"]);
                continue;
            }

            //数据重复
            if (in_array($tmpData['mobile'], $uniqueArr)) {
                ExcelService::service()->setError($row, "数据重复，相同的手机号已经存在");
                continue;
            } else {
                array_push($uniqueArr, $tmpData['mobile']);
            }

            try {
                $this->add($tmpData, $userInfo);
                $success[] = $tmpData;
            } catch (MyException $e) {
                $message = $e->getMessage();
                ExcelService::service()->setError($row, $message);
                continue;
            }
        }

        $filename = ExcelService::service()->saveErrorCsv($sheetConfig);
        $fail =  ExcelService::service()->getErrorCount();
        $error_url = '';
        if($fail > 0 ){
            $error_url = F::downloadUrl($filename, 'error', 'communistImportError.csv');
        }
        $result = [
            'success' => count($success),
            'totals' => count($success) + ExcelService::service()->getErrorCount(),
            'error_url' => $error_url
        ];
        return $this->success($result);
    }

    public function getData($params)
    {
        $info = StCommunist::findOne($params['id']);
        if (!$info) {
            throw new MyException("党员记录不存在！");
        }
        if ($info->is_del == 2) {
            throw new MyException("党员记录已被删除！");
        }
        return $info;
    }

    private function getStationData($stationId)
    {
        $info = StStation::findOne($stationId);
        if (!$info) {
            throw new MyException("先锋岗位不存在！");
        }
        return $info;
    }

    private function _getSheetConfig()
    {
        return [
            'branch' => ['title' => '所在支部', 'rules' => ['required' => true]],
            'name' => ['title' => '姓名', 'rules' => ['required' => true]],
            'mobile' => ['title' => '手机号', 'rules' => ['required' => true]],
            'sex' => ['title' => '性别','rules' => ['required' => true]],
            'type' => ['title' => '党员类型','rules' => ['required' => true]],
            'birth_time' => ['title' => '出生日期', 'rules' => ['required' => true]],
            'join_party_time' => ['title' => '入党日期', 'rules' => ['required' => true]],
            'formal_time' => ['title' => '转正日期'],
            'job' => ['title' => '党内职务']
        ];
    }

}