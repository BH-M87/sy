<?php
/**
 * 先锋岗
 * Created by PhpStorm.
 * User: zhd
 * Date: 2019/7/11
 * Time: 17:45
 */

namespace service\resident;

use yii\base\Exception;
use Yii;
class CommunistService extends BaseService  {

    /***
     * 根据所属组织查询岗位列表
     * @param $data
     * @throws Exception
     */
    public function stationList($data)
    {
        if (empty($data['organization_id'])) {//没有id
            return ['list' => []];
        }
        $data['organization_type'] = substr($data['organization_id'],0,2) == 'jd' ? 1 : 2;
        $data['organization_id'] = substr($data['organization_id'],3,strlen($data['organization_id'])-3);
        $model = new StStation();
        $data['status'] = 1;//取开启的
        $ress = [];
        if ($data['organization_type'] == 2) {//社区
            $res = $model->getList($data);
        } else if ($data['organization_type'] == 1) {//街道
            $res = $model->getList(['street_id' => $data['street_id'], 'organization_type' => $data['organization_type'], 'status' => $data['status']]);
        }
        if (!empty($res)) {foreach ($res as $k => $v) {
            array_push($ress, ['station_id' => $v['id'], 'station_name' => $v['station']]);
        }}
        return ['list' => $ress];
    }

    /***
     * 新增
     * @param $data
     * @throws Exception
     */
    public function add($data)
    {
        $message = \Yii::$app->params['error'];
        self::_add($message, $data);//校验参数
        //组装数据
        $data['organization_type'] = substr($data['organization_id'],0,2) == 'jd' ? 1 : 2;
        $data['organization_id'] = substr($data['organization_id'],3,strlen($data['organization_id'])-3);
        $data['birth_time'] = !empty($data['birth_time']) ? strtotime($data['birth_time']) : '';
        $data['join_party_time'] = !empty($data['join_party_time']) ? strtotime($data['join_party_time']) : '';
        $data['formal_time'] = !empty($data['formal_time']) ? strtotime($data['formal_time']) : '';
        $data['is_authentication'] = 2;
        $data['status'] = 1;
        $model = new StCommunist(['scenario' => 'add']);
        if ($model->load($data, '') && $model->validate()) {
            $result = $model->add($data);
            if (!$result) {
                $result = self::toResult($message['add_error']['code'],$message['add_error']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            return ['id' => $model->attributes['id']];
        } else {
            $result = self::toResult($message['params_error']['code'],array_values($model->errors)[0][0]);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
    }

    /***
     * 编辑
     * @param $data
     * @throws Exception
     */
    public function update($data)
    {
        $message = \Yii::$app->params['error'];
        $smodel = new StCommunist();
        $sres = $smodel->getFindPk(['id' => $data['id'], 'street_id' => $data['street_id']]);
        self::_update($message, $data, $sres);//校验参数
        //组装数据
        $data['organization_type'] = substr($data['organization_id'],0,2) == 'jd' ? 1 : 2;
        $data['organization_id'] = substr($data['organization_id'],3,strlen($data['organization_id'])-3);
        $data['birth_time'] = !empty($data['birth_time']) ? strtotime($data['birth_time']) : '';
        $data['join_party_time'] = !empty($data['join_party_time']) ? strtotime($data['join_party_time']) : '';
        $data['formal_time'] = !empty($data['formal_time']) ? strtotime($data['formal_time']) : '';
        unset($data['create_at']);
        $model = new StCommunist(['scenario' => 'update']);
        $trueName = $data['truename'];
        unset($data['truename']);
        if ($model->load($data, '') && $model->validate()) {
            $model->edit($data);
            if (!empty($sres['user_id'])) {//只有同步后才要发
                if ($data['organization_type'] != $sres['organization_type'] || $data['organization_id'] != $sres['organization_id']) {//组织变更发消息
                    $yszz = '';//原始组织
                    $bgzz = '';//变更组织
                    $socialmodel = new StSocial();
                    if ($sres['organization_type'] == 1) {
                        $yszz = '街道本级';
                    } else {
                        $social = $socialmodel->getOne(['id' => $sres['organization_id']]);
                        $yszz = !empty($social['name']) ? $social['name'] : '';
                    }
                    if ($data['organization_type'] == 1) {
                        $bgzz = '街道本级';
                    } else {
                        $social = $socialmodel->getOne(['id' => $sres['organization_id']]);
                        $bgzz = !empty($social['name']) ? $social['name'] : '';
                    }
                    $msgParams = [
                        'msg_type' => 7,
                        'msg_content' => "党员你好！管理员".$trueName."已把你所在的组织由".$yszz."变更为".$bgzz."。原组织任务已失效。请知悉。若有疑问，请联系管理员。",
                        'msg_to' => $sres['user_id'],
                        'create_at' => time(),
                    ];
                    VolunteerService::service()->volunteerMsgAdd($msgParams, true);
                }
            }
            return [];
        } else {
            $result = self::toResult($message['params_error']['code'],array_values($model->errors)[0][0]);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
    }

    /***
     * 编辑详情
     * @param $data
     * @throws Exception
     */
    public function undateInfo($data)
    {
        $message = \Yii::$app->params['error'];
        if (empty($data['id'])) {//id不能为空
            $result = self::toResult($message['communist_id_null']['code'],$message['communist_id_null']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        //根据id查询记录 判断是否存在
        $smodel = new StCommunist();
        $sres = $smodel->getFindPk(['id' => $data['id'], 'street_id' => $data['street_id']]);
        if (empty($sres)) {//记录不存在
            $result = self::toResult($message['no_find_info_error']['code'],$message['no_find_info_error']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        //组装数据
        $sres['organization_id'] = ($sres['organization_type'] ==1 ) ? 'jd_'.$sres['organization_id'] : 'sq_'.$sres['organization_id'];
        $sres['birth_time'] = !empty($sres['birth_time']) ? date('Y-m-d', $sres['birth_time']) : '';
        $sres['join_party_time'] = !empty($sres['join_party_time']) ? date('Y-m-d', $sres['join_party_time']) : '';
        $sres['formal_time'] = !empty($sres['formal_time']) ? date('Y-m-d', $sres['formal_time']) : '';
        return $sres;
    }

    /***
     * 详情
     * @param $data
     * @throws Exception
     */
    public function info($data)
    {
        $message = \Yii::$app->params['error'];
        if (empty($data['id'])) {//id不能为空
            $result = self::toResult($message['communist_id_null']['code'],$message['communist_id_null']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        //根据id查询记录 判断是否存在
        $smodel = new StCommunist();
        $sres = $smodel->getFindPk(['id' => $data['id'], 'street_id' => $data['street_id']]);
        if (empty($sres)) {//记录不存在
            $result = self::toResult($message['no_find_info_error']['code'],$message['no_find_info_error']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        //组装数据
        $sres['birth_time'] = !empty($sres['birth_time']) ? date('Y-m-d', $sres['birth_time']) : '';
        $sres['join_party_time'] = !empty($sres['join_party_time']) ? date('Y-m-d', $sres['join_party_time']) : '';
        $sres['formal_time'] = !empty($sres['formal_time']) ? date('Y-m-d', $sres['formal_time']) : '';
        if (!empty($sres['sex'])) {
            $sres['sex'] = ($sres['sex'] == 1) ? '男' : '女';
        }
        if (!empty($sres['type'])) {
            $typeName = ['离退休党员', '流动党员', '困难党员', '下岗失业党员', '在职党员'];
            $sres['type_name'] = $typeName[$sres['type']-1];
        }
        if ($sres['organization_type'] == 1) {
            $sres['organization_name'] = '街道本级';
        } else if ($sres['organization_type'] == 2){
            //根据社区id查询社区名称
            $socialmodel = new StSocial();
            $social = $socialmodel->getOne(['id' => $sres['organization_id']]);
            $sres['organization_name'] = !empty($social['name']) ? $social['name'] : '';
        }
        if (!empty($sres['station_id'])){
            //根据岗位id查询名称
            $stationmodel = new StStation();
            $station = $stationmodel->getFindPk(['id' => $sres['station_id'], 'street_id' => $data['street_id']]);
            $sres['station_name'] = !empty($station['station']) ? $station['station'] : '';
        }
        unset($sres['organization_type']);
        unset($sres['type']);
        unset($sres['station_id']);
        unset($sres['create_at']);
        return $sres;
    }

    /***
     * 删除
     * @param $data
     * @throws Exception
     */
    public function del($data)
    {
        $message = \Yii::$app->params['error'];
        //根据用户id查询用户的组织
        $memberUsermodel = new StMemberUser();
        $memberUser = $memberUsermodel->getInfoById(['user_id' => $data['create_at']]);
        if (empty($memberUser)) {//没有权限
            $result = self::toResult($message['station_add_norole']['code'],$message['station_add_norole']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        if ($memberUser['level']<3 || $memberUser['level']>4) {//没有权限
            $result = self::toResult($message['station_add_norole']['code'],$message['station_add_norole']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        if (empty($data['id'])) {//id不能为空
            $result = self::toResult($message['communist_id_null']['code'],$message['communist_id_null']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        //根据id查询记录 判断是否存在
        $smodel = new StCommunist();
        $sres = $smodel->getFindPk(['id' => $data['id'], 'street_id' => $data['street_id']]);
        if (empty($sres)) {//记录不存在
            $result = self::toResult($message['no_find_info_error']['code'],$message['no_find_info_error']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        $datas = ['id' => $data['id'], 'street_id' => $data['street_id'], 'status' => 2];
        $smodel->edit($datas);
        return [];
    }

    /***
     * 列表
     * @param $data
     * @throws Exception
     */
    public function communistList($data)
    {
        //根据用户id查询用户的组织
        $memberUsermodel = new StMemberUser();
        $memberUser = $memberUsermodel->getInfoById(['user_id' => $data['create_at']]);
        if (empty($memberUser)) {//没有权限
            return ['list' => [], 'totals' => 0, 'is_operation' => 0];
        }
        if ($memberUser['level']<3 || $memberUser['level']>4) {//没有权限
            return ['list' => [], 'totals' => 0, 'is_operation' => 0];
        }
        unset($data['create_at']);
        $data['status'] = 1;//获取有效的党员
        $res = [];
        $count = 0;
        $model = new StCommunist();
        if ($memberUser['level'] == 3) {//社区
            $data['organization_type'] = 2;
            $data['organization_id'] = $memberUser['social_id'];
            unset($data['organization_id']);
            $res = $model->getList($data);
            $count = $model->getListCount($data);
        }
        if ($memberUser['level'] == 4) {//街道 可以看所有的
            if (!empty($data['organization_id'])) {
                $data['organization_type'] = substr($data['organization_id'], 0, 2) == 'jd' ? 1 : 2;
                $data['organization_id'] = substr($data['organization_id'], 3, strlen($data['organization_id']) - 3);
            }
            $res = $model->getList($data);
            $count = $model->getListCount($data);
        }
        $typeName = ['离退休党员', '流动党员', '困难党员', '下岗失业党员', '在职党员'];
        if (!empty($res)) {foreach ($res as $k => $v){//数据处理
            if ($res[$k]['sex'] == 1) {
                $res[$k]['sex'] = '男';
            }
            if ($res[$k]['sex'] == 2) {
                $res[$k]['sex'] = '女';
            }
            if ($res[$k]['sex'] == 3) {
                $res[$k]['sex'] = '';
            }
            $res[$k]['mobile'] = substr_replace($res[$k]['mobile'], '****', 3, 4);
            $res[$k]['type'] = $typeName[$res[$k]['type']-1];
            $res[$k]['birth_time'] = !empty($res[$k]['birth_time']) ? date('Y-m-d', $res[$k]['birth_time']) : '';
            $res[$k]['join_party_time'] = !empty($res[$k]['join_party_time']) ? date('Y-m-d', $res[$k]['join_party_time']) : '';
            $res[$k]['formal_time'] = !empty($res[$k]['formal_time']) ? date('Y-m-d', $res[$k]['formal_time']) : '';
            unset($res[$k]['organization_type']);unset($res[$k]['organization_id']);unset($res[$k]['station_id']);unset($res[$k]['status']);
            unset($res[$k]['create_at']);unset($res[$k]['create_time']);unset($res[$k]['is_authentication']);
        }}
        return ['list' => $res, 'totals' => $count, 'is_operation' => 1];
    }

    /***
     * 党员支付宝认证
     * @param $data
     * @throws Exception
     */
    public function updateAuthentication($data)
    {
        $smodel = new StCommunist();
        $sres = $smodel->getFindMobile(['mobile' => $data['mobile']]);
        if (empty($sres)) {//记录不存在
            return [];
        }
        $isCertified = !empty($data['is_certified']) ? $data['is_certified'] : 2;
        if ($isCertified == 1) {
            $name = !empty($data['name']) ? $data['name'] : $sres['name'];
            $sex = !empty($data['sex']) ? $data['sex'] : $sres['sex'];
            $datas = ['id' => $sres['id'], 'name' => $name, 'sex' => $sex, 'street_id' => $sres['street_id'], 'is_authentication' => $isCertified, 'user_id' => $data['user_id']];
            $smodel->edit($datas);
        } else {
            $datas = ['id' => $sres['id'], 'street_id' => $sres['street_id'], 'user_id' => $data['user_id']];
            $smodel->edit($datas);
        }
        return [];
    }

    /***
     * 物业同步党员服务
     * @param $data
     * @throws Exception
     */
    public function synchro($data){
        if (!empty($data['mobile']) && !empty($data['community_id']) && !empty($data['name'])  && !empty($data['type'])) {
            $smodel = new StCommunist();
            $sres = $smodel->getFindMobile(['mobile' => $data['mobile']]);//查询党员信息
            if ($data['type'] == 1) {//打标
                $flog = 0;//判断有没有党员标签
                if (!empty($data['label'])) {//标签数组存在
                    foreach ($data['label'] as $k => $v) {
                        if(strpos($v,'党员') !== false) {
                            $flog = 1;
                        }
                    }
                }
                if($flog) {//标签包含党员的话  进行同步
                    if (empty($sres)) {//党员不存在  新增党员
                        //根据小区id查询关联的用户id
                        if (YII_ENV == 'master') {
                            $userInfo = PsUserCommunity::find()->select('manage_id')->where(['community_id' => $data['community_id']])->asArray()->all();//'deleted' => 0,
                        } else {
                            $userInfo = PsUserCommunity::find()->select('manage_id')->where(['deleted' => 0, 'community_id' => $data['community_id']])->asArray()->all();
                        }
                        if (!empty($userInfo)) {
                            $usrid = [];
                            foreach ($userInfo as $k => $v) {
                                array_push($usrid, $v['manage_id']);
                            }
                            //根据用户id查询街道id
                            $streetInfo = PsUser::find()->select('property_company_id')->where(['deleted' => 0, 'system_type' => 3])->andWhere(['in','id',$usrid])->asArray()->one();
                            if (!empty($streetInfo)) {
                                $streetId = $streetInfo['property_company_id'];
                                $datas = ['organization_type' => 1, 'organization_id' => $streetId];
                                //根据小区id查询社区id
                                $scmodel = new StSocialCommunity();
                                $scinfo = $scmodel->getOneByCommunityId(['community_id' => $data['community_id'], 'street_id' => $streetId]);
                                if (!empty($scinfo)) {//如果有社区id加入社区组织
                                    $datas['organization_type'] = 2;
                                    $datas['organization_id'] = $scinfo['social_id'];
                                }
                                //新增党员
                                $model = new StCommunist();
                                $model->organization_type = $datas['organization_type'];
                                $model->organization_id = $datas['organization_id'];
                                $model->name = $data['name'];
                                $model->mobile = $data['mobile'];
                                $model->status = 1;
                                $model->is_authentication = 2;
                                $model->create_at = 0;
                                $model->create_time = time();
                                $model->street_id = $streetId;
                                $model->save();
                            }
                        }
                    }
                } else {//换了标签不是党员
                    if (!empty($sres)) {// 党员存在的话  删除党员
                        $datas = ['id' => $sres['id'], 'street_id' => $sres['street_id'], 'status' => 2];
                        $smodel->edit($datas);
                    }
                }
            }
            if ($data['type'] == 2 && !empty($sres)) {//去标 党员存在的话就删除党员
                $datas = ['id' => $sres['id'], 'street_id' => $sres['street_id'], 'status' => 2];
                $smodel->edit($datas);
            }
        }
        return [];
    }

    /***
     * 初始化物业同步党员服务
     * @param $data
     * @throws Exception
     */
    public function synchroList($data){
        $sql = "SELECT pru.mobile,pru.name,pru.community_id FROM `ps_room_user_label` prl left join ps_room_user pru on prl.room_user_id = pru.id where pru.community_id <> '' and prl.label_id in (SELECT id FROM `ps_labels` where name like '%党员%')";
        $data = Yii::$app->db->createCommand($sql)->queryAll();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                if (!empty($v['mobile']) && !empty($v['community_id']) && !empty($v['name'])) {
                    $smodel = new StCommunist();
                    $sres = $smodel->getFindMobile(['mobile' => $v['mobile']]);//查询党员信息
                    if (empty($sres)) {//党员不存在  新增党员
                        //根据小区id查询关联的用户id
                        if (YII_ENV == 'master') {
                            $userInfo = PsUserCommunity::find()->select('manage_id')->where(['community_id' => $v['community_id']])->asArray()->all();//'deleted' => 0,
                        } else {
                            $userInfo = PsUserCommunity::find()->select('manage_id')->where(['deleted' => 0, 'community_id' => $v['community_id']])->asArray()->all();
                        }
                        if (!empty($userInfo)) {
                            $usrid = [];
                            foreach ($userInfo as $ks => $vs) {
                                array_push($usrid, $vs['manage_id']);
                            }
                            //根据用户id查询街道id
                            $streetInfo = PsUser::find()->select('property_company_id')->where(['deleted' => 0, 'system_type' => 3])->andWhere(['in','id',$usrid])->asArray()->one();
                            if (!empty($streetInfo)) {
                                $streetId = $streetInfo['property_company_id'];
                                $datas = ['organization_type' => 1, 'organization_id' => $streetId];
                                //根据小区id查询社区id
                                $scmodel = new StSocialCommunity();
                                $scinfo = $scmodel->getOneByCommunityId(['community_id' => $v['community_id'], 'street_id' => $streetId]);
                                if (!empty($scinfo)) {//如果有社区id加入社区组织
                                    $datas['organization_type'] = 2;
                                    $datas['organization_id'] = $scinfo['social_id'];
                                }
                                //新增党员
                                $model = new StCommunist();
                                $model->organization_type = $datas['organization_type'];
                                $model->organization_id = $datas['organization_id'];
                                $model->name = $v['name'];
                                $model->mobile = $v['mobile'];
                                $model->status = 1;
                                $model->is_authentication = 2;
                                $model->create_at = 0;
                                $model->create_time = time();
                                $model->street_id = $streetId;
                                $model->save();
                            }
                        }
                    }
                }
            }
        }
        return [];
    }

    /***
     * 批量上传
     * @param $data
     * @throws Exception
     */
    public function excelAdd($data, $sheetData, $totals, $excel, $systemType){
        $message = \Yii::$app->params['error'];
        //根据用户id查询用户的组织
        $memberUsermodel = new StMemberUser();
        $memberUser = $memberUsermodel->getInfoById(['user_id' => $data['create_at']]);
        if (empty($memberUser)) {//没有权限
            $result = self::toResult($message['station_add_norole']['code'],$message['station_add_norole']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        if ($memberUser['level']<3 || $memberUser['level']>4) {//没有权限
            $result = self::toResult($message['station_add_norole']['code'],$message['station_add_norole']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        $sheetConfig = $this->_getSheetConfig();//sheet配置
        $success = [];
        $successSex =  [];
        for ($i = 3; $i <= $totals; $i++) {
            $row = $excel->format($sheetData[$i], $sheetConfig);//整行数据
            $errors = $excel->valid($row, $sheetConfig);
            if ($errors) {//验证出错
                ExcelService::service()->setError($row, implode(' ; ', $errors));
                continue;
            }
            //校验手机号
            if (!preg_match("/^1(3|4|5|6|7|8|9)\d{9}$/", $row['mobile'])) {
                ExcelService::service()->setError($row, '手机号格式错误');
                continue;
            }
            $model = new StCommunist();
            $info = $model->getFindMobile(['mobile' => $row['mobile']]);
            if (!empty($info)) {
                ExcelService::service()->setError($row, '该手机号已存在');
                continue;
            }
            //校验日期
            if (!empty($row['birth_time']) && strtotime($row['birth_time']) > time()) {//大于当前日期
                ExcelService::service()->setError($row, '出生日期不能大于当前日期');
                continue;
            }
            if (!empty($row['join_party_time'])) {//入党日期存在
                if (strtotime($row['join_party_time']) > time()) {//大于当前日期
                    ExcelService::service()->setError($row, '入党日期不能大于当前日期');
                    continue;
                }
                //出生日期必须存在
                if (empty($row['birth_time'])) {
                    ExcelService::service()->setError($row, '入党日期填了出生日期必填');
                    continue;
                }
                $row['birth_time'] = date('Y-m-d',strtotime($row['birth_time']));
                $row['join_party_time'] = date('Y-m-d',strtotime($row['join_party_time']));
                //入党日期：到天，不得小于出生日期+18年
                $ba = explode('-', $row['birth_time']);
                $jo = explode('-', $row['join_party_time']);
                if (($jo[0] - $ba[0])<18) {
                    ExcelService::service()->setError($row, '入党日期不得小于出生日期+18年');
                    continue;
                }
                if (($jo[0] - $ba[0]) == 18 && $jo[1] < $ba[1]) {
                    ExcelService::service()->setError($row, '入党日期不得小于出生日期+18年');
                    continue;
                }
                if (($jo[0] - $ba[0]) == 18 && $jo[1] == $ba[1] && $jo[2] < $ba[2]) {
                    ExcelService::service()->setError($row, '入党日期不得小于出生日期+18年');
                    continue;
                }
            }
            if (!empty($row['formal_time'])) {//转正日期存在
                if (strtotime($row['formal_time']) > time()) {//大于当前日期
                    ExcelService::service()->setError($row, '转正日期不能大于当前日期');
                    continue;
                }
                //出生日期必须存在
                if (empty($row['join_party_time'])) {
                    ExcelService::service()->setError($row, '转正日期填了入党日期必填');
                    continue;
                }
                $row['formal_time'] = date('Y-m-d',strtotime($row['formal_time']));
                $row['join_party_time'] = date('Y-m-d',strtotime($row['join_party_time']));
                //转正日期：到天，不得小于入党日期+1年
                $ba = explode('-', $row['formal_time']);
                $jo = explode('-', $row['join_party_time']);
                if (($ba[0] - $jo[0])<1) {
                    ExcelService::service()->setError($row, '转正日期不得小于入党日期+1年');
                    continue;
                }
                if (($ba[0] - $jo[0]) == 1 && $ba[1] < $jo[1]) {
                    ExcelService::service()->setError($row, '转正日期不得小于入党日期+1年');
                    continue;
                }
                if (($ba[0] - $jo[0]) == 1 && $ba[1] == $jo[1] && $ba[2] < $jo[2]) {
                    ExcelService::service()->setError($row, '转正日期不得小于入党日期+1年');
                    continue;
                }
            }
            //组装数据
            $row['birth_time'] = !empty($row['birth_time']) ? strtotime($row['birth_time']) : '';
            $row['join_party_time'] = !empty($row['join_party_time']) ? strtotime($row['join_party_time']) : '';
            $row['formal_time'] = !empty($row['formal_time']) ? strtotime($row['formal_time']) : '';
            $row['status'] = 1;
            $row['create_time'] = time();
            $row['create_at'] = $data['street_id'];
            $row['street_id'] = $data['create_at'];
            $row['is_authentication'] = 2;
            $typeName = ['离退休党员', '流动党员', '困难党员', '下岗失业党员', '在职党员'];
            if (!empty($row['type'])) {
                foreach ($typeName as $kt => $vt) {
                    if ($vt == $row['type']) {
                        $row['type'] = $kt+1;
                    }
                }
            }
            $row['organization_id'] = $data['street_id'];
            if ($row['organization_type'] != '街道本级') {//查询社区id
                $socialModel = new StSocial();
                $social = $socialModel->getName(['name' => $row['organization_type'], 'street_id' => $data['street_id']]);
                if (!empty($social)) {
                    $row['organization_type'] = 2;
                    $row['organization_id'] = $social['id'];
                } else {
                    $row['organization_type'] = 1;
                }
            } else {
                $row['organization_type'] = 1;
            }
            if (!empty($row['sex'])) {
                if ($row['sex'] == '男') {
                    $row['sex'] = 1;
                } else if ($row['sex'] == '女') {
                    $row['sex'] = 2;
                } else {
                    $row['sex'] = '';
                }
            } else {
                $row['sex'] = '';
            }
            if (!empty($row['sex'])) {
                $flog = 1;
                if (!empty($successSex)) { foreach ($successSex as $k => $v){//去重
                    if ($v['mobile'] == $row['mobile']) {
                        $flog = 0;
                    }
                }}
                if ($flog) {
                    $successSex[] = $row;
                }
            } else {
                unset($row['sex']);
                $flog = 1;
                if (!empty($success)) { foreach ($success as $k => $v){//去重
                    if ($v['mobile'] == $row['mobile']) {
                        $flog = 0;
                        ExcelService::service()->setError($row, '重复的手机号码');
                        continue;
                    }
                }}
                if ($flog) {
                    $success[] = $row;
                }
            }
        }
        //批量插入
        if (!empty($successSex)) {//插入有性别的
            Yii::$app->db->createCommand()->batchInsert(StCommunist::tableName(), ['organization_type', 'community_name', 'name', 'mobile', 'sex', 'birth_time', 'join_party_time', 'formal_time', 'branch', 'job', 'type', 'status', 'create_time', 'street_id', 'create_at', 'is_authentication', 'organization_id'], $successSex)->execute();
        }
        if (!empty($success)) {//插入没有性别的
            Yii::$app->db->createCommand()->batchInsert(StCommunist::tableName(), ['organization_type', 'community_name', 'name', 'mobile', 'birth_time', 'join_party_time', 'formal_time', 'branch', 'job', 'type', 'status', 'create_time', 'street_id', 'create_at', 'is_authentication', 'organization_id'], $success)->execute();
        }
        $successCount = count($success);
        $successSexCount = count($successSex);
        $errorCount = ExcelService::service()->getErrorCount();
        $filename = ExcelService::service()->saveErrorCsv($sheetConfig);
        $path = '';
        if (YII_ENV == 'test') {//测试环境
            $path = '/sdm/web';
        }
        $errorUrl = '';
        if ($errorCount > 0) {
            $errorUrl = F::streetDownloadUrl($systemType, $filename, 'error', '', $path);
        }
        $result = [
            'totals' => $successCount + $errorCount + $successSexCount,
            'success' => $successCount + $successSexCount,
            'error_url' => $errorUrl,
        ];
        return $result;
    }

    //excel
    private function _getSheetConfig()
    {
        $sex = ['男', '女'];
        $type = ['离退休党员', '流动党员', '困难党员', '下岗失业党员', '在职党员'];
        return [
            'organization_type' => ['title' => '所在组织', 'rules' => ['required' => true]],
            'community_name' => ['title' => '所在小区'],
            'name' => ['title' => '姓名', 'rules' => ['required' => true]],
            'mobile' => ['title' => '手机号', 'rules' => ['required' => true]],
            'sex' => ['title' => '性别', 'rules' => ['items' => $sex]],
            'birth_time' => ['title' => '出生日期'],
            'join_party_time' => ['title' => '入党日期'],
            'formal_time' => ['title' => '转正日期'],
            'branch' => ['title' => '所在支部'],
            'job' => ['title' => '党内职务'],
            'type' => ['title' => '党员类型', 'rules' => ['items' => $type]],
        ];
    }

    //校验参数
    private static function _update($message, $data, $sres) {
        //根据用户id查询用户的组织
        $memberUsermodel = new StMemberUser();
        $memberUser = $memberUsermodel->getInfoById(['user_id' => $data['create_at']]);
        if (empty($memberUser)) {//没有权限
            $result = self::toResult($message['station_add_norole']['code'],$message['station_add_norole']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        if ($memberUser['level']<3 || $memberUser['level']>4) {//没有权限
            $result = self::toResult($message['station_add_norole']['code'],$message['station_add_norole']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        if (empty($data['id'])) {//id不能为空
            $result = self::toResult($message['communist_id_null']['code'],$message['communist_id_null']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        //根据id查询记录 判断是否存在
        if (empty($sres)) {//记录不存在
            $result = self::toResult($message['no_find_info_error']['code'],$message['no_find_info_error']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        if ($sres['is_authentication'] == 1) {//如果是支付宝认证过的 姓名和性别不能修改
            $data['sex'] = $sres['sex'];
            $data['name'] = $sres['name'];
        }
        if (empty($data['organization_id'])) {
            $result = self::toResult($message['organization_id_noll']['code'],$message['organization_id_noll']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        if (!empty($data['birth_time']) && strtotime($data['birth_time']) > time()) {//大于当前日期
            $result = self::toResult($message['birth_time_dayu']['code'],$message['birth_time_dayu']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        if (!empty($data['join_party_time'])) {//入党日期存在
            if (strtotime($data['join_party_time']) > time()) {//大于当前日期
                $result = self::toResult($message['join_party_dayu']['code'],$message['join_party_dayu']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            //出生日期必须存在
            if (empty($data['birth_time'])) {
                $result = self::toResult($message['birth_time_norole']['code'],$message['birth_time_norole']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            $data['birth_time'] = date('Y-m-d',strtotime($data['birth_time']));
            $data['join_party_time'] = date('Y-m-d',strtotime($data['join_party_time']));
            //入党日期：到天，不得小于出生日期+18年
            $ba = explode('-', $data['birth_time']);
            $jo = explode('-', $data['join_party_time']);
            if (($jo[0] - $ba[0])<18) {
                $result = self::toResult($message['birth_time_error']['code'],$message['birth_time_error']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            if (($jo[0] - $ba[0]) == 18 && $jo[1] < $ba[1]) {
                $result = self::toResult($message['birth_time_error']['code'],$message['birth_time_error']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            if (($jo[0] - $ba[0]) == 18 && $jo[1] == $ba[1] && $jo[2] < $ba[2]) {
                $result = self::toResult($message['birth_time_error']['code'],$message['birth_time_error']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
        }
        if (!empty($data['formal_time'])) {//转正日期存在
            if (strtotime($data['formal_time']) > time()) {//大于当前日期
                $result = self::toResult($message['formel_time_dayu']['code'],$message['formel_time_dayu']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            //出生日期必须存在
            if (empty($data['join_party_time'])) {
                $result = self::toResult($message['join_party_time_null']['code'],$message['join_party_time_null']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            $data['formal_time'] = date('Y-m-d',strtotime($data['formal_time']));
            $data['join_party_time'] = date('Y-m-d',strtotime($data['join_party_time']));
            //转正日期：到天，不得小于入党日期+1年
            $ba = explode('-', $data['formal_time']);
            $jo = explode('-', $data['join_party_time']);
            if (($ba[0] - $jo[0])<1) {
                $result = self::toResult($message['join_party_error']['code'],$message['join_party_error']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            if (($ba[0] - $jo[0]) == 1 && $ba[1] < $jo[1]) {
                $result = self::toResult($message['join_party_error']['code'],$message['join_party_error']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            if (($ba[0] - $jo[0]) == 1 && $ba[1] == $jo[1] && $ba[2] < $jo[2]) {
                $result = self::toResult($message['join_party_error']['code'],$message['join_party_error']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
        }
    }

    //校验参数
    private static function _add($message, $data) {
        //根据用户id查询用户的组织
        $memberUsermodel = new StMemberUser();
        $memberUser = $memberUsermodel->getInfoById(['user_id' => $data['create_at']]);
        if (empty($memberUser)) {//没有权限
            $result = self::toResult($message['station_add_norole']['code'],$message['station_add_norole']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        if ($memberUser['level']<3 || $memberUser['level']>4) {//没有权限
            $result = self::toResult($message['station_add_norole']['code'],$message['station_add_norole']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        if (empty($data['organization_id'])) {
            $result = self::toResult($message['organization_id_noll']['code'],$message['organization_id_noll']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        if (!empty($data['birth_time']) && strtotime($data['birth_time']) > time()) {//大于当前日期
            $result = self::toResult($message['birth_time_dayu']['code'],$message['birth_time_dayu']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        if (!empty($data['join_party_time'])) {//入党日期存在
            if (strtotime($data['join_party_time']) > time()) {//大于当前日期
                $result = self::toResult($message['join_party_dayu']['code'],$message['join_party_dayu']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            //出生日期必须存在
            if (empty($data['birth_time'])) {
                $result = self::toResult($message['birth_time_norole']['code'],$message['birth_time_norole']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            $data['birth_time'] = date('Y-m-d',strtotime($data['birth_time']));
            $data['join_party_time'] = date('Y-m-d',strtotime($data['join_party_time']));
            //入党日期：到天，不得小于出生日期+18年
            $ba = explode('-', $data['birth_time']);
            $jo = explode('-', $data['join_party_time']);
            if (($jo[0] - $ba[0])<18) {
                $result = self::toResult($message['birth_time_error']['code'],$message['birth_time_error']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            if (($jo[0] - $ba[0]) == 18 && $jo[1] < $ba[1]) {
                $result = self::toResult($message['birth_time_error']['code'],$message['birth_time_error']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            if (($jo[0] - $ba[0]) == 18 && $jo[1] == $ba[1] && $jo[2] < $ba[2]) {
                $result = self::toResult($message['birth_time_error']['code'],$message['birth_time_error']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
        }
        if (!empty($data['formal_time'])) {//转正日期存在
            if (strtotime($data['formal_time']) > time()) {//大于当前日期
                $result = self::toResult($message['formel_time_dayu']['code'],$message['formel_time_dayu']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            //出生日期必须存在
            if (empty($data['join_party_time'])) {
                $result = self::toResult($message['join_party_time_null']['code'],$message['join_party_time_null']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            $data['formal_time'] = date('Y-m-d',strtotime($data['formal_time']));
            $data['join_party_time'] = date('Y-m-d',strtotime($data['join_party_time']));
            //转正日期：到天，不得小于入党日期+1年
            $ba = explode('-', $data['formal_time']);
            $jo = explode('-', $data['join_party_time']);
            if (($ba[0] - $jo[0])<1) {
                $result = self::toResult($message['join_party_error']['code'],$message['join_party_error']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            if (($ba[0] - $jo[0]) == 1 && $ba[1] < $jo[1]) {
                $result = self::toResult($message['join_party_error']['code'],$message['join_party_error']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            if (($ba[0] - $jo[0]) == 1 && $ba[1] == $jo[1] && $ba[2] < $jo[2]) {
                $result = self::toResult($message['join_party_error']['code'],$message['join_party_error']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
        }
    }

    //获取excel参数
    /*private static function _makeExcelAdd($data, $sheetData, $totals) {
        $datas = [];
        $typeName = ['离退休党员', '流动党员', '困难党员', '下岗失业党员', '在职党员'];
        for ($i = 3; $i <= $totals; $i++) {
            $type = '';
            if (!empty($sheetData[$i]['K'])) {
                foreach ($typeName as $kt => $vt) {
                    if ($vt == $sheetData[$i]['K']) {
                        $type = $kt+1;
                    }
                }
            }
            $sex = '';
            if (!empty($sheetData[$i]['E'])) {
                if ($sheetData[$i]['E'] == '男') {
                    $sex = 1;
                }
                if ($sheetData[$i]['E'] == '女') {
                    $sex = 2;
                }
            }
            $organization_type = 1;
            $organization_id = $data['street_id'];
            if (empty($sheetData[$i]['A'])) {//所在组织必填
                $result = self::toResult(80000, '第'.$i.'行所属组织不能为空');
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            if ($sheetData[$i]['A'] != '街道本级') {//查询社区id
                $socialModel = new StSocial();
                $social = $socialModel->getName(['name' => $sheetData[$i]['A'], 'street_id' => $data['street_id']]);
                if (!empty($social)) {
                    $organization_type = 2;
                    $organization_id = $social['id'];
                }
            }
            array_push($datas,[
                'organization_type' => $organization_type,//所属组织类型(1街道本级 2社区)
                'organization_id' => $organization_id,//所属组织Id
                'community_name' => $sheetData[$i]['B'],//所属小区
                'name' => $sheetData[$i]['C'],//姓名
                'mobile' => $sheetData[$i]['D'],//手机号
                'sex' => $sex,//性别
                'birth_time' => !empty($sheetData[$i]['F']) ? $sheetData[$i]['F'] : '',//出生日期
                'join_party_time' => !empty($sheetData[$i]['G']) ? $sheetData[$i]['G'] : '',//入党日期
                'formal_time' => !empty($sheetData[$i]['H']) ? $sheetData[$i]['H'] : '',//转正日期
                'branch' => !empty($sheetData[$i]['I']) ? $sheetData[$i]['I'] : '',//所在支部
                'job' => !empty($sheetData[$i]['J']) ? $sheetData[$i]['J'] : '',//党内职务
                'type' => $type,//党员类型
                'status' => 1,//状态：1有效  2已删除
                'create_at' => $data['create_at'],//创建人id
                'create_time' => time(),//创建时间
                'street_id' => $data['street_id'],//街道id
                'is_authentication' => 2//是否支付宝认证 1是 2否
            ]);
        }
        return $datas;
    }*/

    //校验参数
    /*private static function _excelAdd($message, $datas, $data) {
        //根据用户id查询用户的组织
        $memberUsermodel = new StMemberUser();
        $memberUser = $memberUsermodel->getInfoById(['user_id' => $data['create_at']]);
        if (empty($memberUser)) {//没有权限
            $result = self::toResult($message['station_add_norole']['code'],$message['station_add_norole']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        if ($memberUser['level']<3 || $memberUser['level']>4) {//没有权限
            $result = self::toResult($message['station_add_norole']['code'],$message['station_add_norole']['info']);
            throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        foreach ($datas as $k => $v) {
            $hang = '第'.($k+3).'行';
            if (!empty($v['birth_time']) && strtotime($v['birth_time']) > time()) {//大于当前日期
                $result = self::toResult($message['birth_time_dayu']['code'],$hang.$message['birth_time_dayu']['info']);
                throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            }
            if (!empty($v['join_party_time'])) {//入党日期存在
                if (strtotime($v['join_party_time']) > time()) {//大于当前日期
                    $result = self::toResult($message['join_party_dayu']['code'],$hang.$message['join_party_dayu']['info']);
                    throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
                }
                //出生日期必须存在
                if (empty($v['birth_time'])) {
                    $result = self::toResult($message['birth_time_norole']['code'],$hang.$message['birth_time_norole']['info']);
                    throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
                }
                $v['birth_time'] = date('Y-m-d',strtotime($v['birth_time']));
                $v['join_party_time'] = date('Y-m-d',strtotime($v['join_party_time']));
                //入党日期：到天，不得小于出生日期+18年
                $ba = explode('-', $v['birth_time']);
                $jo = explode('-', $v['join_party_time']);
                if (($jo[0] - $ba[0])<18) {
                    $result = self::toResult($message['birth_time_error']['code'],$hang.$message['birth_time_error']['info']);
                    throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
                }
                if (($jo[0] - $ba[0]) == 18 && $jo[1] < $ba[1]) {
                    $result = self::toResult($message['birth_time_error']['code'],$hang.$message['birth_time_error']['info']);
                    throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
                }
                if (($jo[0] - $ba[0]) == 18 && $jo[1] == $ba[1] && $jo[2] < $ba[2]) {
                    $result = self::toResult($message['birth_time_error']['code'],$hang.$message['birth_time_error']['info']);
                    throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
                }
            }
            if (!empty($v['formal_time'])) {//转正日期存在
                if (strtotime($v['formal_time']) > time()) {//大于当前日期
                    $result = self::toResult($message['formel_time_dayu']['code'],$hang.$message['formel_time_dayu']['info']);
                    throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
                }
                //出生日期必须存在
                if (empty($v['join_party_time'])) {
                    $result = self::toResult($message['join_party_time_null']['code'],$hang.$message['join_party_time_null']['info']);
                    throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
                }
                $v['formal_time'] = date('Y-m-d',strtotime($v['formal_time']));
                $v['join_party_time'] = date('Y-m-d',strtotime($v['join_party_time']));
                //转正日期：到天，不得小于入党日期+1年
                $ba = explode('-', $v['formal_time']);
                $jo = explode('-', $v['join_party_time']);
                if (($ba[0] - $jo[0])<1) {
                    $result = self::toResult($message['join_party_error']['code'],$hang.$message['join_party_error']['info']);
                    throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
                }
                if (($ba[0] - $jo[0]) == 1 && $ba[1] < $jo[1]) {
                    $result = self::toResult($message['join_party_error']['code'],$hang.$message['join_party_error']['info']);
                    throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
                }
                if (($ba[0] - $jo[0]) == 1 && $ba[1] == $jo[1] && $ba[2] < $jo[2]) {
                    $result = self::toResult($message['join_party_error']['code'],$hang.$message['join_party_error']['info']);
                    throw new Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
                }
            }
        }
    }*/
}