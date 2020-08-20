ALTER TABLE `ps_repair_record`
ADD COLUMN `hard_type` tinyint(4) DEFAULT '1' COMMENT '1 一般问题，2 疑难问题' AFTER `status`;

