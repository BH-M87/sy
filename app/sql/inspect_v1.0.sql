CREATE TABLE `ps_inspect_point` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `communityId` varchar(30) NOT NULL COMMENT '小区Id',
  `name` varchar(50) NOT NULL COMMENT '巡检点名称',
  `address` varchar(50) NOT NULL COMMENT '巡检点位置',
  `deviceId` int(11) NULL DEFAULT '0' COMMENT '设备ID',
  `type` varchar(15) NOT NULL COMMENT '打卡方式：1扫码 2定位 3智点 4拍照 多个逗号隔开',
  `lon` decimal(10,6) DEFAULT '0.000000' COMMENT '经度',
  `lat` decimal(10,6) DEFAULT '0.000000' COMMENT '纬度',
  `location` varchar(50) NOT NULL COMMENT '定位位置',
  `remark` varchar(255) NULL DEFAULT '' COMMENT '备注',
  `codeImg` varchar(255) NULL DEFAULT '' COMMENT '二维码图片',
  `createAt` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='巡检点';

CREATE TABLE `ps_inspect_record_point` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(10) NOT NULL COMMENT '小区Id',
  `record_id` int(11) NOT NULL COMMENT '记录id',
  `point_id` int(11) NOT NULL COMMENT '巡检点id',
  `device_status` tinyint(1) DEFAULT '0' COMMENT '设备状态 1正常  2异常',
  `location` varchar(50) DEFAULT '' COMMENT '巡检地理位置',
  `lon` decimal(10,6) DEFAULT '0.000000' COMMENT '巡检经度',
  `lat` decimal(10,6) DEFAULT '0.000000' COMMENT '巡检纬度',
  `imgs` varchar(500) DEFAULT '' COMMENT '巡检图片多图逗号分隔',
  `record_note` varchar(255) DEFAULT '' COMMENT '巡检记录',
  `status` tinyint(1) DEFAULT '1' COMMENT '巡检状态：1未巡检，2已巡检，3漏巡检',
  `point_name` varchar(50) NOT NULL COMMENT '巡检点名称',
  `type` varchar(15) NOT NULL COMMENT '打卡方式：1扫码 2定位 3智点 4拍照 多个逗号隔开',
  `point_location` varchar(50) DEFAULT NULL COMMENT '巡检点：地理位置',
  `point_lon` decimal(10,6) DEFAULT NULL COMMENT '巡检点：经度',
  `point_lat` decimal(10,6) DEFAULT NULL COMMENT '巡检点：纬度',
  `finish_at` int(11) DEFAULT NULL COMMENT '巡检提交时间',
  `create_at` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='巡检记录的巡检点表';

CREATE TABLE `ps_inspect_line` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `communityId` varchar(30) NOT NULL COMMENT '小区Id',
  `name` varchar(15) NOT NULL COMMENT '线路名称',
  `img` varchar(255) NULL DEFAULT '' COMMENT '路线图',
  `remark` varchar(255) NULL DEFAULT '' COMMENT '备注',
  `createAt` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='巡检线路表';

CREATE TABLE `ps_inspect_line_point` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `lineId` int(10) NOT NULL COMMENT '线路Id',
  `pointId` int(10) NOT NULL COMMENT '巡检点id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='巡检线路与巡检点关联关系表';

CREATE TABLE `ps_device` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `communityId` varchar(30) NULL DEFAULT '' COMMENT '小区Id',
  `name` varchar(50) NOT NULL COMMENT '设备名称',
  `deviceType` varchar(50) NOT NULL COMMENT '设备类型',
  `deviceNo` varchar(50) NOT NULL COMMENT '设备编号',
  `createAt` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='智点设备表';