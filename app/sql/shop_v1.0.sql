CREATE TABLE `ps_shop_merchant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '商家名称',
  `merchant_code` varchar(30) NOT NULL DEFAULT '' COMMENT '商家code',
  `check_code` varchar(30) NOT NULL DEFAULT '' COMMENT '审核code',
  `type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '商户类型 1小微商家 2个体工商户',
  `category_first` varchar(64) NOT NULL DEFAULT '' COMMENT '经营类目一级',
  `category_second` varchar(64) NOT NULL DEFAULT '' COMMENT '经营类目二级',
  `merchant_img` varchar(500) NOT NULL DEFAULT '' COMMENT '商家照片',
  `business_img` varchar(500) NOT NULL DEFAULT '' COMMENT '营业执照',
  `lon` decimal(10,6) DEFAULT '0.000000' COMMENT '经度',
  `lat` decimal(10,6) DEFAULT '0.000000' COMMENT '纬度',
  `location` varchar(255) NOT NULL DEFAULT '' COMMENT '位置名称',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  `start` varchar(10) NOT NULL DEFAULT '' COMMENT '营业开始时间',
  `end` varchar(10) NOT NULL DEFAULT '' COMMENT '营业结束时间',
  `link_name` varchar(10) NOT NULL DEFAULT '' COMMENT '联系人',
  `link_mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '联系人手机号',
  `scale` tinyint(4) NOT NULL DEFAULT '0' COMMENT '规模  1 0~5,2 5~10,3 10~20,4 20~50,5 50以上',
  `area` tinyint(4) NOT NULL DEFAULT '0' COMMENT '面积 1 10㎡以内,2 10~50㎡,3 50~100㎡,4 100㎡以上',
  `check_status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '审核状态 1待审核,2审核通过,3审核未通过',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '商家状态 1正常,2锁定',
  `check_content` varchar(255) NOT NULL DEFAULT '' COMMENT '审核备注',
  `member_id` varchar(30) NOT NULL DEFAULT '' COMMENT '会员id (java平台)',
  `check_id`  varchar(30) NOT NULL DEFAULT '' COMMENT '审核人id',
  `check_name`  varchar(10) NOT NULL DEFAULT '' COMMENT '审核人名称',
  `ali_form_id` varchar(100) NOT NULL DEFAULT '' COMMENT '支付宝表单id',
  `ali_user_id` varchar(100) NOT NULL DEFAULT '' COMMENT '支付宝用户id',
  `check_at` int(10) NOT NULL DEFAULT '0' COMMENT '审核时间',
  `create_at` int(10) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_at` int(10) NOT NULL DEFAULT 0 COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商户表';


CREATE TABLE `ps_shop_merchant_community` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_code` varchar(30) NOT NULL DEFAULT '' COMMENT '商家code',
  `community_id` varchar(30) NOT NULL DEFAULT '' COMMENT '小区id',
  `community_name` varchar(30) NOT NULL DEFAULT '' COMMENT '小区名称',
  `society_id` varchar(30) NOT NULL DEFAULT '' COMMENT '社区id',
  `society_name` varchar(30) NOT NULL DEFAULT '' COMMENT '社区名称',
  `create_at` int(10) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_at` int(10) NOT NULL DEFAULT 0 COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商户关联社区小区表';


CREATE TABLE `ps_shop_category` (
  `code` varchar(64) NOT NULL COMMENT '编码',
  `name` varchar(64) NOT NULL COMMENT '名称',
  `parentCode` varchar(64) DEFAULT '' COMMENT '父级编码',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '类型 1一级类目2二级类目',
  KEY `type` (`type`),
  KEY `parentCode` (`parentCode`),
  KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='经营类目';

INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '10', '食品','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '1010', '生鲜果蔬','10',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '1011', '休闲零食','10',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '1012', '蛋糕烘培','10',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '1013', '茶饮酒水','10',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '1014', '滋补保健','10',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '1015', '粮油米面','10',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '11', '数码家电','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '12', '女装','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '13', '男装','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '14', '美妆','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '15', '日用百货','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '16', '休闲娱乐','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '17', '亲子','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '18', '教育培训','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '19', '餐饮外卖','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '20', '箱包配饰','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '21', '家居家纺','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '22', '媒体服务','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '23', '海外购','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '24', '运动户外','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '25', '礼品鲜花','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '26', '医疗健康','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '27', '酒店旅游','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '28', '票务卡券','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '29', '其他','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '2900', '汽车养护','29',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '2901', '丽人健身','29',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '2902', '婚庆摄影','29',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '2903', '充值缴费','29',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '2904', '图书影像','29',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '2905', '家政服务','29',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '2906', '民俗文化','29',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '2907', '鞋靴','29',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '2908', '宠物','29',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '2909', '其他','29',2 );

CREATE TABLE `ps_shop` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `merchant_code` int(11) NOT NULL COMMENT '商家编号',
  `shop_code` varchar(20) NOT NULL COMMENT '店铺编号',
  `shop_name` varchar(20) NOT NULL COMMENT '店铺名称',
  `address` varchar(100) NOT NULL COMMENT '详细地址',
  `lon` varchar(100) NOT NULL COMMENT '经度',
  `lat` varchar(100) NOT NULL COMMENT '纬度',
  `link_name` varchar(20) NOT NULL COMMENT '联系人姓名',
  `link_mobile` varchar(20) NOT NULL COMMENT '手机号',
  `start` varchar(10) NOT NULL DEFAULT '' COMMENT  '营业开始时间',
  `end` varchar(10) NOT NULL DEFAULT '' COMMENT  '营业结束时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '店铺状态 1营业中 2打烊',
  `img` varchar(255) NOT NULL DEFAULT '' COMMENT '营业执照',
  `app_id` varchar(50) NULL DEFAULT '' COMMENT '小程序appID',
  `app_name` varchar(50) NULL DEFAULT '' COMMENT '小程序app名称',
  `update_at` int(11) DEFAULT '0' COMMENT '更新时间',
  `create_at` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='店铺表';

CREATE TABLE `ps_shop_community` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL COMMENT '店铺ID',
  `distance` varchar(30) NOT NULL DEFAULT '' COMMENT '店铺小区距离',
  `community_id` varchar(30) NOT NULL DEFAULT '' COMMENT '小区id',
  `community_name` varchar(30) NOT NULL DEFAULT '' COMMENT '小区名称',
  `society_id` varchar(30) NOT NULL DEFAULT '' COMMENT '社区id',
  `society_name` varchar(30) NOT NULL DEFAULT '' COMMENT '社区名称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='店铺关联社区小区表';

CREATE TABLE `ps_shop_goods_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `shop_id` int(11) NOT NULL COMMENT '店铺ID',
  `type_name` varchar(20) NOT NULL COMMENT '商品分类名称',
  `update_at` int(11) DEFAULT '0' COMMENT '更新时间',
  `create_at` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品分类表';

CREATE TABLE `ps_shop_goods_type_rela` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `goods_id` int(11) NOT NULL COMMENT '商品ID',
  `type_id` varchar(20) NOT NULL COMMENT '商品分类ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品分类关系表';

CREATE TABLE `ps_shop_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `shop_id` int(11) NOT NULL COMMENT '店铺ID',
  `merchant_code` int(11) NOT NULL COMMENT '商家编号',
  `goods_code` varchar(20) NOT NULL COMMENT '商品编号',
  `goods_name` varchar(20) NOT NULL COMMENT '商品名称',
  `status` tinyint(1) NOT NULL COMMENT '商品状态 1上架 2下架',
  `img` varchar(255) NOT NULL DEFAULT '' COMMENT '商品图片 多图逗号隔开',
  `update_at` int(11) DEFAULT '0' COMMENT '更新时间',
  `create_at` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品表';


CREATE TABLE `ps_shop_merchant_promote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchant_code` varchar(30) NOT NULL DEFAULT '' COMMENT '商家code',
  `merchant_name` varchar(30) NOT NULL DEFAULT '' COMMENT '商家名称',
  `shop_code` varchar(30) NOT NULL DEFAULT '' COMMENT '店铺code',
  `shop_name` varchar(30) NOT NULL DEFAULT '' COMMENT '店铺名称',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '素材名称',
  `img` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
  `create_at` int(10) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_at` int(10) NOT NULL DEFAULT 0 COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='社区店铺推广';

CREATE TABLE `ps_shop_statistic` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) NOT NULL COMMENT '店铺id',
  `year` int(11) NOT NULL COMMENT '统计时所在年份',
  `month` tinyint(2) NOT NULL COMMENT '所在月份',
  `day` date NOT NULL COMMENT '具体的哪天',
  `click_num` int(10) NOT NULL DEFAULT '0' COMMENT '今天的点击数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='店铺点击统计表';

/***********2020-07-30***********/
ALTER TABLE  `ps_shop_merchant_community` ADD  `lon` varchar(100) NOT NULL COMMENT '经度' AFTER `society_name`;
ALTER TABLE  `ps_shop_merchant_community` ADD  `lat` varchar(100) NOT NULL COMMENT '纬度' AFTER `society_name`;