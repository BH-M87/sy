CREATE TABLE `answer` (
`id`  int(11) NOT NULL AUTO_INCREMENT ,
`member_id`  int(11) NOT NULL DEFAULT 0 COMMENT '用户id' ,
`grade`  varchar(255) NOT NULL DEFAULT 0 COMMENT '分数' ,
`created_at`  int(11) NOT NULL DEFAULT 0 ,
PRIMARY KEY (`id`)
);

ALTER TABLE `answer`
ADD COLUMN `app_user_id`  int(11) NOT NULL COMMENT 'app_user表id' AFTER `id`;

