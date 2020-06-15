CREATE TABLE `ps_delivery_records` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `product_id` int(10) NOT NULL COMMENT '商品id',
  `community_id` varchar(30) DEFAULT '' COMMENT '小区id',
  `room_id` varchar(30) DEFAULT '' COMMENT '房屋id',
  `product_name` varchar(30) DEFAULT '' COMMENT '兑换商品名称',
  `product_img` varchar(255) DEFAULT '' COMMENT '兑换商品图片',
  `cust_name` varchar(30) DEFAULT '' COMMENT '兑换人',
  `cust_mobile` varchar(30) DEFAULT '' COMMENT '兑换人手机',
  `user_id` varchar(30) DEFAULT '' COMMENT '兑换人id（会员id）',
  `volunteer_id` int(10) DEFAULT '0' COMMENT '自愿者id（街道）',
  `product_num` int(2) NOT NULL DEFAULT '1' COMMENT '兑换数量',
  `integral` int(4) NOT NULL DEFAULT '0' COMMENT '消耗积分',
  `address` varchar(200) DEFAULT '' COMMENT '兑换地址',
  `delivery_type` int(2) NOT NULL DEFAULT '0' COMMENT '配送方式 1快递 2自提',
  `status` int(2) NOT NULL DEFAULT '1' COMMENT '状态 1未处理 2已发 3已提',
  `courier_company` varchar(50) DEFAULT '' COMMENT '快递公司',
  `order_num` varchar(50) DEFAULT '' COMMENT '快递单号',
  `records_code` varchar(10) DEFAULT '' COMMENT '自提码',
  `operator_name` varchar(10) DEFAULT '' COMMENT '操作人',
  `operator_id` varchar(30) DEFAULT '' COMMENT '操作人id',
  `create_at` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_at` int(10) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='兑换记录';


ALTER TABLE `ps_delivery_records` ADD COLUMN `verification_qr_code` varchar(255) DEFAULT NULL COMMENT '核销二维码' after `operator_name`;


CREATE TABLE `ps_community_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` varchar(30) NOT NULL DEFAULT '0' COMMENT '小区id',
  `qr_code` varchar(255) NOT NULL DEFAULT '' COMMENT '一区一码二维码',
  `bang_code` varchar(255) NOT NULL DEFAULT '' COMMENT '帮帮码二维码',
  `create_at` int(11) NOT NULL COMMENT '创建时间',
  `update_at` int(10) NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20084 DEFAULT CHARSET=utf8 COMMENT='小区配置表';