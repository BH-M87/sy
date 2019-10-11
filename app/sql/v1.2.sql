ALTER TABLE `ps_community`
MODIFY COLUMN `status`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '1启用 2禁用' AFTER `house_type`,
ADD COLUMN `map_gid`  varchar(100) NOT NULL DEFAULT '' COMMENT '围栏gid' AFTER `status`,
ADD COLUMN `acceptance_time`  int(11) NOT NULL DEFAULT 0 COMMENT '验收时间' AFTER `delivery_time`,
ADD COLUMN `right_start`  int(11) NOT NULL DEFAULT 0 COMMENT '产权开始时间' AFTER `acceptance_time`,
ADD COLUMN `right_end`  int(11) NOT NULL DEFAULT 0 COMMENT '产权结束时间' AFTER `right_start`,
ADD COLUMN `register_time`  int(11) NOT NULL DEFAULT 0 COMMENT '登记时间' AFTER `right_end`;

ALTER TABLE `ps_community`
MODIFY COLUMN `house_type`  tinyint(2) NULL DEFAULT 0 COMMENT '1普通小区、2安置小区、3老旧小区' AFTER `area_sign`;

ALTER TABLE `ps_community`
ADD COLUMN `link_name`  varchar(50) NOT NULL DEFAULT '' COMMENT '联系人名称' AFTER `address`;

