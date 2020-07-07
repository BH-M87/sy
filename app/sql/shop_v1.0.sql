CREATE TABLE `ps_shop_merchant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '商家名称',
  `merchant_code` varchar(30) NOT NULL DEFAULT '' COMMENT '商家code',
  `check_code` varchar(30) NOT NULL DEFAULT '' COMMENT '审核code',
  `type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '商户类型 1小微商家 2个体工商户',
  `category_code` varchar(64) NOT NULL DEFAULT '' COMMENT '经营类目',
  `merchant_img` varchar(500) NOT NULL DEFAULT '' COMMENT '商家照片',
  `business_img` varchar(500) NOT NULL DEFAULT '' COMMENT '营业执照',
  `lon` decimal(10,6) DEFAULT '0.000000' COMMENT '经度',
  `lat` decimal(10,6) DEFAULT '0.000000' COMMENT '纬度',
  `location` varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  `start` varchar(10) NOT NULL DEFAULT '' COMMENT '营业开始时间',
  `end` varchar(10) NOT NULL DEFAULT '' COMMENT '营业结束时间',
  `link_name` varchar(10) NOT NULL DEFAULT '' COMMENT '联系人',
  `link_mobile` varchar(10) NOT NULL DEFAULT '' COMMENT '联系人手机号',
  `scale` varchar(100) NOT NULL DEFAULT '' COMMENT '规模',
  `area` varchar(100) NOT NULL DEFAULT '' COMMENT '面积',
  `check_status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '审核状态 1待审核,2审核通过,3审核未通过',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '商家状态 1正常,2锁定',
  `check_content` varchar(255) NOT NULL DEFAULT '' COMMENT '审核备注',
  `member_id` varchar(30) NOT NULL DEFAULT '' COMMENT '会员id (java平台)',
  `check_id`  varchar(30) NOT NULL DEFAULT '' COMMENT '审核人id',
  `check_name`  varchar(10) NOT NULL DEFAULT '' COMMENT '审核人名称',
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
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '19', '其他','',1 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '1900', '汽车养护','19',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '1901', '丽人健身','19',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '1902', '婚庆摄影','19',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '1903', '充值缴费','19',2 );
INSERT INTO `ps_shop_category` ( `code`, `name`, `parentCode`, `type` ) VALUES ( '1904', '图书影像','19',2 );

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
  `updateAt` int(11) DEFAULT '0' COMMENT '更新时间',
  `createAt` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='店铺表';

CREATE TABLE `ps_shop_goods_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `shop_id` int(11) NOT NULL COMMENT '店铺ID',
  `type_name` varchar(20) NOT NULL COMMENT '商品分类名称',
  `updateAt` int(11) DEFAULT '0' COMMENT '更新时间',
  `createAt` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品分类表';

CREATE TABLE `ps_shop_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `shop_id` int(11) NOT NULL COMMENT '店铺ID',
  `type_id` int(11) NOT NULL COMMENT '商品分类ID',
  `merchant_code` int(11) NOT NULL COMMENT '商家编号',
  `goods_code` varchar(20) NOT NULL COMMENT '商品编号',
  `goods_name` varchar(20) NOT NULL COMMENT '商品名称',
  `status` tinyint(1) NOT NULL COMMENT '商品状态 1上架 2下架',
  `img` varchar(255) NOT NULL DEFAULT '' COMMENT '商品图片 多图逗号隔开',
  `updateAt` int(11) DEFAULT '0' COMMENT '更新时间',
  `createAt` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品表';
