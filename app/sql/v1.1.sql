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