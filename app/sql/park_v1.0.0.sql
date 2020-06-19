CREATE TABLE `ps_park_set` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`community_id` varchar(50) NOT NULL DEFAULT '' COMMENT '小区id',
`community_name` varchar(200) NOT NULL DEFAULT '' COMMENT '小区名称',
`if_one` tinyint(1) DEFAULT '1' COMMENT '是否一车一库 1是 2否',
`if_visit` tinyint(1) DEFAULT '1' COMMENT '是否允许访客车辆 1是 2否',
`cancle_num` int(11) DEFAULT '3' COMMENT '当天最多取消预约次数',
`late_at` int(11) DEFAULT '15' COMMENT '迟到取消预约时间',
`due_notice` int(11) DEFAULT '15' COMMENT '车位预约到期提前通知时间',
`end_at` int(11) DEFAULT '15' COMMENT '预约截止时间',
`black_num` int(11) DEFAULT '3' COMMENT '黑名单违约数',
`appointment` int(11) DEFAULT '0' COMMENT '预约超时',
`appointment_unit` tinyint(1) DEFAULT '1' COMMENT '预约超时单位 1分钟 2小时',
`lock` int(11) DEFAULT '0' COMMENT '锁定时间',
`lock_unit` tinyint(1) DEFAULT '1' COMMENT '锁定时间单位 1分钟 2小时 3天 4周 5月',
`min_time` decimal(12,1) DEFAULT '0' COMMENT '共享最小计时单位',
`integral` int(11) DEFAULT '0' COMMENT '预约成功获取积分',
`create_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
PRIMARY KEY (`id`)
) ENGINE=InnoDB CHARSET=utf8 COMMENT='系统设置表';

CREATE TABLE `ps_park_black` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`community_id` varchar(50) NOT NULL DEFAULT '' COMMENT '小区id',
`community_name` varchar(200) NOT NULL DEFAULT '' COMMENT '小区名称',
`room_id` varchar(50) NOT NULL DEFAULT '' COMMENT '房屋ID',
`room_name` varchar(50) NOT NULL DEFAULT '' COMMENT '房号',
`name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
`mobile` varchar(50) NOT NULL DEFAULT '' COMMENT '联系电话',
`num` int(11) DEFAULT '0' COMMENT '累计违约',
`create_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
PRIMARY KEY (`id`)
) ENGINE=InnoDB CHARSET=utf8 COMMENT='黑名单表';

CREATE TABLE `ps_park_break_promise` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`community_id` varchar(50) NOT NULL DEFAULT '' COMMENT '小区id',
`community_name` varchar(200) NOT NULL DEFAULT '' COMMENT '小区名称',
`room_id` varchar(50) NOT NULL DEFAULT '' COMMENT '房屋ID',
`room_name` varchar(50) NOT NULL DEFAULT '' COMMENT '房号',
`name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
`mobile` varchar(50) NOT NULL DEFAULT '' COMMENT '联系电话',
`car_number` varchar(15) NOT NULL DEFAULT '' COMMENT '车牌号',
`break_time` int(11) NOT NULL DEFAULT 0 COMMENT '违约时长',
`num` int(11) DEFAULT '0' COMMENT '累计违约',
`lock_at` int(11) NOT NULL DEFAULT '0' COMMENT '锁定时间',
`create_at` int(11) NOT NULL DEFAULT '0' COMMENT '新增时间',
PRIMARY KEY (`id`)
) ENGINE=InnoDB CHARSET=utf8 COMMENT='违约表';



CREATE TABLE `ps_park_shared` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` varchar(30) NOT NULL DEFAULT '' COMMENT '小区Id',
  `community_name` varchar(30) NOT NULL DEFAULT '' COMMENT '小区名称',
  `room_id` varchar(30) NOT NULL DEFAULT '' COMMENT '房屋id',
  `room_name` varchar(50) NOT NULL DEFAULT '' COMMENT '房号',
  `publish_id` varchar(30) NOT NULL DEFAULT '' COMMENT '发布人id',
  `publish_name` varchar(30) NOT NULL DEFAULT '' COMMENT '发布人名称',
  `publish_mobile` varchar(30) NOT NULL DEFAULT '' COMMENT '发布人手机',
  `park_space` varchar(5) NOT NULL DEFAULT '' COMMENT '车位号',
  `start_date` int(11) NOT NULL DEFAULT '0' COMMENT '开始日期',
  `end_date` int(11) NOT NULL DEFAULT '0' COMMENT '结束日期',
  `start_at` varchar(10) NOT NULL DEFAULT '' COMMENT '开始时间',
  `end_at` varchar(10) NOT NULL DEFAULT '' COMMENT '结束时间',
  `ali_form_id` varchar(100) NOT NULL DEFAULT '' COMMENT '支付宝表单id',
  `ali_user_id` varchar(100) NOT NULL DEFAULT '' COMMENT '支付宝用户id',
  `exec_type_msg` varchar(200) NOT NULL DEFAULT '' COMMENT '一周执行规律日期',
  `is_del` tinyint(2) NOT NULL DEFAULT '1' COMMENT '是否删除 1没有删除 2已删除',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_at` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='发布共享';



CREATE TABLE `ps_park_space` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` varchar(30) NOT NULL DEFAULT '' COMMENT '小区Id',
  `community_name` varchar(30) NOT NULL DEFAULT '' COMMENT '小区名称',
  `room_id` varchar(30) NOT NULL DEFAULT '' COMMENT '房屋id',
  `room_name` varchar(50) NOT NULL DEFAULT '' COMMENT '房号',
  `publish_id` varchar(30) NOT NULL DEFAULT '' COMMENT '发布人id',
  `publish_name` varchar(30) NOT NULL DEFAULT '' COMMENT '发布人名称',
  `publish_mobile` varchar(30) NOT NULL DEFAULT '' COMMENT '发布人手机',
  `shared_id` int(11) NOT NULL DEFAULT '0' COMMENT '共享ID',
  `park_space` varchar(5) NOT NULL DEFAULT '' COMMENT '车位号',
  `shared_at` int(11) NOT NULL DEFAULT '0' COMMENT '共享日期',
  `start_at` int(11) NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_at` int(11) NOT NULL DEFAULT '0' COMMENT '结束时间',
  `status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '共享状态，1待预约 2已预约 3使用中 4已关闭 5已完成',
  `is_del` tinyint(3) NOT NULL DEFAULT '1' COMMENT '是否删除 1未删除 2 已删除',
  `notice_15` tinyint(3) NOT NULL DEFAULT '1' COMMENT '15分钟前判断 1没有发送通知 2发送过通知',
  `notice_5` tinyint(3) NOT NULL DEFAULT '1' COMMENT '5分钟前判断 1没有发送通知 2发送过通知',
  `score` int(4) NOT NULL DEFAULT '0' COMMENT '积分',
  `ali_form_id` varchar(100) NOT NULL DEFAULT '' COMMENT '支付宝表单id',
  `ali_user_id` varchar(100) NOT NULL DEFAULT '' COMMENT '支付宝用户id',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_at` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='共享车位';

CREATE TABLE `ps_park_reservation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` varchar(30) NOT NULL DEFAULT '' COMMENT '小区Id',
  `community_name` varchar(30) NOT NULL DEFAULT '' COMMENT '小区名称',
  `room_id` varchar(30) NOT NULL DEFAULT '' COMMENT '房屋id',
  `room_name` varchar(50) NOT NULL DEFAULT '' COMMENT '房号',
  `space_id` int(11) NOT NULL DEFAULT '0' COMMENT '预约车位id',
  `start_at` int(11) NOT NULL DEFAULT '0' COMMENT '共享开始时间',
  `end_at` int(11) NOT NULL DEFAULT '0' COMMENT '共享结束时间',
  `appointment_id` varchar(30) NOT NULL DEFAULT '' COMMENT '预约人id',
  `appointment_name` varchar(30) NOT NULL DEFAULT '' COMMENT '预约人名称',
  `appointment_mobile` varchar(30) NOT NULL DEFAULT '' COMMENT '预约人电话',
  `car_number` varchar(10) NOT NULL DEFAULT '' COMMENT '预约车牌',
  `enter_at` int(11) NOT NULL DEFAULT '0' COMMENT '入场时间',
  `out_at` int(11) NOT NULL DEFAULT '0' COMMENT '离场时间',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '状态  1已预约 2使用中 3已超时 4已关闭 5已取消 6已完成',
  `ali_form_id` varchar(100) NOT NULL DEFAULT '' COMMENT '支付宝表单id',
  `ali_user_id` varchar(100) NOT NULL DEFAULT '' COMMENT '支付宝用户id',
  `is_del` tinyint(3) NOT NULL DEFAULT '1' COMMENT '1未删除 2已删除（发布人，系统）',
  `crop_id` varchar(30) NOT NULL DEFAULT '' COMMENT '后台登陆租户id',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_at` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='预约记录';



CREATE TABLE `ps_park_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` varchar(30) NOT NULL DEFAULT '' COMMENT '小区Id',
  `community_name`  varchar(30) NOT NULL DEFAULT '' COMMENT '小区名称',
  `user_id` varchar(30) NOT NULL DEFAULT '' COMMENT '接收人id',
  `type` tinyint(3) NOT NULL DEFAULT 0 COMMENT '类型 1已预约 2积分',
  `content`  varchar(100) NOT NULL DEFAULT '' COMMENT '消息内容',
  `create_at` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_at` int(11) NOT NULL DEFAULT 0 COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB CHARSET=utf8 COMMENT='消息';
