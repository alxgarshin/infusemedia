CREATE TABLE `infuse`
(
    `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
    `ip_address`  varchar(25)  NOT NULL,
    `user_agent`  varchar(500) NOT NULL,
    `view_date`   datetime     NOT NULL,
    `page_url`    varchar(255) NOT NULL,
    `views_count` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `infuse_ip_address_IDX` (`ip_address`,`user_agent`,`page_url`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;