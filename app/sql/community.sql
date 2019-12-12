ALTER TABLE `ps_repair`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`,

ALTER TABLE `ps_repair`
MODIFY COLUMN `room_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '房屋id';

ALTER TABLE `ps_repair_type`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`;

ALTER TABLE `ps_repair_bill`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`;

ALTER TABLE `ps_repair_bill`
add COLUMN `community_no`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '支付宝小区编号' AFTER `community_id`;

ALTER TABLE `ps_repair_materials`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`;

ALTER TABLE `ps_repair_materials_cate`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`;

ALTER TABLE `ps_bill`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`;

ALTER TABLE `ps_bill_alipay_log`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`;

ALTER TABLE `ps_bill_backup`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`;

ALTER TABLE `ps_bill_del`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`;

ALTER TABLE `ps_bill_income`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`;

ALTER TABLE `ps_bill_msg`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`;

ALTER TABLE `ps_bill_period`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`;

ALTER TABLE `ps_bill_report`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`;

ALTER TABLE `ps_bill_task`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`;

ALTER TABLE `ps_community_operate_log`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`;

ALTER TABLE `ps_order`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`;

ALTER TABLE `ps_order_del`
MODIFY COLUMN `community_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '小区id' AFTER `id`;

ALTER TABLE `ps_repair`
ADD COLUMN `repair_time`  int(11) NULL COMMENT '保修时间，选填' AFTER `status`;

ALTER TABLE `ps_repair`
ADD COLUMN `contact_name`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '报事报修人' AFTER `room_id`;

ALTER TABLE `ps_repair_assign`
MODIFY COLUMN `user_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '维修工人id';

ALTER TABLE `ps_repair_record`
MODIFY COLUMN `operator_id`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '处理人id';

ALTER TABLE `ps_repair_record`
ADD COLUMN `mobile`  varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '手机号' AFTER `operator_name`;