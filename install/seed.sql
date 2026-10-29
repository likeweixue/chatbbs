-- ChatBBS 初始数据

-- 用户组
INSERT INTO `__PREFIX__user_groups` (`id`,`name`,`slug`,`description`,`color`,`permissions`,`is_system`,`sort_order`) VALUES
(1,'管理员','admin','站点最高管理员，拥有全部权限','#d93026','["*"]',1,1),
(2,'超级版主','super_moderator','管理全站内容与用户','#fa8c16','["thread.view","thread.create","thread.reply","thread.edit","thread.delete","thread.top","thread.essence","thread.close","thread.move","post.delete","attachment.upload","attachment.download","pm.send","tag.create","checkin.do","say.create"]',1,2),
(3,'版主','moderator','管理指定板块内容','#52c41a','["thread.view","thread.create","thread.reply","thread.edit","thread.delete","thread.top","thread.essence","thread.close","post.delete","attachment.upload","attachment.download","pm.send","checkin.do","say.create"]',1,3),
(4,'普通用户','member','注册用户，可发帖回帖','#4D698E','["thread.view","thread.create","thread.reply","thread.edit","attachment.upload","attachment.download","pm.send","tag.create","checkin.do","say.create"]',1,4),
(5,'游客','guest','未登录访客，仅可浏览','#8a919f','["thread.view"]',1,5),
(6,'禁言用户','muted','被禁言，仅可浏览','#8a919f','["thread.view"]',1,6);

-- 等级
INSERT INTO `__PREFIX__user_levels` (`id`,`name`,`icon`,`min_exp`,`need_threads`,`need_posts`,`need_likes`,`need_days`,`need_checkin`,`auto_upgrade`,`sort_order`) VALUES
(1,'V1 新手','',0,0,0,0,0,0,1,1),
(2,'V2 活跃','',100,10,20,10,7,3,1,2),
(3,'V3 进阶','',300,30,60,30,30,10,1,3),
(4,'V4 老手','',800,80,150,80,90,25,1,4),
(5,'V5 专家','',2000,150,300,200,180,50,1,5),
(6,'V6 大师','',5000,300,600,500,360,100,1,6);

-- 认证
INSERT INTO `__PREFIX__user_certifications` (`id`,`name`,`icon`,`color`,`description`,`sort_order`) VALUES
(1,'官方认证','','#4D698E','官方团队成员',1),
(2,'优质作者','','#52c41a','内容优质创作者',2),
(3,'活跃用户','','#fa8c16','社区活跃贡献者',3);

-- 帖子标记
INSERT INTO `__PREFIX__thread_marks` (`id`,`name`,`color`,`icon`,`groups`,`sort_order`) VALUES
(1,'已解决','#52c41a','✓','1,2,3,4',1),
(2,'已完成','#4D698E','✔','1,2,3,4',2),
(3,'已结束','#8a919f','✖','1,2,3',3),
(4,'待解决','#fa8c16','?','1,2,3,4',4);

-- 菜单
INSERT INTO `__PREFIX__menus` (`id`,`title`,`url`,`icon`,`target`,`parent_id`,`visible`,`sort_order`) VALUES
(1,'首页','/','home','_self',0,1,1),
(2,'板块','/forums','category','_self',0,1,2),
(4,'说说','/say','comment','_self',0,1,4),
(5,'标签','/tags','tag','_self',0,1,5),
(6,'签到','/checkin','calendar','_self',0,1,6),
(7,'聊天','/pm','mail','_self',0,1,7),
(8,'通知','/notifications','bell','_self',0,1,8),
(9,'用户中心','/user','user','_self',0,1,9);

-- 默认板块
INSERT INTO `__PREFIX__forums` (`id`,`parent_id`,`name`,`slug`,`description`,`icon`,`color`,`type`,`sort_order`,`visible`) VALUES
(1,0,'综合社区','general','','','#4D698E',1,1,1),
(2,1,'新手报到','hello','新人自我介绍，欢迎每一位新朋友','','#52c41a',2,1,1),
(3,1,'闲聊灌水','chat','随便聊聊，分享生活','','#4D698E',2,2,1),
(4,1,'站点公告','notice','官方公告与版本更新','','#d93026',2,3,1),
(5,0,'资源分享','resource','','','#722ed1',1,2,1),
(6,5,'软件资源','software','实用软件与工具分享','','#722ed1',2,1,1),
(7,5,'学习资料','study','教程、文档、电子书','','#13c2c2',2,2,1);

-- 默认标签
INSERT INTO `__PREFIX__tags` (`id`,`name`,`slug`,`color`,`description`,`status`) VALUES
(1,'讨论','discussion','#4D698E','',1),
(2,'求助','help','#fa8c16','',1),
(3,'分享','share','#52c41a','',1),
(4,'公告','notice','#d93026','',1),
(5,'教程','tutorial','#722ed1','',1);

-- 插件 / 主题占位
INSERT INTO `__PREFIX__themes` (`id`,`name`,`slug`,`version`,`author`,`description`,`enabled`) VALUES
(1,'默认主题','default','1.1.0','ChatBBS','默认主题：4px 圆角，支持经典 / 岛屿 / 单栏布局',1);
