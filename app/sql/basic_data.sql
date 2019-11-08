ALTER TABLE `door_record`
ADD COLUMN `visitor_id`  int(11) NULL AFTER `community_id`;


CREATE TABLE `st_labels` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `organization_type` int(1) NOT NULL DEFAULT '0' COMMENT '所属组织类型',
  `organization_id` varchar(50) NOT NULL DEFAULT '' COMMENT '所属角色code',
  `name` varchar(50) NOT NULL COMMENT '标签名称',
  `label_attribute` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1房屋 2人员 3车辆',
  `label_type` tinyint(3) NOT NULL DEFAULT '1' COMMENT '1日常画像 2重点关注 3关怀对象',
  `content` varchar(255) DEFAULT '' COMMENT '标签描述',
  `is_sys` tinyint(1) DEFAULT '1' COMMENT '1自定义标签 2系统内置标签',
  `is_delete` tinyint(1) DEFAULT '1' COMMENT '1未删除 2已删除',
  `created_at` int(11) DEFAULT '0' COMMENT '新增时间',
  `updated_at` int(11) DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=183 DEFAULT CHARSET=utf8 COMMENT='标签表';


CREATE TABLE `st_labels_rela` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_type` int(1) NOT NULL DEFAULT '0' COMMENT '所属组织类型',
  `organization_id` varchar(50) NOT NULL DEFAULT '' COMMENT '所属角色code',
  `labels_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ps_labels表id',
  `type` tinyint(11) DEFAULT '1' COMMENT '1迁入-未认证，2迁入-已认证 3迁出-未认证 4迁出-已认证',
  `data_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ps_community_roominfo/ps_member/parking_cars表id',
  `data_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1房屋ps_community_roominfo 2人员ps_member 3车辆parking_cars',
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1793 DEFAULT CHARSET=utf8 COMMENT='房屋/住户/车辆对应标签关系表';


INSERT INTO `st_labels` VALUES ('1',  '0','0', '网格员', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('2',  '0','0', '人大代表', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('3',  '0','0', '政协委员', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('4',  '0','0', '群防群治人员', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('5',  '0','0', '平安志愿者', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('6',  '0','0', '社区（村）干部', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('7',  '0','0', '治保积极分子', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('8',  '0','0', '咪表管理', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('9',  '0','0', '公职人员', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('10', '0','0', '在职党员', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('11', '0','0', '低慢小持有', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('12', '0','0', '烈性犬持有', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('13', '0','0', '信鸽持有', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('14', '0','0', '拆迁安置人员', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('15', '0','0', '三峡移民', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('16', '0','0', '民办教师', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('17', '0','0', '军转人员', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('18', '0','0', '持枪猎民', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('19', '0','0', '枪械爱好者', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('20', '0','0', '网络主播', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('21', '0','0', '律师法官', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('22', '0','0', '教师教授', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('23', '0','0', '医生护士', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('24', '0','0', '快递外卖', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('25', '0','0', '财务人员', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('26', '0','0', '塔吊人员', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('27', '0','0', '出租车司机', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('28', '0','0', '公交司机', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('29', '0','0', '滴滴司机', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('30', '0','0', '环卫保洁', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('31', '0','0', '独居老人', '2', '3', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('32', '0','0', '残疾人员', '2', '3', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('33', '0','0', '低保人员', '2', '3', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('34', '0','0', '军警烈属', '2', '3', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('35', '0','0', '失业人员', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('36', '0','0', '失独人员', '2', '3', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('37', '0','0', '卧病在床', '2', '3', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('38', '0','0', '困难家庭', '2', '3', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('39', '0','0', '外出务工', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('40', '0','0', '外出留学', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('41', '0','0', '精神障碍', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('42', '0','0', '个人极端', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('43', '0','0', '七类前科', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('44', '0','0', '刑满释放', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('45', '0','0', '社区矫正', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('46', '0','0', '境外人员', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('47', '0','0', '吸毒人员', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('48', '0','0', '醉驾前科', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('49', '0','0', '刑嫌对象', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('50', '0','0', '剥夺政治权利人员', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('51', '0','0', '涉恐关注', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('52', '0','0', '国保重点', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('53', '0','0', '经侦重点', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('54', '0','0', '信访重点', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('55', '0','0', '境外重点', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('56', '0','0', '网格重点', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('57', '0','0', '邪教人员', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('58', '0','0', '非法宗教', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('59', '0','0', '公安信访', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('60', '0','0', '在逃人员', '2', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('61', '0','0', '高龄老人', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('62', '0','0', '流动人口', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('63', '0','0', '住户分离', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('64', '0','0', '群众', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('65', '0','0', '60岁以上老人', '2', '3', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('66', '0','0', '访客人口', '2', '1', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('67', '0','0', '出租房', '1', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('68', '0','0', '营业房', '1', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('69', '0','0', '自住房', '1', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('70', '0','0', '网约房', '1', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('71', '0','0', '实有车辆', '3', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');
INSERT INTO `st_labels` VALUES ('72', '0','0', '外部车辆', '3', '2', '系统内置标签', '2', '1', '1563244797', '1563244797');