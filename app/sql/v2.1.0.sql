CREATE TABLE `ps_system_set` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `company_id` varchar(30) DEFAULT NULL COMMENT '公司id',
  `payment_set` tinyint(3) NOT NULL DEFAULT '1' COMMENT '无间断缴费 1关闭 2开启',
  `notice_content` varchar(200) DEFAULT '' COMMENT '缴费通知单备注',
  `create_at` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_at` int(10) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统设置';


CREATE TABLE `ps_channel_day_report` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `community_id` varchar(30) NOT NULL COMMENT '小区id',
  `community_name` varchar(50) DEFAULT NULL COMMENT '小区名称',
  `cost_id` int(10) NOT NULL COMMENT '缴费项id',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父级id',
  `parent_time` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL COMMENT '收款方式:1现金,2支付宝,3微信,4刷卡,5对公,6支票,9线上收款',
  `type_name` varchar(50) DEFAULT NULL COMMENT '名称',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '缴费金额',
  `total` int(11) NOT NULL DEFAULT '0' COMMENT '缴费',
  `create_at` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_at` int(11) NOT NULL DEFAULT '0' COMMENT '上次更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;