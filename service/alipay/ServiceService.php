<?php
/**
 * 小区服务
 */
namespace service\alipay;

use common\MyException;
use service\BaseService;
use Yii;
use yii\db\Query;

Class ServiceService extends BaseService {

    /**
     * 2016-12-15
     * 查看服务
     */
    public function serviceShow($id)
    {
        $model = Yii::$app->db->createCommand("SELECT id as service_id, name, parent_id, header_type, `type` as type_id, intro, order_sort, img_url, link_url, status
            FROM ps_service where id = :id")
            ->bindValue(':id', $id)
            ->queryOne();

        if ($model) {
            $model['header_type_desc'] = ServiceService::service()->getTypesNameById($model['header_type']);
            $parent_name = Yii::$app->db->createCommand("SELECT name FROM ps_service where id = :id")
                ->bindValue(':id', $model['parent_id'])
                ->queryScalar();

            $model['parent_name'] = $parent_name ? $parent_name : '无';
            $model['type_desc'] = isset(LifeNoService::$lifeTypes[$model['type_id']-1]) ? LifeNoService::$lifeTypes[$model['type_id']-1]['value'] : '';
            return $model;
        }
    }

    /**
     * 2016-12-14
     * 获取服务列表 limit $limit, $rows
     */
    public function serviceList($data)
    {
        $name       = !empty($data['service_name']) ? $data['service_name'] : '';
        $parent_id  = !empty($data['service_parent_id']) ? $data['service_parent_id'] : '';
        $service_no = !empty($data['service_no']) ? $data['service_no'] : '';
        $type       = !empty($data['type']) ? $data['type'] : '';
        $status     = !empty($data['service_status']) ? $data['service_status'] : '';
        $page       = !empty($data['page']) ? intval($data['page']) : 1 ;
        $rows       = !empty($data['rows']) ? intval($data['rows']) : 20;
        $limit      = ($page - 1) * $rows;

        $where  = '1 = 1';
        $params = [];
        if ($name) {
            $params = array_merge($params, [':name' => '%'.$name.'%']);
            $where .= " AND name like :name";
        }


        if ($parent_id) {
            $params = array_merge($params, [':parent_id' => $parent_id]);
            $where .= " AND parent_id = :parent_id";
        }

        if ($parent_id == -1) {
            $params = array_merge($params, [':parent_id' => 0]);
            $where .= " AND parent_id = :parent_id";
        }

        if ($type) {
            $params = array_merge($params, [':type' => $type]);
            $where .= " AND type = :type";
        }

        if ($status) {
            $params = array_merge($params, [':status' => $status]);
            $where .= " AND status = :status";
        }

        if ($service_no) {
            $params = array_merge($params, [':service_no' => '%'.$service_no.'%']);
            $where .= " AND service_no like :service_no";
        }

        $totals = Yii::$app->db->createCommand("SELECT COUNT(id) FROM ps_service where $where", $params)->queryScalar();
        $list   = Yii::$app->db->createCommand("SELECT id as service_no, name, parent_id, img_url, header_type, `type` as type_id, link_url, intro, order_sort, status 
            FROM ps_service where $where order by id desc limit $limit, $rows", $params)->queryAll();

        foreach ($list as $key => $val) {
            $name = Yii::$app->db->createCommand("SELECT name from ps_service where id = :id")
                ->bindValue(':id', $val['parent_id'])
                ->queryScalar();

            $list[$key]['parent_name'] = $name ? $name : '无';
            $list[$key]['type_desc'] = isset(LifeNoService::$lifeTypes[$val['type_id']-1]) ? LifeNoService::$lifeTypes[$val['type_id']-1]["value"] : '';
            $list[$key]['header_type_desc'] =  isset(LifeNoService::$lifeHeaderType[$val['header_type']-1]) ? LifeNoService::$lifeHeaderType[$val['header_type']-1]["value"] : '';
        }

        return ['list' => $list, 'totals' => $totals];
    }

    /**
     * 2016-12-14
     * 查找父级服务
     */
    public function serviceParent($status)
    {
        $where  = '';
        $params = [];

        if (1 == $status || 2 == $status) {
            $params = array_merge($params, [':status' => $status]);
            $where .= " AND status = :status";
        }

        $result = Yii::$app->db->createCommand("SELECT id as service_id, name FROM ps_service 
            where parent_id = 0 $where", $params)->queryAll();

        return $result;
    }

    /**
     * 2016-12-14
     * 启用停用服务
     */
    public function serviceCheck($data,$userinfo)
    {
        $service_id = $data['service_id'];
        $status     = $data['status'];

        $service = Yii::$app->db->createCommand("SELECT id,name FROM ps_service where id = :id")
            ->bindValue(':id', $service_id)
            ->queryOne();

        if (!empty($service)) {
            if (2 == $status) {
                // 禁用服务时判断是否包含开启的子服务
                $exist = Yii::$app->db->createCommand("SELECT id FROM ps_service where parent_id = :id AND status = 1")
                    ->bindValue(':id', $service_id)
                    ->queryScalar();

                if ($exist) {
                    throw new MyException('该服务包含子服务，不能直接禁用');
                }
                // 禁用服务时判断是否配置了生活号：2017-12-14陈科浪修改
                $serv = Yii::$app->db->createCommand("SELECT id FROM ps_life_services_menu where service_id = :id ")
                    ->bindValue(':id', $service_id)
                    ->queryScalar();

                if ($serv) {
                    throw new MyException('该服务已被生活号使用，不能直接禁用');
                }
            }

            Yii::$app->db->createCommand()->update('ps_service', ['status' => $status], "id = '$service_id'")->execute();

            $content = '服务名称'.$service['name'].',';
            $content .='更改状态'.($data['status'] == 1 ? "启用":"禁用");
            $operate=[
                "operate_menu"=>"服务管理",
                "operate_type"=>"状态修改",
                "operate_content"=>$content,
            ];
            OperateService::add($userinfo,$operate);
        } else {
            throw new MyException('服务ID不存在');
        }
    }

    /**
     * 2016-12-14
     * 有service_id修改服务  没有则新增服务
     */
    public function serviceUpdate($data,$userinfo)
    {
        $service_id     = !empty($data['service_id']) ? $data['service_id'] : '0';
        $img_url        = !empty($data['img_url']) ? $data['img_url'] : '';
        $link_url       = !empty($data['link_url']) ? $data['link_url'] : '';
        $intro          = !empty($data['intro']) ? $data['intro'] : '';
        $header_type    = !empty($data['header_type']) ? $data['header_type'] : '';
        $type       = !empty($data['type']) ? $data['type'] : 0;
        $name       = !empty($data['name']) ? $data['name'] : '';
        $order_sort = !empty($data['order_sort']) ? $data['order_sort'] : 0;
        $parent_id  = !empty($data['parent_id']) ? $data['parent_id'] : 0;
        $status     = !empty($data['status']) ? $data['status'] : 0;
        $create_at  = time();

        $id = Yii::$app->db->createCommand("SELECT id FROM ps_service where id = :id")
            ->bindValue(':id', $service_id)
            ->queryScalar();
        $count = Yii::$app->db->createCommand("SELECT COUNT(id) FROM ps_service 
            WHERE id != :id AND name = :name")
            ->bindValue(':id', $service_id)
            ->bindValue(':name', $name)
            ->queryScalar();

        if ($count) {
            throw new MyException('服务名称已存在');
        }

        if ($id) { // 修改
            Yii::$app->db->createCommand()->update('ps_service', [
                'name'           => $name,
                'parent_id'      => $parent_id,
                'header_type'    => $header_type,
                'type'           => $type,
                'intro'          => $intro,
                'order_sort'     => $order_sort,
                'status'         => $status,
                'img_url'        => $img_url,
                'link_url'       => $link_url
            ], 'id ='.$service_id)->execute();
            $operate_type = "编辑服务";
            Yii::$app->db->createCommand()->update('ps_community_open_service', [
                'service_name' => $name
            ], "service_id = '$id'")->execute();
            //如果修改了服务名称和服务图标，已经配置过此服务的生活号自动更新菜单


        } else { // 新增
            Yii::$app->db->createCommand()->insert('ps_service', [
                'name'           => $name,
                'parent_id'      => $parent_id,
                'header_type'    => $header_type,
                'type'           => $type,
                'intro'          => $intro,
                'order_sort'     => $order_sort,
                'status'         => $status,
                'img_url'        => $img_url,
                'link_url'       => $link_url,
                'create_at'      => $create_at])->execute();

            $service_no = Yii::$app->db->getLastInsertID();
            $operate_type = "新增服务";
            Yii::$app->db->createCommand()->update('ps_service', [
                'service_no' => $service_no
            ], "id = '$service_no'")->execute();
        }

        $content = '服务名称:'.$name.',';
        $content.='服务说明:'.$intro.',';
        $content.='状态:'.($status==1 ? "启用":"禁用").',';
        $content.=$link_url ? '链接地址：'.$link_url.',' : "";
        $content.=$order_sort ? '服务排序：'.$order_sort.',' : "";

        $operate=[
            "operate_menu"=>"服务管理",
            "operate_type"=>$operate_type,
            "operate_content"=>$content,
        ];
        OperateService::add($userinfo,$operate);
    }

    /**
     * 2016-12-17
     * $name  服务项目名称
     */
    public function  getServiceByName( $name)
    {
        $query = new Query();
        $query->select("*");
        $query->from("ps_service");
        $query->where('name=:name', [':name' => $name]);

        $model = $query->one();
        return $model;
    }

    /*获取生活缴费下的所有子集*/
    public function  getBillService(){
        $sql = "select A.id,A.name from ps_service A left join ps_service B on A.parent_id = B.id where B.name='物业缴费'";
        $models = Yii::$app->db->createCommand($sql)->queryAll();
        return $models;
    }

    public function getCommunityService($community_id){
        $sql = "select A.id,A.name from ps_service A left join ps_service B on A.parent_id = B.id where A.`status`=1 and B.name='物业缴费' ";
        $models =   Yii::$app->db->createCommand($sql)->queryAll();
        return $models;

//        $sql = "select C.id,C.name from  ps_community_open_service D inner join (select A.id,A.name from ps_service A left join ps_service B on A.parent_id = B.id where A.`status`=1 and B.name='物业缴费') C
//          on C.id = D.service_id where D.community_id=".$community_id;
//        $models =   Yii::$app->db->createCommand($sql)->queryAll();
//        return $models;
    }

    /*
    public function getServiceByName( $name)
    {
        $query = new Query();
        $query->select("*");
        $query->from("ps_service");
        $query->where('name=:name', [':name' => $name]);
        $model = $query->one();
        return $model;
    }
    */

    /**
     * 查看服务类型
     * @return array
     */
    public function getTypes()
    {
        return LifeNoService::$lifeHeaderType;
    }

    /**
     * 根据id值获取类型名称
     * @param $id
     * @return string
     */
    public function getTypesNameById($id)
    {
        $typeList = $this->getTypes();
        foreach ($typeList as $k => $v) {
            if ($v['key'] == $id) {
                return $v['value'];
            }
        }
        return "";
    }

    /**
     * 获取开通服务
     */
    public function getService()
    {
        $list = Yii::$app->db->createCommand("SELECT id as value, name as label FROM ps_service where parent_id = :parent_id and status = :status")
            ->bindValue(":status", 1)
            ->bindValue(":parent_id", 0)
            ->queryAll();

        foreach ($list as $key => $val) {
            $model = Yii::$app->db->createCommand("SELECT id as value, name as label FROM ps_service where parent_id = :parent_id and status = :status")
                ->bindValue(":status", 1)
                ->bindValue(":parent_id", $val['value'])
                ->queryAll();
            
            $list[$key]['key'] = $val['value'];

            foreach ($model as $k => $v) {
                $model[$k]['key'] = $v['value'];
            }

            $list[$key]['children'] = $model;
        }

        return $list;
    }


    //获取快递列表
    public function courierList($data){
        $stores       = !empty($data['stores']) ? $data['stores'] : '';
        $number  = !empty($data['number']) ? $data['number'] : '';
        $company = !empty($data['company']) ? $data['company'] : '';
        $user_name       = !empty($data['user_name']) ? $data['user_name'] : '';
        $take_code     = !empty($data['take_code']) ? $data['take_code'] : '';
        $status     = !empty($data['status']) ? $data['status'] : '';
        $start_storage_at     = !empty($data['start_storage_at']) ? strtotime($data['start_storage_at']) : '';
        $end_storage_at     = !empty($data['end_storage_at']) ? strtotime(date("Y-m-d 23:59:59",strtotime($data['end_storage_at']))) : '';
        $start_outbound_at     = !empty($data['start_outbound_at']) ? strtotime($data['start_outbound_at']) : '';
        $end_outbound_at     = !empty($data['end_outbound_at']) ? strtotime(date("Y-m-d 23:59:59",strtotime($data['end_outbound_at']))) : '';
        $page       = !empty($data['page']) ? intval($data['page']) : 1 ;
        $rows       = !empty($data['rows']) ? intval($data['rows']) : 20;
        $limit      = ($page - 1) * $rows;

        $where  = '1 = 1';
        $params = [];
        if ($stores) {
            $where .= " AND stores like :stores";
            $params = array_merge($params, [':stores' => '%'.$stores.'%']);
        }
        if ($number) {
            $where .= " AND number like :number";
            $params = array_merge($params, [':number' => '%'.$number.'%']);
        }
        if ($company) {
            $where .= " AND company like :company";
            $params = array_merge($params, [':company' => '%'.$company.'%']);
        }
        if ($user_name) {
            $where .= " AND ( user_name like :user_name or mobile like :user_name)";
            $params = array_merge($params, [':user_name' => '%'.$user_name.'%']);
        }
        if ($take_code) {
            $where .= " AND take_code like :take_code";
            $params = array_merge($params, [':take_code' => '%'.$take_code.'%']);
        }
        if ($status) {
            $where .= " AND status = :status";
            $params = array_merge($params, [':status' => $status]);
        }
        if ($start_storage_at) {
            $params = array_merge($params, [':start_storage_at' => $start_storage_at, ':end_storage_at' => $end_storage_at]);
            $where .= " AND storage_at >= :start_storage_at AND storage_at <= :end_storage_at";
        }
        if ($start_outbound_at) {
            $params = array_merge($params, [':start_outbound_at' => $start_outbound_at, ':end_outbound_at' => $end_outbound_at]);
            $where .= " AND outbound_at >= :start_outbound_at AND outbound_at <= :end_outbound_at";
        }


        $totals = Yii::$app->db->createCommand("SELECT COUNT(id) FROM ps_courier_parcel where $where", $params)->queryScalar();
        $list   = Yii::$app->db->createCommand("SELECT * FROM ps_courier_parcel where $where order by id desc limit $limit, $rows", $params)->queryAll();
        if($totals>0){
            foreach ($list as $key => $val) {
                $list[$key]['storage_at'] = !empty($val['storage_at'])?date('Y-m-d H:i',$val['storage_at']):'';
                $list[$key]['outbound_at'] = !empty($val['outbound_at'])?date('Y-m-d H:i',$val['outbound_at']):'';
            }
            return ['list' => $list, 'totals' => $totals];
        }
        return ['list' => [], 'totals' => 0];
    }
}