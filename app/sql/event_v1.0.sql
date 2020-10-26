CREATE TABLE `ps_event` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `jd_id` varchar(20) NOT NULL DEFAULT '' COMMENT '街道id',
  `jd_name` varchar(100) NOT NULL DEFAULT '' COMMENT '街道名称',
  `sq_id` varchar(20) NOT NULL DEFAULT '' COMMENT '社区id',
  `sq_name` varchar(100) NOT NULL DEFAULT '' COMMENT '社区名称',
  `xq_id` varchar(20) NOT NULL DEFAULT '' COMMENT '小区id',
  `xq_name` varchar(100) NOT NULL DEFAULT '' COMMENT '小区名称',
  `wy_id` varchar(20) NOT NULL DEFAULT '' COMMENT '物业id',
  `wy_name` varchar(100) NOT NULL DEFAULT '' COMMENT '物业名称',
  `contacts_name` varchar(20) NOT NULL DEFAULT '' COMMENT '联系人',
  `contacts_mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '联系人电话',
  `address` varchar(200) DEFAULT '' COMMENT '地址',
  `event_time` int(10) NOT NULL COMMENT '上报时间',
  `event_content` varchar(1000) NOT NULL COMMENT '事件内容',
  `source` tinyint(2) DEFAULT '1' COMMENT '来源：1街道，2区数据局',
  `event_img` text COMMENT '事件照片',
  `status` tinyint(2) DEFAULT '1' COMMENT '状态：1待处理，2处理中，3已办结，4已驳回',
  `is_close` tinyint(2) DEFAULT '0' COMMENT '是否结案：1未结案，2已结案',
  `create_id` varchar(20) NOT NULL COMMENT '新增用户id',
  `create_name` varchar(20) NOT NULL COMMENT '新增用户名称',
  `property_user` varchar(20) NOT NULL COMMENT '物业钉钉管理员(平台用户id)',
  `create_at` int(10) DEFAULT NULL COMMENT '新增时间',
  `update_at` int(10) DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='事件处置表';

CREATE TABLE `ps_event_comment` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `event_id` int(10) NOT NULL COMMENT '事件id',
  `comment` varchar(1000) DEFAULT NULL COMMENT '评价内容',
  `create_id` varchar(20) NOT NULL COMMENT '评价用户id',
  `create_name` varchar(20) NOT NULL COMMENT '评价用户名称',
  `create_at` int(10) NOT NULL COMMENT '评价时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='事件评价表';

CREATE TABLE `ps_event_process` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `event_id` int(10) NOT NULL COMMENT '事件id',
  `status` tinyint(2) NOT NULL COMMENT '处置状态；1-已签收 2-已办结 3-已驳回 4-已结案',
  `content` varchar(1000) DEFAULT NULL COMMENT '处置内容',
  `process_img` text COMMENT '处置图片',
  `create_id` varchar(20) NOT NULL COMMENT '处置用户id',
  `create_name` varchar(20) NOT NULL COMMENT '处置用户名称',
  `create_at` int(10) NOT NULL COMMENT '处置时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='事件处置过程表';

