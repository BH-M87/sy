CREATE TABLE `ps_water_meter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` varchar(30) NOT NULL DEFAULT '' COMMENT '小区id',
  `meter_no` varchar(20) NOT NULL DEFAULT '0' COMMENT '表身号',
  `meter_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '缴费类型',
  `meter_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '水表状态 1启用 2禁用',
  `room_id` varchar(30) NOT NULL DEFAULT '' COMMENT '房屋号id',
  `group_id` varchar(30) NOT NULL DEFAULT '' COMMENT '房屋苑/期/区',
  `building_id` varchar(30) NOT NULL DEFAULT '' COMMENT '幢',
  `unit_id` varchar(30) NOT NULL DEFAULT '' COMMENT '室号',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '房屋地址',
  `start_ton` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '起始吨数/立方米',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '抄表时间',
  `cycle_time` int(11) NOT NULL DEFAULT '1' COMMENT '抄表周期/月',
  `payment_time` int(11) NOT NULL DEFAULT '30' COMMENT '账期/天',
  `has_reading` int(11) NOT NULL DEFAULT '2' COMMENT '本周期内是否已抄表 1已抄 2未抄 ',
  `remark` varchar(150) DEFAULT '' COMMENT '备注',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `latest_record_time` int(11) NOT NULL DEFAULT '0' COMMENT '最近一次抄表日期',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='小区水表';


CREATE TABLE `ps_electric_meter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` varchar(30) NOT NULL DEFAULT '' COMMENT '小区id',
  `meter_no` varchar(20) NOT NULL DEFAULT '0' COMMENT '表身号',
  `meter_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '缴费类型',
  `meter_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '电表状态 1启用 2禁用',
  `room_id` varchar(30) NOT NULL DEFAULT '' COMMENT '房屋号id',
  `group_id` varchar(30) NOT NULL DEFAULT '' COMMENT '房屋苑/期/区',
  `building_id` varchar(30) NOT NULL DEFAULT '' COMMENT '幢',
  `unit_id` varchar(30) NOT NULL DEFAULT '' COMMENT '室号',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '房屋地址',
  `start_ton` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '起始电数/立方米',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '抄表时间',
  `cycle_time` int(11) NOT NULL DEFAULT '1' COMMENT '抄表周期/月',
  `payment_time` int(11) NOT NULL DEFAULT '30' COMMENT '账期/天',
  `has_reading` int(11) NOT NULL DEFAULT '2' COMMENT '本周期内是否已抄表 1已抄 2未抄 ',
  `remark` varchar(150) DEFAULT '' COMMENT '备注',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `latest_record_time` int(11) NOT NULL DEFAULT '0' COMMENT '最近一次抄表日期',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='小区电表';

CREATE TABLE `ps_shared` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` varchar(30) NOT NULL COMMENT '小区ID',
  `shared_type` tinyint(1) NOT NULL COMMENT '公摊类型， 1电梯用电，2楼道用电，3整体用水用电',
  `name` varchar(15) NOT NULL COMMENT '项目名称',
  `panel_type` tinyint(1) NOT NULL COMMENT '对应表盘，1水表，2电表',
  `panel_status` tinyint(1) NOT NULL COMMENT '表盘状态, 1正常，2异常',
  `start_num` decimal(10,2) NOT NULL COMMENT '起始度数',
  `remark` varchar(200) DEFAULT '' COMMENT '备注',
  `create_at` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='公摊费用项目';

CREATE TABLE `ps_shared_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` varchar(30) NOT NULL COMMENT '小区ID',
  `period_id` int(11) NOT NULL COMMENT '账期ID',
  `shared_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '公摊类型， 1电梯用电，2楼道用电，3整体用水用电',
  `shared_id` int(11) NOT NULL COMMENT '公摊项目ID',
  `latest_num` decimal(16,2) DEFAULT '0.00' COMMENT '上次度数，第一次为起始读数',
  `current_num` decimal(16,2) NOT NULL DEFAULT '0.00' COMMENT '本次读数',
  `amount` decimal(12,2) NOT NULL COMMENT '对应金额',
  `create_at` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='公摊抄表记录表';

CREATE TABLE `ps_shared_lift_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` varchar(30) NOT NULL COMMENT '小区ID',
  `rule_type` tinyint(1) NOT NULL COMMENT '分摊规则类型：1按楼层， 2按面积， 3按楼层&面积',
  `create_at` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分摊规则表';

CREATE TABLE `ps_meter_cycle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period` int(11) NOT NULL DEFAULT '0' COMMENT '抄表周期',
  `community_id` varchar(30) NOT NULL DEFAULT '' COMMENT '小区id',
  `community_name` varchar(30) NOT NULL DEFAULT '' COMMENT '小区名称',
  `meter_time` int(11) NOT NULL DEFAULT '0' COMMENT '本次抄表时间',
  `type` tinyint(3) NOT NULL DEFAULT '1' COMMENT '1:水表 2:电表 3:公共表',
  `status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '1:未发布账单 2:发布账单',
  `created_at` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='抄表周期表';

CREATE TABLE `ps_shared_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` varchar(30) NOT NULL COMMENT '小区ID',
  `period_start` int(11) NOT NULL COMMENT '账期开始时间戳',
  `period_end` int(11) NOT NULL COMMENT '账期结束时间戳',
  `period_format` varchar(30) NOT NULL COMMENT '账期时间格式户:2018-01-01~2018-02-01',
  `status` tinyint(1) NOT NULL COMMENT '账单状态:1未发布账单，2已发布账单',
  `create_at` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分摊账期表';

ALTER TABLE ps_water_record add `group_id` varchar(30) NOT NULL DEFAULT '' COMMENT '房屋苑/期/区' AFTER room_id;
ALTER TABLE ps_water_record add `building_id` varchar(30) NOT NULL DEFAULT '' COMMENT '幢' AFTER room_id;
ALTER TABLE ps_water_record add `unit_id` varchar(30) NOT NULL DEFAULT '' COMMENT '室号' AFTER room_id;
ALTER TABLE ps_water_record add `address` varchar(255) NOT NULL DEFAULT '' COMMENT '房屋地址' AFTER room_id;
ALTER TABLE ps_water_record add `community_id` varchar(30) NOT NULL DEFAULT '' COMMENT '小区id' AFTER room_id;


CREATE TABLE `ps_shared_bill` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` varchar(11) NOT NULL DEFAULT '' COMMENT '小区id',
  `period_id` int(11) NOT NULL DEFAULT '0' COMMENT '公摊账期id',
  `room_id` varchar(30) NOT NULL DEFAULT '' COMMENT '房屋id',
  `elevator_total` decimal(16,2) DEFAULT '0.00' COMMENT '电梯用电总金额\n',
  `elevator_shared` decimal(16,2) DEFAULT '0.00' COMMENT '电梯应分摊金额\n',
  `corridor_total` decimal(16,2) DEFAULT '0.00' COMMENT '本楼道用电总金额',
  `corridor_shared` decimal(16,2) DEFAULT '0.00' COMMENT '本楼道分摊金额\n',
  `water_electricity_total` decimal(16,2) DEFAULT '0.00' COMMENT '小区整体用水用电总金额\n',
  `water_electricity_shared` decimal(16,2) DEFAULT '0.00' COMMENT '小区整体用水用电分摊金额\n',
  `shared_total` decimal(16,2) NOT NULL DEFAULT '0.00' COMMENT '应分摊总金额\n',
  `bill_status` tinyint(1) DEFAULT '1' COMMENT '1账单未发布2已发布',
  `create_at` int(11) DEFAULT '0' COMMENT '录入时间',
  PRIMARY KEY (`id`),
  KEY `period_id` (`period_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;