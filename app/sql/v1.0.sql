CREATE TABLE `ps_property_company` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联用户id',
  `agent_id` int(11) NOT NULL DEFAULT '0' COMMENT '代理商id',
  `property_name` varchar(100) NOT NULL DEFAULT '' COMMENT '物业公司',
  `property_type` int(2) NOT NULL DEFAULT '0' COMMENT '物业类型 1物业 2开发商 3家装 4零售 5其他',
  `business_license` char(20) NOT NULL DEFAULT '' COMMENT '营业执照号',
  `business_img` varchar(255) NOT NULL DEFAULT '' COMMENT '营业执照图片',
  `business_img_local` varchar(255) NOT NULL DEFAULT '' COMMENT '本地图片绝对路径',
  `mcc_code` varchar(50) NOT NULL DEFAULT 'S_S02_6513' COMMENT '经营类目编码',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父节点',
  `company_level` int(2) NOT NULL DEFAULT '1' COMMENT '公司层级',
  `company_type` int(2) NOT NULL DEFAULT '2' COMMENT '1=租户 2=公司',
  `company_logo` varchar(200) DEFAULT NULL COMMENT '公司logo',
  `company_desc` varchar(64) DEFAULT NULL,
  `province_code` varchar(32) DEFAULT NULL,
  `province_name` varchar(64) DEFAULT NULL,
  `city_code` varchar(32) DEFAULT NULL,
  `city_name` varchar(64) DEFAULT NULL,
  `area_code` varchar(11) DEFAULT NULL COMMENT '区编码',
  `area_name` varchar(255) DEFAULT NULL COMMENT '区名称',
  `address` varchar(200) DEFAULT NULL,
  `credit_code` varchar(32) DEFAULT NULL COMMENT '社会信用代码',
  `link_man` varchar(45) NOT NULL DEFAULT '' COMMENT '联系人',
  `link_phone` varchar(15) NOT NULL DEFAULT '' COMMENT '联系电话',
  `login_phone` varchar(15) DEFAULT '' COMMENT '登录关联手机号',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱地址',
  `alipay_account` varchar(100) DEFAULT '' COMMENT '支付宝账号',
  `seller_id` varchar(100) DEFAULT '' COMMENT '临时支付用的seller_id',
  `status` int(3) DEFAULT '1' COMMENT '状态 1启用 2禁用',
  `create_at` int(11) DEFAULT '0' COMMENT '创建时间',
  `nonce` char(32) NOT NULL DEFAULT '' COMMENT '物业公司随机码，用于授权回调验证',
  `lz_account` varchar(20) DEFAULT '' COMMENT '联掌门禁免登帐号',
  `has_sign_qrcode` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否签约当面付',
  `auth_type` int(2) DEFAULT NULL COMMENT '授权期限类型 1：永久授权 2：试用 3：自定义',
  `auth_start_time` datetime DEFAULT NULL COMMENT '授权期限开始时间',
  `auth_end_time` datetime DEFAULT NULL COMMENT '授权期限结束时间',
  `integral` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '积分',
  `card_roll` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '卡券',
  `balance` decimal(20,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '余额',
  `tenant_id` int(11) NOT NULL DEFAULT '0' COMMENT '租户id',
  `deleted` int(1) NOT NULL DEFAULT '0' COMMENT '1：已删除 0：未删除',
  `create_people` varchar(45) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `modify_people` varchar(45) DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='物业公司表';

CREATE TABLE `ps_community` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_no` varchar(64) DEFAULT NULL COMMENT '支付宝小区编号',
  `event_community_no` varchar(64) DEFAULT NULL,
  `province_code` varchar(64) NOT NULL DEFAULT '' COMMENT '所在省编码',
  `city_id` varchar(64) NOT NULL DEFAULT '' COMMENT '城市ID',
  `district_code` varchar(64) DEFAULT '' COMMENT '所在区编码',
  `pro_company_id` int(11) NOT NULL DEFAULT '0' COMMENT '物业ID',
  `name` varchar(100) NOT NULL COMMENT '小区名称',
  `locations` varchar(150) NOT NULL DEFAULT '' COMMENT '高德地图经度值,如 114.234534|22.567564,114.012534|22.567564',
  `longitude` decimal(10,6) NOT NULL DEFAULT '0.000000',
  `latitude` decimal(10,6) NOT NULL DEFAULT '0.000000',
  `address` varchar(150) NOT NULL COMMENT '地址',
  `phone` varchar(15) NOT NULL COMMENT '电话',
  `logo_url` varchar(255) NOT NULL DEFAULT '' COMMENT 'logo图片地址',
  `pinyin` varchar(5) NOT NULL DEFAULT '#' COMMENT '拼音',
  `community_code` varchar(15) DEFAULT '' COMMENT '小区唯一code',
  `comm_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '小区类型  1物业 2新房',
  `area_sign` varchar(10) DEFAULT '' COMMENT '地区标识，杭州，hz',
  `house_type` tinyint(2) DEFAULT '0' COMMENT '1:老旧小区，2：商品房，3安置小区',
  `build_time` int(11) NOT NULL DEFAULT '0' COMMENT '小区建成时间',
  `delivery_time` int(11) NOT NULL DEFAULT '0' COMMENT '交付时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1启用 2禁用',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_idex_community_no` (`event_community_no`),
  KEY `district_code` (`district_code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='小区表';

CREATE TABLE `ps_community_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '苑/期/区名称',
  `groups_code` varchar(20) DEFAULT '' COMMENT '苑期区唯一code',
  `code` varchar(2) NOT NULL DEFAULT '0' COMMENT '苑/期/区编码，用作房屋呼叫',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='小区苑期区';

CREATE TABLE `ps_community_building` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `group_id` int(11) NOT NULL DEFAULT '0' COMMENT '苑/期/区id',
  `group_name` varchar(50) NOT NULL DEFAULT '' COMMENT '苑/期/区名称',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '幢名称',
  `building_code` varchar(25) DEFAULT '' COMMENT '楼幢唯一code',
  `code` varchar(3) DEFAULT '0' COMMENT '楼幢编码，用作房屋呼叫',
  `unit_num` tinyint(2) NOT NULL DEFAULT '0' COMMENT '单元数量',
  `floor_num` tinyint(4) NOT NULL DEFAULT '0' COMMENT '楼层数',
  `nature` tinyint(1) NOT NULL DEFAULT '0' COMMENT '性质：1商用，2住宅，3商住两用',
  `orientation` varchar(20) NOT NULL DEFAULT '' COMMENT '楼宇朝向',
  `locations` varchar(40) NOT NULL DEFAULT '' COMMENT '经纬度地址',
  `longitude` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '经度',
  `latitude` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '纬度',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='小区幢列表';

CREATE TABLE `ps_community_units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `group_id` int(11) NOT NULL DEFAULT '0' COMMENT '苑/期/区id',
  `building_id` int(11) NOT NULL DEFAULT '0' COMMENT '幢id',
  `group_name` varchar(50) NOT NULL DEFAULT '' COMMENT '苑/期/区名称',
  `building_name` varchar(50) NOT NULL DEFAULT '' COMMENT '幢名称',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '单元名称',
  `unit_no` varchar(20) NOT NULL DEFAULT '' COMMENT '单元编号',
  `code` varchar(2) DEFAULT '0' COMMENT '单元编码，用作房屋呼叫',
  `unit_code` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='小区单元表';

CREATE TABLE `ps_community_roominfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `out_room_id` varchar(128) DEFAULT '',
  `room_id` varchar(128) NOT NULL DEFAULT '支付宝系统房间唯一标示',
  `community_id` int(11) NOT NULL,
  `group` varchar(32) NOT NULL DEFAULT '' COMMENT '房屋所在的组团名称',
  `building` varchar(32) NOT NULL DEFAULT '' COMMENT '房屋所在楼栋名称',
  `unit` varchar(32) NOT NULL DEFAULT '' COMMENT '房屋所在单元名称',
  `room` varchar(64) NOT NULL DEFAULT '' COMMENT '房屋所在房号',
  `address` varchar(128) NOT NULL DEFAULT '' COMMENT '房间完整门牌地址',
  `unit_id` int(11) NOT NULL DEFAULT '0' COMMENT '单元id',
  `floor_coe` decimal(5,2) DEFAULT '0.00' COMMENT '楼段系数',
  `floor_shared_id` int(11) DEFAULT '0' COMMENT '楼道号ID(ps_shared)',
  `lift_shared_id` int(11) DEFAULT '0' COMMENT '电梯编号ID(ps_shared)',
  `is_elevator` tinyint(1) DEFAULT '0' COMMENT '是否需要电梯\n:1需要2不需要',
  `charge_area` decimal(16,2) NOT NULL DEFAULT '0.00' COMMENT '收费面积',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '房屋状态 1已售 2未售',
  `property_type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '物业类型,1居住物业，2商业物业，3工业物业',
  `intro` varchar(600) DEFAULT '' COMMENT '备注',
  `floor` int(2) DEFAULT '0' COMMENT '楼层',
  `roominfo_code` varchar(35) DEFAULT '' COMMENT '房屋唯一code',
  `room_code` varchar(4) NOT NULL DEFAULT '0' COMMENT '室号编码，用作房屋呼叫',
  `house_type` varchar(20) NOT NULL DEFAULT '' COMMENT '房屋户型 如 1|1|1|1 代表1室1厅1厨1卫',
  `orientation` varchar(20) DEFAULT '' COMMENT '房屋朝向',
  `delivery_time` int(11) DEFAULT '0' COMMENT '交房时间',
  `own_age_limit` tinyint(4) DEFAULT '0' COMMENT '产权年限',
  `sync_rent_manage` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0未同步，1已同步',
  `room_image` varchar(500) DEFAULT '' COMMENT '房屋图片',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `group` (`group`) USING BTREE,
  KEY `building` (`building`) USING BTREE,
  KEY `unit` (`unit`) USING BTREE,
  KEY `room` (`room`) USING BTREE,
  KEY `c_index` (`community_id`) USING BTREE,
  KEY `sync_rent_manage` (`sync_rent_manage`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='小区房屋表';

CREATE TABLE `ps_room_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `room_id` int(11) NOT NULL COMMENT '房屋号id',
  `member_id` int(11) NOT NULL COMMENT '会员ID',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '住户姓名',
  `sex` tinyint(1) NOT NULL DEFAULT '1' COMMENT '住户性别 1男 2女',
  `mobile` varchar(12) NOT NULL DEFAULT '' COMMENT '住户手机号',
  `card_no` varchar(20) DEFAULT '' COMMENT '身份证号',
  `group` varchar(64) NOT NULL DEFAULT '' COMMENT '苑/期/区',
  `building` varchar(64) NOT NULL DEFAULT '' COMMENT '幢',
  `unit` varchar(64) NOT NULL DEFAULT '' COMMENT '单元',
  `room` varchar(64) NOT NULL DEFAULT '' COMMENT '室',
  `identity_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1业主 2家人 3租客',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态，1迁入未认证，2迁入已认证，3未认证迁出，4已认证迁出',
  `out_time` int(11) DEFAULT '0' COMMENT '迁出时间',
  `auth_time` int(11) NOT NULL DEFAULT '0',
  `time_end` int(11) NOT NULL DEFAULT '0' COMMENT '有效期，0表示长期有效',
  `operator_id` int(11) DEFAULT '0' COMMENT '用户创建人',
  `operator_name` varchar(20) DEFAULT '' COMMENT '操作人名称',
  `enter_time` int(11) DEFAULT '0' COMMENT '入驻时间',
  `reason` varchar(450) DEFAULT NULL COMMENT '入驻原因',
  `work_address` varchar(255) DEFAULT NULL COMMENT '工作单位',
  `qq` varchar(15) DEFAULT NULL COMMENT 'qq号',
  `wechat` varchar(50) DEFAULT NULL COMMENT '微信号',
  `email` varchar(50) DEFAULT NULL COMMENT '邮箱',
  `telephone` varchar(15) DEFAULT NULL COMMENT '家庭电话',
  `emergency_contact` varchar(20) DEFAULT NULL COMMENT '紧急联系人',
  `emergency_mobile` varchar(15) DEFAULT NULL COMMENT '紧急联系人电话',
  `nation` tinyint(3) DEFAULT '0' COMMENT '民族',
  `face` tinyint(3) DEFAULT '0' COMMENT '1:党员 2:团员 3:群众',
  `household_type` tinyint(3) DEFAULT '0' COMMENT '1:非农业户口  2:农业户口',
  `marry_status` tinyint(3) DEFAULT '0' COMMENT '1:已婚 2:未婚 3:离异 4:分居 5:丧偶',
  `household_province` int(10) DEFAULT NULL COMMENT '户口省地址',
  `household_city` int(10) DEFAULT NULL COMMENT '户口市',
  `household_area` int(10) DEFAULT NULL COMMENT '区',
  `household_address` varchar(255) DEFAULT NULL COMMENT '详细地址',
  `residence_number` varchar(100) DEFAULT NULL COMMENT '暂住证号码',
  `live_type` tinyint(3) DEFAULT '0' COMMENT '1:户在人在 2:户在人不在 3:常住（已购房，户籍不在）4:承租 5:空房 6:借住 7:其他 8:人在户不在',
  `live_detail` tinyint(3) DEFAULT NULL COMMENT '1:空巢老人 2:独居 3:孤寡 4:其他',
  `change_detail` tinyint(3) DEFAULT NULL COMMENT '1:迁入 2:迁出 3:死亡 4:失联 5:购房入驻 6:出生 7:其他',
  `change_before` varchar(255) DEFAULT NULL COMMENT '变动前地址',
  `change_after` varchar(255) DEFAULT NULL COMMENT '变动后地址',
  `face_url` varchar(255) DEFAULT '' COMMENT '人脸照片',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '用户创建时间',
  `update_at` int(11) DEFAULT '0' COMMENT '最后编辑时间',
  PRIMARY KEY (`id`),
  KEY `cnm` (`community_id`,`room_id`,`mobile`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='住户表';

CREATE TABLE `ps_resident_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL COMMENT '小区ID',
  `member_id` int(11) NOT NULL COMMENT '用户ID',
  `room_id` int(11) NOT NULL COMMENT '房屋ID',
  `audit_id` int(11) NOT NULL DEFAULT '0' COMMENT '审核表ID',
  `room_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '住户表ID',
  `status` tinyint(1) NOT NULL COMMENT '迁入状态， 1迁入，2待审核，3未通过，4已迁出',
  `operator_id` int(11) NOT NULL COMMENT '操作人ID',
  `operator_name` int(11) NOT NULL COMMENT '操作人姓名',
  `create_at` int(11) NOT NULL COMMENT '变更时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='住户状态变更历史表';

CREATE TABLE `ps_resident_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL COMMENT '小区ID',
  `member_id` int(11) NOT NULL COMMENT '用户ID',
  `room_id` int(11) NOT NULL COMMENT '房屋ID',
  `name` varchar(50) NOT NULL COMMENT '业主姓名',
  `mobile` varchar(12) NOT NULL COMMENT '业主手机号',
  `card_no` varchar(20) NOT NULL DEFAULT '' COMMENT '身份证号',
  `sex` tinyint(2) DEFAULT '1' COMMENT '1:男，2女',
  `images` text COMMENT '审核图片',
  `identity_type` tinyint(1) NOT NULL COMMENT '业主身份 1业主，2家人，3租客',
  `time_end` int(11) NOT NULL DEFAULT '0' COMMENT '有效期，0表示长期',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '审核状态，0未审核，1通过，2未通过',
  `reason` varchar(200) NOT NULL DEFAULT '' COMMENT '未通过原因',
  `operator` int(11) NOT NULL DEFAULT '0' COMMENT '处理人ID',
  `operator_name` varchar(50) NOT NULL DEFAULT '' COMMENT '处理人姓名',
  `create_at` int(11) NOT NULL COMMENT '提交时间',
  `update_at` int(11) NOT NULL COMMENT '更新时间',
  `accept_at` int(11) NOT NULL DEFAULT '0' COMMENT '审核通过时间',
  `unaccept_at` int(11) DEFAULT '0' COMMENT '审核不通过时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='房屋认证审核表';

CREATE TABLE `ps_nation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8;

CREATE TABLE `ps_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL COMMENT '业主姓名',
  `sex` tinyint(1) NOT NULL DEFAULT '1' COMMENT '住户性别 1男 2女',
  `mobile` char(11) NOT NULL COMMENT '业主手机号',
  `is_real` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否真实',
  `card_no` varchar(20) DEFAULT NULL COMMENT '身份证号',
  `member_card` varchar(20) NOT NULL DEFAULT '' COMMENT '业主卡号',
  `wallet` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '钱包金额',
  `face_url` varchar(255) NOT NULL DEFAULT '' COMMENT '用户头像',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile_index` (`mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='住户表';

CREATE TABLE `ps_labels_rela` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `labels_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ps_labels表id',
  `data_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ps_community_roominfo/ps_room_user/parking_cars表id',
  `data_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1房屋ps_community_roominfo 2人员ps_room_user 3车辆parking_cars',
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='房屋/住户/车辆对应标签关系表';

CREATE TABLE `ps_labels` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `community_id` int(10) NOT NULL DEFAULT '0' COMMENT '小区id',
  `name` varchar(50) NOT NULL COMMENT '标签名称',
  `label_attribute` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1房屋 2人员 3车辆',
  `label_type` tinyint(3) NOT NULL DEFAULT '1' COMMENT '1日常画像 2重点关注 3关怀对象',
  `content` varchar(255) DEFAULT '' COMMENT '标签描述',
  `is_sys` tinyint(1) DEFAULT '1' COMMENT '1自定义标签 2系统内置标签',
  `is_delete` tinyint(1) DEFAULT '1' COMMENT '1未删除 2已删除',
  `created_at` int(11) DEFAULT '0' COMMENT '新增时间',
  `updated_at` int(11) DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='标签表';

CREATE TABLE `ps_device_repair` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `community_id` int(10) NOT NULL COMMENT '小区Id',
  `category_id` int(11) NOT NULL COMMENT '设备分类ID',
  `device_id` int(11) NOT NULL COMMENT '设备ID',
  `device_name` varchar(15) NOT NULL COMMENT '设备名称',
  `device_no` varchar(15) NOT NULL COMMENT '设备编号',
  `start_at` int(10) NOT NULL COMMENT '保养开始时间',
  `end_at` int(10) NOT NULL COMMENT '保养结束时间',
  `repair_person` varchar(15) NOT NULL COMMENT '设备保养人',
  `content` varchar(200) DEFAULT NULL COMMENT '保养要求与目的',
  `status` tinyint(1) NOT NULL COMMENT '保养状态 1合格 2不合格',
  `check_note` varchar(200) DEFAULT NULL COMMENT '保养检查结果',
  `check_person` varchar(15) NOT NULL COMMENT '报废人',
  `check_at` int(10) NOT NULL COMMENT '检查日期',
  `file_url` varchar(500) DEFAULT NULL COMMENT '文件地址',
  `file_name` varchar(100) DEFAULT NULL COMMENT '文件名称',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='设备保养记录表';

CREATE TABLE `ps_device_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(10) NOT NULL COMMENT '小区Id',
  `name` varchar(15) NOT NULL COMMENT '类别名称',
  `parent_id` int(10) NOT NULL DEFAULT '0' COMMENT '父级ID',
  `note` varchar(100) DEFAULT NULL COMMENT '类别说明',
  `level` tinyint(1) NOT NULL DEFAULT '1' COMMENT '等级',
  `type` tinyint(1) DEFAULT '0' COMMENT '1不删除 0可以删除',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='设备分类表';

CREATE TABLE `ps_device_accident` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `community_id` int(10) NOT NULL COMMENT '小区Id',
  `category_id` int(11) NOT NULL COMMENT '设备分类ID',
  `device_id` int(11) NOT NULL COMMENT '设备ID',
  `happen_at` int(10) NOT NULL COMMENT '事故发生时间',
  `scene_at` int(10) NOT NULL COMMENT '出现场时间',
  `scene_person` varchar(15) DEFAULT NULL COMMENT '出现场人员',
  `confirm_person` varchar(15) DEFAULT NULL COMMENT '确认人',
  `describe` varchar(200) DEFAULT NULL COMMENT '事故事件描述及损失范围',
  `opinion` varchar(200) DEFAULT NULL COMMENT '事故原因及处理意见',
  `result` varchar(200) DEFAULT NULL COMMENT '处理结果',
  `file_url` varchar(200) DEFAULT NULL COMMENT '附件',
  `file_name` varchar(100) DEFAULT NULL COMMENT '文件名称',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='重大事故记录表';

CREATE TABLE `ps_device` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `community_id` int(10) NOT NULL COMMENT '小区Id',
  `category_id` int(11) NOT NULL COMMENT '设备分类ID',
  `name` varchar(15) NOT NULL COMMENT '设备名称',
  `device_no` varchar(15) NOT NULL COMMENT '设备编号',
  `technology` varchar(15) DEFAULT NULL COMMENT '技术规格',
  `num` int(10) DEFAULT '0' COMMENT '数量',
  `price` int(10) DEFAULT '0' COMMENT '单价',
  `supplier` varchar(15) NOT NULL COMMENT '供应商',
  `supplier_tel` varchar(15) NOT NULL COMMENT '供应商联系电话',
  `install_place` varchar(15) NOT NULL COMMENT '安装地点',
  `leader` varchar(15) NOT NULL COMMENT '设备负责人',
  `inspect_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '巡检状态：1正常；2异常',
  `status` tinyint(1) NOT NULL COMMENT '设备状态 1运行 2报废',
  `plan_scrap_at` date NOT NULL COMMENT '拟报废日期',
  `start_at` date DEFAULT NULL COMMENT '出厂日期',
  `expired_at` date DEFAULT NULL COMMENT '保修截止日期',
  `age_limit` varchar(15) DEFAULT NULL COMMENT '寿命年限',
  `repair_company` varchar(15) DEFAULT NULL COMMENT '保修单位',
  `make_company` varchar(15) DEFAULT NULL COMMENT '制造单位',
  `make_company_tel` varchar(15) DEFAULT NULL COMMENT '制造单位电话',
  `install_company` varchar(15) DEFAULT NULL COMMENT '安装单位',
  `install_company_tel` varchar(15) DEFAULT NULL COMMENT '安装单位电话',
  `note` varchar(200) DEFAULT NULL COMMENT '备注',
  `file_url` varchar(500) DEFAULT NULL COMMENT '文件地址',
  `file_name` varchar(100) DEFAULT NULL COMMENT '文件名称',
  `scrap_person` varchar(15) DEFAULT NULL COMMENT '报废人',
  `scrap_note` varchar(200) DEFAULT NULL COMMENT '报废说明',
  `scrap_at` int(11) DEFAULT '0' COMMENT '报废日期',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='设备表';

CREATE TABLE `ps_area_ali` (
  `areaCode` varchar(64) NOT NULL COMMENT '地区编码',
  `areaName` varchar(64) NOT NULL COMMENT '地区名称',
  `areaParentId` varchar(64) DEFAULT '' COMMENT '父级编码',
  `areaType` tinyint(2) NOT NULL DEFAULT '0' COMMENT '区域类型 1国家2省3市4区5街道',
  KEY `areaType` (`areaType`),
  KEY `areaParentId` (`areaParentId`),
  KEY `areaCode` (`areaCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

CREATE TABLE `parking_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `user_name` varchar(20) NOT NULL DEFAULT '' COMMENT '车主姓名',
  `user_mobile` varchar(15) NOT NULL DEFAULT '' COMMENT '车主手机号',
  `created_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `user_name` (`user_name`) USING BTREE,
  KEY `user_mobile` (`user_mobile`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='车主表';

CREATE TABLE `parking_user_carport` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '车主id',
  `car_id` int(11) NOT NULL DEFAULT '0' COMMENT '车辆id',
  `carport_id` int(11) NOT NULL DEFAULT '0' COMMENT '车位id',
  `carport_pay_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '车位拥有类型 1买断 2租赁',
  `carport_rent_start` int(11) NOT NULL DEFAULT '0' COMMENT '车位租赁开始时间',
  `carport_rent_end` int(11) NOT NULL DEFAULT '0' COMMENT '车位租赁截止时间',
  `carport_rent_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '车位租赁价格，总价',
  `room_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1是住户 2非住户',
  `member_id` int(11) NOT NULL DEFAULT '0' COMMENT '业主id',
  `room_id` int(11) NOT NULL DEFAULT '0' COMMENT '房屋id',
  `room_address` varchar(80) NOT NULL DEFAULT '' COMMENT '房屋详情',
  `caruser_name` varchar(20) NOT NULL DEFAULT '' COMMENT '车主姓名（冗余）',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否有效 1有效 2过期',
  `park_card_no` varchar(20) NOT NULL DEFAULT '' COMMENT '停车卡号',
  `created_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='车辆车位车主关系表';


CREATE TABLE `parking_lot` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL DEFAULT '0',
  `community_id` int(11) NOT NULL,
  `name` varchar(40) NOT NULL DEFAULT '' COMMENT '停车场名称/停车场区域名称',
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0停车场，1停车场区域',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1正常 2被删除',
  `park_code` varchar(30) NOT NULL DEFAULT '' COMMENT '唯一编码',
  `parkId` varchar(30) DEFAULT '' COMMENT 'IOT对接设备的车场id',
  `iot_park_name` varchar(100) NOT NULL DEFAULT '' COMMENT 'iot 对应车场名',
  `third_code` varchar(10) NOT NULL DEFAULT '' COMMENT '第三方车场code',
  `alipay_park_id` varchar(50) NOT NULL DEFAULT '' COMMENT '支付宝停车场id',
  `overtime` int(11) NOT NULL DEFAULT '0' COMMENT '付费后多久离场，单位分钟',
  `total_num` int(11) NOT NULL DEFAULT '0' COMMENT '车位总数',
  `lon` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '经度值',
  `lat` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '纬度值',
  `location` varchar(100) NOT NULL DEFAULT '' COMMENT '位置',
  `created_at` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='停车场表';

CREATE TABLE `parking_cars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `car_num` varchar(20) NOT NULL DEFAULT '' COMMENT '车牌号',
  `car_model` varchar(20) NOT NULL DEFAULT '' COMMENT '车辆型号',
  `car_color` varchar(10) NOT NULL DEFAULT '' COMMENT '车辆颜色',
  `car_delivery` decimal(2,1) NOT NULL DEFAULT '0.0' COMMENT '车辆排量',
  `images` varchar(500) NOT NULL DEFAULT '' COMMENT '车辆图片',
  `created_at` int(11) NOT NULL DEFAULT '0' COMMENT '车辆添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='车辆表';

CREATE TABLE `parking_carport` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商id',
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `lot_id` int(11) NOT NULL DEFAULT '0' COMMENT '停车场Id',
  `lot_area_id` int(11) NOT NULL DEFAULT '0' COMMENT '停车场区域id',
  `car_port_num` varchar(255) NOT NULL DEFAULT '' COMMENT '车位号',
  `car_port_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '车位类型 1人防车位 2公共车位 3产权车位',
  `car_port_area` double NOT NULL DEFAULT '0' COMMENT '车位面积',
  `car_port_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0空闲，1已售，2已租',
  `room_id` int(11) NOT NULL DEFAULT '0' COMMENT '房屋id',
  `room_address` varchar(80) NOT NULL DEFAULT '' COMMENT '房屋信息',
  `room_name` varchar(20) NOT NULL DEFAULT '' COMMENT '产权人',
  `room_id_card` varchar(20) NOT NULL DEFAULT '' COMMENT '产权人身份证号',
  `room_mobile` varchar(15) NOT NULL DEFAULT '' COMMENT '联系电话',
  `created_at` int(11) DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `iot_suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '供应商名称',
  `contactor` varchar(20) NOT NULL DEFAULT '' COMMENT '联系人',
  `mobile` varchar(15) NOT NULL DEFAULT '' COMMENT '联系电话',
  `supplier_name` varchar(20) NOT NULL DEFAULT '' COMMENT '供应商标识',
  `productSn` varchar(40) DEFAULT '' COMMENT '产品SN',
  `functionFace` tinyint(1) DEFAULT '0' COMMENT '是否支持人脸开门功能，1支持，0不支持',
  `functionBlueTooth` tinyint(1) DEFAULT '0' COMMENT '是否支持蓝牙开门功能，1支持，0不支持',
  `functionCode` tinyint(1) DEFAULT '0' COMMENT '是否支持二维码开门功能，1支持，0不支持',
  `functionPassword` tinyint(1) DEFAULT '0' COMMENT '是否支持密码开门功能，1支持，0不支持',
  `functionCard` tinyint(1) DEFAULT '0' COMMENT '是否支持门开开门功能，1支持，0不支持',
  `created_at` int(11) DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='硬件供应商表';

CREATE TABLE `iot_supplier_community` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL DEFAULT '0' COMMENT '供应商id',
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区id',
  `auth_code` varchar(255) DEFAULT '' COMMENT '供应商对于此小区的授权码，每个小区不一样',
  `auth_at` int(11) DEFAULT '0' COMMENT '授权时间',
  `open_alipay_parking` tinyint(2) DEFAULT '0' COMMENT '此供应商在此小区是否开通支付宝停车缴费',
  `interface_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '接入方式  0未接入 1主动接第三方 2第三方接我们',
  `supplier_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '供应商类型 1道闸 2门禁',
  `created_at` int(11) DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='硬件供应商与小区的关联关系表';