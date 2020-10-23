CREATE TABLE `ps_phone` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` varchar(30) NOT NULL DEFAULT '' COMMENT '小区id',
  `community_name` varchar(30) NOT NULL DEFAULT '' COMMENT '小区名称',
  `contact_name` varchar(20) NOT NULL DEFAULT '' COMMENT '联系人',
  `contact_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `type` tinyint(3) NOT NULL DEFAULT '1' COMMENT '1:小区服务电话 2:公共服务电话',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '新增时间',
  `update_at` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='常用电话';


CREATE TABLE `ps_decoration_registration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` varchar(30) NOT NULL DEFAULT '' COMMENT '小区id',
  `community_name` varchar(30) NOT NULL DEFAULT '' COMMENT '小区名称',
  `status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '状态 1进行中 2已完成',
  `room_id` varchar(30) NOT NULL DEFAULT '' COMMENT '房屋号id',
  `group_id` varchar(30) NOT NULL DEFAULT '' COMMENT '房屋苑/期/区',
  `building_id` varchar(30) NOT NULL DEFAULT '' COMMENT '幢',
  `unit_id` varchar(30) NOT NULL DEFAULT '' COMMENT '单元',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '房屋地址',
  `owner_name` varchar(20) NOT NULL DEFAULT '' COMMENT '业主',
  `owner_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '业主电话',
  `project_unit` varchar(50) NOT NULL DEFAULT '' COMMENT '承包单位',
  `project_name` varchar(20) NOT NULL DEFAULT '' COMMENT '项目经理',
  `project_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '项目经理电话',
  `img` varchar(225) NOT NULL DEFAULT '' COMMENT '装修备案图',
  `money` decimal(12,2) NOT NULL DEFAULT '0' COMMENT '保证金',
  `is_refund` tinyint(3) NOT NULL DEFAULT '1' COMMENT '是否退款 1否 2是 默认1',
  `refund_at` int(11) NOT NULL DEFAULT '0' COMMENT '退款时间',
  `is_receive` tinyint(3) NOT NULL DEFAULT '1' COMMENT '是否收款 1否 2是 默认1',
  `receive_at` int(11) NOT NULL DEFAULT '0' COMMENT '收款时间',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '新增时间',
  `update_at` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='装修登记';


CREATE TABLE `ps_decoration_patrol` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `decoration_id` int(11) NOT NULL DEFAULT '0' COMMENT '装修登记id',
  `community_id` varchar(30) NOT NULL DEFAULT '' COMMENT '小区id',
  `community_name` varchar(30) NOT NULL DEFAULT '' COMMENT '小区名称',
  `room_id` varchar(30) NOT NULL DEFAULT '' COMMENT '房屋号id',
  `group_id` varchar(30) NOT NULL DEFAULT '' COMMENT '房屋苑/期/区',
  `building_id` varchar(30) NOT NULL DEFAULT '' COMMENT '幢',
  `unit_id` varchar(30) NOT NULL DEFAULT '' COMMENT '单元',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '房屋地址',
  `is_licensed` tinyint(3) NOT NULL DEFAULT '0' COMMENT '持证情况1:有2:无',
  `is_safe` tinyint(3) NOT NULL DEFAULT '0' COMMENT '安全情况1:有2:无',
  `is_violation` tinyint(3) NOT NULL DEFAULT '0' COMMENT '违章情况1:有2:无',
  `is_env` tinyint(3) NOT NULL DEFAULT '0' COMMENT '环境情况1:有2:无',
  `problem_num` int(2) NOT NULL DEFAULT '0' COMMENT '存在问题数',
  `content` varchar(30) NOT NULL DEFAULT '' COMMENT '装修内容 1工作、2水电、3泥工、4木工、5油漆工、6保洁',
  `remarks` varchar(225) NOT NULL DEFAULT '' COMMENT '备注',
  `patrol_name` varchar(20) NOT NULL DEFAULT '' COMMENT '巡查人',
  `patrol_id` varchar(30) NOT NULL DEFAULT '' COMMENT '巡查人id',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '新增时间',
  `update_at` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='装修巡查记录';

CREATE TABLE `ps_decoration_problem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patrol_id` int(11) NOT NULL DEFAULT '0' COMMENT '巡查记录id',
  `decoration_id` int(11) NOT NULL DEFAULT '0' COMMENT '装修登记id',
  `status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '状态 1待处理 2已处理',
  `community_id` varchar(30) NOT NULL DEFAULT '' COMMENT '小区id',
  `community_name` varchar(30) NOT NULL DEFAULT '' COMMENT '小区名称',
  `room_id` varchar(30) NOT NULL DEFAULT '' COMMENT '房屋号id',
  `group_id` varchar(30) NOT NULL DEFAULT '' COMMENT '房屋苑/期/区',
  `building_id` varchar(30) NOT NULL DEFAULT '' COMMENT '幢',
  `unit_id` varchar(30) NOT NULL DEFAULT '' COMMENT '单元',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '房屋地址',
  `type_msg` varchar(50) NOT NULL DEFAULT '' COMMENT '问题类型 1违章、2安全、3环境、4持证',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '存在问题',
  `problem_img` varchar(225) NOT NULL DEFAULT '' COMMENT '问题图片',
  `assign_name` varchar(20) NOT NULL DEFAULT '' COMMENT '指派人',
  `assign_id` varchar(30) NOT NULL DEFAULT '' COMMENT '指派人id',
  `assigned_name` varchar(20) NOT NULL DEFAULT '' COMMENT '被指派人',
  `assigned_id` varchar(30) NOT NULL DEFAULT '' COMMENT '被指派人id',
  `deal_at` int(11) NOT NULL DEFAULT '0' COMMENT '处理时间',
  `deal_content` varchar(255) NOT NULL DEFAULT '' COMMENT '处理问题',
  `deal_img` varchar(225) NOT NULL DEFAULT '' COMMENT '处理图片',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '新增时间',
  `update_at` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='装修巡查问题工单';

ALTER TABLE ps_decoration_registration ADD `owner_id` varchar(30) NOT NULL DEFAULT '' COMMENT '业主id' AFTER owner_phone;

