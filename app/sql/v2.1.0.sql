CREATE TABLE `ps_system_set` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `company_id` varchar(30) DEFAULT NULL COMMENT '公司id',
  `payment_set` tinyint(3) NOT NULL DEFAULT '1' COMMENT '无间断缴费 1关闭 2开启',
  `notice_content` varchar(200) DEFAULT '' COMMENT '缴费通知单备注',
  `create_at` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_at` int(10) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统设置';