CREATE TABLE `ps_repair_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '报事报修类别名称',
  `level` tinyint(1) NOT NULL DEFAULT '1' COMMENT '分类级别',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '分类父级id',
  `is_relate_room` tinyint(1) NOT NULL DEFAULT '2' COMMENT '是否关联房屋 1关联 2不关联',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否显示 1显示 2隐藏',
  `created_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='报事报修类型表';

CREATE TABLE `ps_repair` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `room_id` int(11) NOT NULL DEFAULT '0' COMMENT '房屋id',
  `contact_mobile` varchar(12) NOT NULL DEFAULT '' COMMENT '报事保修联系人',
  `repair_no` varchar(20) NOT NULL DEFAULT '' COMMENT '修理单号',
  `member_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员id',
  `room_username` varchar(20) NOT NULL DEFAULT '' COMMENT '住户名称',
  `room_address` varchar(255) NOT NULL DEFAULT '' COMMENT '报修地址',
  `appuser_id` int(11) NOT NULL DEFAULT '0' COMMENT 'app用户id',
  `repair_type_id` int(11) NOT NULL DEFAULT '0' COMMENT '报修类型id',
  `repair_content` varchar(200) NOT NULL DEFAULT '' COMMENT '报修类容',
  `expired_repair_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0未填写 1上午 2下午',
  `repair_imgs` varchar(1000) NOT NULL DEFAULT '' COMMENT '报修图片',
  `expired_repair_time` int(11) NOT NULL DEFAULT '0' COMMENT '期望修理时间',
  `repair_from` tinyint(1) NOT NULL DEFAULT '1' COMMENT '报事报修来源  1：C端报修  2物业后台报修  3邻易联app报修',
  `created_id` int(11) NOT NULL DEFAULT '0' COMMENT '提交人id',
  `created_username` varchar(50) NOT NULL DEFAULT '' COMMENT '提交人姓名',
  `operator_id` int(1) NOT NULL DEFAULT '0' COMMENT '当前操作人',
  `operator_name` varchar(50) NOT NULL DEFAULT '' COMMENT '当前操作人姓名',
  `is_assign` tinyint(1) NOT NULL DEFAULT '2' COMMENT '是否已分配 1已分配 2未分配',
  `pay_code_url` varchar(255) NOT NULL DEFAULT '' COMMENT '账单支付二维码',
  `is_pay` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 未支付，2 已支付 3无需支付',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '工单所需金额',
  `hard_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 一般问题，2 疑难问题',
  `hard_check_at` int(11) NOT NULL DEFAULT '0' COMMENT '标记为疑难问题时间',
  `hard_remark` varchar(150) NOT NULL DEFAULT '' COMMENT '疑难问题备注说明',
  `leave_msg` varchar(250) NOT NULL DEFAULT '' COMMENT '物业系统给报修客户的留言',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '订单状态 1待处理 2待完成 3已完成 4已结束 5已复核 6已作废 7待确认 8已驳回 9复核不通过',
  `day` date NOT NULL COMMENT '报修日期',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '提交订单时间',
  `is_assign_again` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0复核不通过可以再次发起订单，1已经重新发起过订单',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='报事报修表';

CREATE TABLE `ps_repair_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repair_id` int(11) NOT NULL DEFAULT '0' COMMENT '处理订单id',
  `content` varchar(255) DEFAULT '' COMMENT '处理内容，如果作废，这里是作废内容',
  `repair_imgs` varchar(1000) DEFAULT '' COMMENT '当前处理图片',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '操作类型 2 待完成，3 已完成，5复核，6作废，8驳回，9复核不通过',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '处理时间',
  `operator_id` int(11) NOT NULL DEFAULT '0' COMMENT '处理人id',
  `operator_name` varchar(20) DEFAULT '' COMMENT '处理人姓名',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='报事报修 处理结果表';

CREATE TABLE `ps_repair_materials_cate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `cate_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 通用类型 2小区自己的分类',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '耗材类别名称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='报事报修耗材分类表';

CREATE TABLE `ps_repair_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL COMMENT '小区id',
  `cate_id` tinyint(2) NOT NULL DEFAULT '0' COMMENT '耗材分类 枚举类型',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '耗材名称',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '单价',
  `price_unit` tinyint(1) NOT NULL DEFAULT '0' COMMENT '价格单位  1 米  2卷  3个 4次',
  `num` int(11) NOT NULL DEFAULT '0' COMMENT '材料数量',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '状态 1显示',
  `created_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='报事报修耗材表';

CREATE TABLE `ps_repair_bill_material` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repair_id` int(11) NOT NULL DEFAULT '0' COMMENT '报事报修id',
  `repair_bill_id` int(11) NOT NULL DEFAULT '0' COMMENT '报事报修订单id',
  `material_id` int(11) NOT NULL DEFAULT '0' COMMENT '耗材id',
  `num` tinyint(4) NOT NULL DEFAULT '0' COMMENT '使用数量',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='报事报修单耗材使用情况';

CREATE TABLE `ps_repair_bill` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repair_id` int(11) NOT NULL DEFAULT '0' COMMENT '报事报修单id',
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `community_name` varchar(255) NOT NULL DEFAULT '' COMMENT '小区名称',
  `property_company_id` int(11) NOT NULL COMMENT '物业公司id',
  `order_no` varchar(100) DEFAULT '' COMMENT '账单编号',
  `property_alipay_account` varchar(100) NOT NULL COMMENT '物业公司支付宝账号',
  `materials_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '材料总费用',
  `other_charge` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '其他费用',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付总金额',
  `out_trade_no` varchar(64) DEFAULT NULL COMMENT '系统生成的支付流水',
  `trade_no` varchar(64) NOT NULL DEFAULT '' COMMENT '交易流水号',
  `buyer_login_id` varchar(100) NOT NULL DEFAULT '' COMMENT '付款方支付宝账号',
  `buyer_user_id` varchar(100) NOT NULL DEFAULT '' COMMENT '支付宝user_id',
  `seller_id` varchar(100) DEFAULT '',
  `pay_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付状态  0 待支付 1支付成功 2支付失败',
  `note` varchar(200) DEFAULT '' COMMENT '备注',
  `pay_type` tinyint(3) DEFAULT '1' COMMENT '1.线上 2:线下支付',
  `paid_at` int(11) NOT NULL DEFAULT '0' COMMENT '支付时间',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '账单创建时间',
  PRIMARY KEY (`id`),
  KEY `repair_id` (`repair_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='报事报修账单表';

CREATE TABLE `ps_repair_assign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repair_id` int(11) NOT NULL DEFAULT '0' COMMENT '报事报修单id',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '维修工人id',
  `remark` varchar(250) DEFAULT NULL COMMENT '物业备注',
  `is_operate` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0，用户不能操作，1用户可操作',
  `finish_time` int(11) NOT NULL DEFAULT '0' COMMENT '期望完成时间',
  `operator_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人id',
  `created_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `repair_id` (`repair_id`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='报事报修单指派记录';

CREATE TABLE `ps_repair_appraise` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repair_id` int(11) NOT NULL DEFAULT '0' COMMENT '报事报修id',
  `start_num` tinyint(2) NOT NULL,
  `appraise_labels` varchar(255) NOT NULL DEFAULT '' COMMENT '评价标签，多个以逗号隔开',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '评价内容',
  `created_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=187 DEFAULT CHARSET=utf8 COMMENT='报事报修点赞记录表' ;

CREATE TABLE `ps_user_community` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `manage_id` int(11) NOT NULL COMMENT '用户id',
  `community_id` int(11) NOT NULL COMMENT '小区id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32558 DEFAULT CHARSET=utf8 COMMENT='管理员关联小区表';

CREATE TABLE `ps_app_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_user_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='支付宝会员关系表(多对一)';

CREATE TABLE `ps_app_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nick_name` varchar(20) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(20) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `phone` char(11) NOT NULL DEFAULT '' COMMENT '用户手机号',
  `user_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '用户类型 1：支付宝',
  `id_card` varchar(25) NOT NULL DEFAULT '' COMMENT '身份证号',
  `user_ref` tinyint(1) NOT NULL DEFAULT '1' COMMENT '用户来源 1支付宝生活号用户 ',
  `access_token` varchar(255) NOT NULL COMMENT '用户令牌',
  `expires_in` int(11) NOT NULL DEFAULT '0' COMMENT '令牌过期时间',
  `refresh_token` varchar(255) NOT NULL COMMENT '更新令牌值',
  `channel_user_id` varchar(100) NOT NULL COMMENT '渠道授权用户id，支付宝独有，用以关联查询账单状态等',
  `ali_user_id` varchar(100) NOT NULL DEFAULT '' COMMENT '支付宝用户id',
  `avatar` varchar(200) NOT NULL DEFAULT '' COMMENT '用户头像',
  `gender` tinyint(1) NOT NULL DEFAULT '0' COMMENT '性别 0未知 1男 2女',
  `is_certified` tinyint(1) DEFAULT '2' COMMENT '是否通过支付宝实名认证：1通过 2未通过',
  `authtoken` varchar(255) DEFAULT '' COMMENT '会员卡授权码',
  `biz_card_no` varchar(100) DEFAULT '' COMMENT '会员卡号',
  `sign` varchar(500) DEFAULT NULL,
  `set_no` int(11) DEFAULT '0' COMMENT '编号，用于未认证无昵称的用户',
  `last_city_code` varchar(64) DEFAULT NULL COMMENT '最近访问城市编号',
  `last_city_name` varchar(50) DEFAULT NULL COMMENT '最近访问城市名称',
  `last_comm_id` int(11) NOT NULL DEFAULT '0' COMMENT '最近一次访问的小区id',
  `keys` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已经编辑过常用钥匙',
  `is_guide` tinyint(1) DEFAULT '1' COMMENT '是否有蒙层指导:1.是,2否',
  `num` tinyint(2) DEFAULT NULL COMMENT '查询账单次数,每天五次',
  `sel_time` int(10) DEFAULT NULL COMMENT '查询账单的时间:每天五次',
  `plate_number` varchar(10) NOT NULL DEFAULT '' COMMENT '车牌号',
  `create_at` int(11) DEFAULT NULL COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='授权用户表';

CREATE TABLE `door_room_password` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL DEFAULT '0' COMMENT '房屋id',
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `unit_id` int(11) NOT NULL DEFAULT '0' COMMENT '楼宇id',
  `member_id` int(11) NOT NULL DEFAULT '0' COMMENT '住户id',
  `code` varchar(100) NOT NULL DEFAULT '' COMMENT '住户密码',
  `code_img` varchar(100) NOT NULL DEFAULT '' COMMENT '二维码图片地址',
  `expired_time` int(11) NOT NULL DEFAULT '0' COMMENT '有效期',
  `created_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='住户密码表';

CREATE TABLE `door_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `supplier_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商id',
  `capture_photo` varchar(1000) DEFAULT '' COMMENT '抓拍图片',
  `open_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '开门方式 1 人脸开门， 2 蓝牙开门， 3 密码开门, 4 钥匙开门, 5 门卡开门，6 扫码开门, 7 临时密码 8二维码开门',
  `open_time` int(11) NOT NULL COMMENT '开门时间',
  `user_name` varchar(50) DEFAULT '' COMMENT '用户姓名',
  `user_phone` varchar(15) DEFAULT '' COMMENT '住户手机号',
  `user_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '被呼叫用户类型:1业主 2家人 3租客 4访客',
  `card_no` varchar(50) DEFAULT '' COMMENT '门卡卡号',
  `device_name` varchar(50) NOT NULL DEFAULT '' COMMENT '设备名称',
  `device_no` varchar(80) NOT NULL DEFAULT '' COMMENT '设备编号',
  `group` varchar(20) DEFAULT '' COMMENT '苑期区',
  `building` varchar(20) DEFAULT '' COMMENT '幢',
  `unit` varchar(20) DEFAULT '' COMMENT '单元',
  `room` varchar(20) DEFAULT NULL COMMENT '室',
  `room_id` int(11) DEFAULT '0' COMMENT '室',
  `coat_color` tinyint(2) NOT NULL DEFAULT '0' COMMENT '上衣颜色',
  `coat_color_str` varchar(10) NOT NULL DEFAULT '' COMMENT '上衣颜色描述',
  `coat_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '上衣类型',
  `coat_type_str` varchar(10) NOT NULL DEFAULT '' COMMENT '上衣类型描述',
  `trousers_color` tinyint(2) NOT NULL DEFAULT '0' COMMENT '下衣颜色',
  `trousers_color_str` varchar(10) NOT NULL DEFAULT '' COMMENT '下衣颜色描述',
  `trousers_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '下衣类型',
  `trousers_type_str` varchar(10) NOT NULL DEFAULT '' COMMENT '下衣类型描述',
  `has_hat` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否带帽子 1不戴帽子 2戴帽子',
  `has_bag` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否背包 1不带包 2带包',
  `device_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '设备类型 1入门设备，2出门设备',
  `create_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cs` (`community_id`,`supplier_id`) USING BTREE,
  KEY `time_index` (`open_time`) USING BTREE,
  KEY `device_no` (`device_no`) USING BTREE,
  KEY `open_type` (`open_type`) USING BTREE,
  KEY `user_phone` (`user_phone`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='开门记录表';


CREATE TABLE `door_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `supplier_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商id',
  `capture_photo` varchar(300) DEFAULT '' COMMENT '抓拍的图片',
  `call_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '呼叫方式：1手机呼叫 2房号呼叫',
  `call_time` int(11) NOT NULL DEFAULT '0' COMMENT '呼叫时间',
  `user_name` varchar(20) DEFAULT '' COMMENT '被呼叫用户姓名',
  `user_phone` varchar(15) DEFAULT '' COMMENT '被呼叫人手机号',
  `user_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '被呼叫用户类型:1业主 2家人 3租客 4访客',
  `device_no` varchar(50) DEFAULT '0' COMMENT '设备编号',
  `device_name` varchar(50) DEFAULT '' COMMENT '设备名称',
  `group` varchar(20) DEFAULT NULL COMMENT '苑期区',
  `building` varchar(20) DEFAULT NULL,
  `unit` varchar(20) DEFAULT '0' COMMENT '楼宇幢单元',
  `room` varchar(20) DEFAULT NULL COMMENT '室',
  `room_id` int(11) DEFAULT '0' COMMENT '室',
  `created_at` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='门禁抓拍记录表';

CREATE TABLE `door_last_visit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区Id',
  `community_name` varchar(50) NOT NULL COMMENT '小区名称',
  `room_id` int(11) NOT NULL COMMENT '最后一次访问房屋id',
  `out_room_id` varchar(100) NOT NULL COMMENT '商户系统小区房屋唯一ID标示',
  `room_address` varchar(200) NOT NULL COMMENT '最后一次访问房屋地址',
  `member_id` int(11) NOT NULL DEFAULT '0' COMMENT '业主id',
  `update_at` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='用户选择房屋表';

CREATE TABLE `door_key` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `community_name` varchar(50) DEFAULT '' COMMENT '小区名称',
  `device_id` int(11) NOT NULL DEFAULT '0' COMMENT '门禁id',
  `room_id` int(11) NOT NULL DEFAULT '0' COMMENT '室id',
  `member_id` int(11) NOT NULL DEFAULT '0' COMMENT '业主id',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '插入时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='门禁常用钥匙表';

CREATE TABLE `door_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL COMMENT '小区id',
  `supplier_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商id',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '门禁名称',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '设备类型：1单元机2围墙机',
  `device_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '开门类型，1入门设备，2出门设备',
  `device_id` varchar(100) NOT NULL DEFAULT '' COMMENT '设备序列号',
  `note` text,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1启用 2禁用',
  `online_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1在线 2离线 3设备常开，未自动关闭',
  `open_door_type` varchar(10) NOT NULL DEFAULT '0' COMMENT '开门方式类型:1.人脸,2.蓝牙,3.二维码,4.电子钥匙,5密码 说明:多个方式逗号分隔',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='门禁设备表';

CREATE TABLE `door_device_unit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `devices_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联设备表Id',
  `group_id` int(11) NOT NULL DEFAULT '0' COMMENT '苑期区id',
  `building_id` int(11) NOT NULL DEFAULT '0' COMMENT '楼幢id',
  `unit_id` int(11) NOT NULL DEFAULT '0' COMMENT '单元Id',
  `created_at` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='门禁权限表';

CREATE TABLE `door_device_broken` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `supplier_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商id',
  `deviceNo` varchar(50) NOT NULL COMMENT '设备编号',
  `deviceName` varchar(50) DEFAULT NULL COMMENT '设备名称',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '设备状态 1在线 2离线 3设备常开，未自动关闭',
  `created_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='设备故障表';

CREATE TABLE `ps_room_vistors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL DEFAULT '0' COMMENT '房屋id',
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `group` varchar(32) NOT NULL DEFAULT '' COMMENT '苑期区',
  `building` varchar(32) NOT NULL DEFAULT '' COMMENT '幢',
  `unit` varchar(32) NOT NULL DEFAULT '' COMMENT '单元',
  `room` varchar(64) NOT NULL DEFAULT '' COMMENT '室',
  `app_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '添加此访客的支付宝用户id',
  `member_id` int(11) NOT NULL DEFAULT '0' COMMENT '住户id',
  `vistor_name` varchar(20) NOT NULL DEFAULT '' COMMENT '访客姓名',
  `vistor_mobile` varchar(15) NOT NULL DEFAULT '' COMMENT '访客手机号',
  `vistor_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '访客类型 1临时访客 2预约访客',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '到访开始时间',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '到访结束时间',
  `device_name` varchar(50) NOT NULL DEFAULT '' COMMENT '门禁名称',
  `code` varchar(20) NOT NULL DEFAULT '' COMMENT '访客密码',
  `qrcode` varchar(100) DEFAULT '' COMMENT '二维码url',
  `car_number` varchar(15) NOT NULL DEFAULT '' COMMENT '车牌号',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '访问状态 1待访 2已访 3过期',
  `is_cancel` tinyint(1) DEFAULT '2' COMMENT '取消邀请:1是，2否',
  `is_del` tinyint(1) DEFAULT '2' COMMENT '是否删除：1是。2否',
  `is_msg` tinyint(1) DEFAULT '2' COMMENT '发送短信:1已发，2未发',
  `people_num` tinyint(2) NOT NULL DEFAULT '1' COMMENT '来访人数',
  `reason_type` tinyint(1) DEFAULT '0' COMMENT '来访事由：1亲戚朋友，2中介看房，3搬家放行，4送货上门，5装修放行，6家政服务，9其他',
  `reason` varchar(200) DEFAULT '' COMMENT '来访事由为其他时的备注',
  `addtion_id` int(10) DEFAULT '0' COMMENT '补录人ID',
  `addtion_prople` varchar(20) DEFAULT '' COMMENT '补录人名称',
  `passage_at` int(10) DEFAULT '0' COMMENT '通行时间',
  `passage_num` tinyint(1) DEFAULT '0' COMMENT '通行次数：不能超过3次',
  `face_url` varchar(400) DEFAULT '' COMMENT '人脸图片',
  `sex` tinyint(1) NOT NULL DEFAULT '1' COMMENT '性别，1男，2女',
  `sync` tinyint(1) NOT NULL DEFAULT '0' COMMENT '同步到期访客给java，0还未同步，1同步删除成功，2同步失败',
  `created_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='房屋对应的访客表';

CREATE TABLE `st_communist` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `mobile` varchar(13) NOT NULL DEFAULT '' COMMENT '手机号',
  `image` varchar(200) NOT NULL DEFAULT '' COMMENT '头像',
  `sex` tinyint(1) NOT NULL DEFAULT '1' COMMENT '性别 1男 2女',
  `birth_time` int(11) NOT NULL DEFAULT '0' COMMENT '出生日期',
  `join_party_time` int(11) NOT NULL DEFAULT '0' COMMENT '入党日期',
  `formal_time` int(11) NOT NULL DEFAULT '0' COMMENT '转正日期',
  `branch` varchar(50) NOT NULL DEFAULT '' COMMENT '所在支部',
  `job` varchar(50) NOT NULL DEFAULT '' COMMENT '党内职务',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '党员类型：1离退休党员、2流动党员、3困难党员、4下岗失业党员、5在职党员',
  `station_id` int(11) NOT NULL DEFAULT '0' COMMENT '先锋岗位id',
  `pioneer_value` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '获得的先锋值',
  `operator_id` int(11) NOT NULL DEFAULT '0' COMMENT '创建人id',
  `operator_name` varchar(20) NOT NULL DEFAULT '' COMMENT '创建人',
  `create_at` int(11) NOT NULL COMMENT '创建时间',
  `is_authentication` tinyint(1) NOT NULL DEFAULT '2' COMMENT '是否支付宝认证 1是 2否',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '支付宝ps_app_user用户id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='党员表';

CREATE TABLE `st_station` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `station` varchar(30) NOT NULL DEFAULT '' COMMENT '先锋岗名称',
  `content` varchar(200) NOT NULL DEFAULT '' COMMENT '描述',
  `operator_id` int(11) NOT NULL COMMENT '创建人id',
  `operator_name` varchar(20) NOT NULL DEFAULT '' COMMENT '操作人名',
  `create_at` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='先锋岗位';


CREATE TABLE `st_party_task` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '任务id',
  `station_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '先锋岗位id',
  `task_name` varchar(30) NOT NULL DEFAULT '' COMMENT '任务标题',
  `task_introdu` varchar(100) NOT NULL DEFAULT '' COMMENT '任务简介',
  `pioneer_value` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '先锋值',
  `expire_time_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '领取截止时间类型 1 长期有效  2指定日期',
  `expire_time` int(11) NOT NULL DEFAULT '0' COMMENT '领取截止时间',
  `party_address` varchar(50) NOT NULL DEFAULT '' COMMENT '任务地址',
  `contact_name` varchar(10) NOT NULL DEFAULT '' COMMENT '联系人名称',
  `contact_phone` varchar(12) NOT NULL DEFAULT '' COMMENT '联系人手机号码',
  `is_location` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否需要定位 1是 2否',
  `task_details` text NOT NULL COMMENT '任务详情',
  `create_at` int(11) unsigned NOT NULL COMMENT '创建时间',
  `operator_id` int(11) NOT NULL COMMENT '创建人id',
  `operator_name` varchar(20) NOT NULL DEFAULT '' COMMENT '操作人名',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='党建任务表';


CREATE TABLE `st_party_task_station` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `task_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '任务id',
  `communist_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '党员id',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '审核状态 1=待完成 2=审核中 3=已审核 4=已取消',
  `pioneer_value` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '获得的先锋值',
  `create_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='党员任务关联表';

CREATE TABLE `st_party_task_operate_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `party_task_station_id` int(11) NOT NULL DEFAULT '0' COMMENT '党员认领任务操作记录表',
  `task_id` int(11) NOT NULL DEFAULT '0' COMMENT '任务id',
  `communist_id` int(11) NOT NULL DEFAULT '0' COMMENT '党员id',
  `operate_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '操作类型 1完成 2审核完成 3取消',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '输入内容  operate_type1时为完成内容 2 审核说明 3 时为取消理由',
  `images` varchar(500) NOT NULL DEFAULT '' COMMENT '上传图片，多个以逗号相连',
  `location` varchar(255) NOT NULL DEFAULT '' COMMENT '当前位置',
  `lon` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '所在位置经度值',
  `lat` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '所在位置纬度值',
  `pioneer_value` int(11) NOT NULL DEFAULT '0' COMMENT '评分  operate_type 为 2时必填',
  `operator_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人id',
  `operator_name` varchar(20) NOT NULL DEFAULT '' COMMENT '操作人名',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='党建认领任务操作记录表';

CREATE TABLE `st_notice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '消息类型 1通知 2消息',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '公告标题',
  `describe` varchar(200) DEFAULT '' COMMENT '简介',
  `content` text NOT NULL COMMENT '发送内容',
  `operator_id` int(11) NOT NULL DEFAULT '0' COMMENT '创建人id',
  `operator_name` varchar(20) NOT NULL DEFAULT '' COMMENT '创建人姓名',
  `accessory_file` varchar(500) DEFAULT '' COMMENT '附件，多个以逗号相连',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='通知通报表';

CREATE TABLE `st_notice_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notice_id` int(11) NOT NULL COMMENT '通知ID',
  `receive_user_id` int(11) NOT NULL COMMENT '通知接收者用户id',
  `receive_user_name` varchar(20) NOT NULL DEFAULT '' COMMENT '通知接收者用户名',
  `is_send` tinyint(1) NOT NULL DEFAULT '1' COMMENT '消息是否推送：1暂未推送 2已推送',
  `is_read` tinyint(1) NOT NULL DEFAULT '1' COMMENT '消息是否已读：1未读 2已读',
  `create_at` int(11) NOT NULL COMMENT '创建时间',
  `send_at` int(11) NOT NULL DEFAULT '0' COMMENT '发送时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='通知通报与人关联表';

CREATE TABLE `st_xz_task_template` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '任务名称',
  `task_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '任务类型 1常规任务、2指令任务、3工作日志',
  `task_attribute_id` int(10) NOT NULL DEFAULT '0' COMMENT '任务类别',
  `start_date` int(10) NOT NULL DEFAULT '0' COMMENT '开始时间，精准到天',
  `end_date` int(10) NOT NULL DEFAULT '0' COMMENT '结束时间，精准到天',
  `exec_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '执行类型 1按天执行，2按周执行，3按月执行',
  `interval_y` tinyint(2) NOT NULL DEFAULT '0' COMMENT '间隔扩展值 如，每周周y，每月y号',
  `contact_mobile` varchar(12) NOT NULL DEFAULT '' COMMENT '联系电话',
  `describe` varchar(200) DEFAULT '' COMMENT '任务描述',
  `exec_users` varchar(200) NOT NULL DEFAULT '' COMMENT '执行人员，多个以逗号隔开',
  `accessory_file` varchar(500) DEFAULT '' COMMENT '附件，多个以逗号相连',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '是否显示 1显示 2隐藏',
  `operator_id` int(10) NOT NULL DEFAULT '0' COMMENT '创建人id',
  `operator_name` varchar(20) NOT NULL DEFAULT '' COMMENT '创建人名称',
  `created_at` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COMMENT='行政居务任务模板表';

CREATE TABLE `st_xz_task_attribute` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '类别名称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='行政居务任务类别表';

CREATE TABLE `st_xz_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '任务执行人id',
  `user_name` int(11) NOT NULL DEFAULT '0' COMMENT '任务执行人名',
  `task_template_id` int(11) NOT NULL DEFAULT '0' COMMENT '任务模板id',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '任务开始时间',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '任务结束时间',
  `status` tinyint(1) NOT NULL DEFAULT '2' COMMENT '1待处理  2已处理',
  `check_content` varchar(500) NOT NULL DEFAULT '' COMMENT '处理提交具体内容',
  `check_images` varchar(500) NOT NULL DEFAULT '' COMMENT '处理提交具体图片，多个以逗号相连',
  `check_location_lon` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '记录提交时所在位置经度值',
  `check_location_lat` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '记录提交时所在位置纬度值',
  `check_location` varchar(255) NOT NULL DEFAULT '' COMMENT '提交时所在位置',
  `created_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=353 DEFAULT CHARSET=utf8 COMMENT='行政居务任务表';

ALTER TABLE `st_party_task_operate_record`
ADD COLUMN `info`  varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '完成内容' AFTER `create_at`;

ALTER TABLE `ps_device`
ADD COLUMN `lon`  decimal(10,6) NOT NULL DEFAULT 0.000000 COMMENT '所在位置经度值' AFTER `install_place`,
ADD COLUMN `lat`  decimal(10,6) NOT NULL DEFAULT 0.000000 COMMENT '所在位置纬度值' AFTER `lon`;

ALTER TABLE `st_party_task_station`
ADD COLUMN `update_at`  int(11) NOT NULL AFTER `pioneer_value`;






















