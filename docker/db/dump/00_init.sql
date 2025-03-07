USE php_test;
SET collation_connection = utf8mb4_unicode_ci;
SET NAMES utf8;
ALTER DATABASE php_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;



CREATE TABLE `companies` (
    `id`   int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID компании',
    `name` varchar(255)     NOT NULL                COMMENT 'Название компании',

    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = utf8mb4
    COMMENT = 'Компании';



CREATE TABLE `agencies` (
    `id`   int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID агентства',
    `name` varchar(255)     NOT NULL                COMMENT 'Название агентства',

    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = utf8mb4
    COMMENT = 'Агентства';



CREATE TABLE `countries` (
    `id`   int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID страны',
    `name` varchar(255)     NOT NULL                COMMENT 'Название страны',

    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = utf8mb4
    COMMENT = 'Страны';



CREATE TABLE `cities` (
    `id`         int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID города',
    `name`       varchar(255)     NOT NULL                COMMENT 'Название города',
    `country_id` int(10) unsigned NOT NULL                COMMENT 'ID страны',

    PRIMARY KEY (`id`),
    FOREIGN KEY (`country_id`) REFERENCES countries(`id`)
)
    ENGINE = InnoDB
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = utf8mb4
    COMMENT = 'Города';



CREATE TABLE `hotels` (
    `id`      int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID отеля',
    `name`    varchar(255)     NOT NULL                COMMENT 'Название отеля',
    `stars`   int(1) unsigned  NOT NULL                COMMENT 'Звездность',
    `city_id` int(10) unsigned NOT NULL                COMMENT 'ID города',

    PRIMARY KEY (`id`),
    FOREIGN KEY (`city_id`) REFERENCES cities(`id`)
)
    ENGINE = InnoDB
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = utf8mb4
    COMMENT = 'Отели';



CREATE TABLE `agency_hotel_options` (
    `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
    `hotel_id`    int(10) unsigned NOT NULL                 COMMENT 'ID отеля',
    `agency_id`   int(10) unsigned NOT NULL                 COMMENT 'ID агентства',
    `percent`     int(10)          NOT NULL DEFAULT 0       COMMENT 'Процент',
    `is_black`    tinyint(1)       NOT NULL DEFAULT 0       COMMENT 'Отель в черном списке',
    `is_recomend` tinyint(1)       NOT NULL DEFAULT 0       COMMENT 'Рекомендованный отель',
    `is_white`    tinyint(1)       NOT NULL DEFAULT 0       COMMENT 'Отель в белом списке',

    PRIMARY KEY (`id`),
    FOREIGN KEY (`agency_id`) REFERENCES agencies(`id`),
    FOREIGN KEY (`hotel_id`)  REFERENCES hotels(`id`)
)
    ENGINE = InnoDB
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = utf8mb4
    COMMENT = 'Настройки агентства для отеля';

/* Можно, наверное, искать последний вариант по id, но это не MySql - подход */
ALTER TABLE `agency_hotel_options` ADD UNIQUE `idx___agency_hotel_options___hotel_agency` (`agency_id`, `hotel_id`);



CREATE TABLE `hotel_agreements` (
    `id`                int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID договора',
    `hotel_id`          int(10) unsigned NOT NULL                COMMENT 'ID отеля',
    `discount_percent`  int(10)          NOT NULL DEFAULT 0      COMMENT 'Процент скидки',
    `comission_percent` int(10)          NOT NULL DEFAULT 0      COMMENT 'Процент комиссии',
    `is_default`        tinyint(1)       NOT NULL DEFAULT 0      COMMENT 'Договор по умолчанию',
    `vat_percent`       int(10)          NOT NULL DEFAULT 0      COMMENT 'Процент НДС',
    `vat1_percent`      int(10)          NOT NULL DEFAULT 0      COMMENT 'Процент НДС1',
    `vat1_value`        int(10)          NOT NULL DEFAULT 0      COMMENT 'НДС значение',
    `company_id`        int(10) unsigned NOT NULL                COMMENT 'ID компании',
    `date_from`         datetime                  DEFAULT NULL   COMMENT 'Дата начала действия договора',
    `date_to`           datetime                  DEFAULT NULL   COMMENT 'Дата окончания действия договора',
    `is_cash_payment`   tinyint(1)       NOT NULL DEFAULT 0      COMMENT 'Возможность наличной оплаты',

    PRIMARY KEY (`id`),
    FOREIGN KEY (`company_id`) REFERENCES companies(`id`),
    FOREIGN KEY (`hotel_id`)   REFERENCES hotels(`id`)
)
    ENGINE = InnoDB
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = utf8mb4
    COMMENT = 'Договоры между компанией и отелем';



CREATE TABLE `filter_rule` (
    `id`          int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID правила',
    `agency_id`   int(10) unsigned NOT NULL                COMMENT 'ID агентства',
    `description` varchar(255)     NOT NULL                COMMENT 'Описание',
    `value`       JSON             NOT NULL                COMMENT 'Правила',

    PRIMARY KEY (`id`),
    FOREIGN KEY (`agency_id`) REFERENCES agencies(`id`)
)
    ENGINE = InnoDB
    AUTO_INCREMENT = 0
    DEFAULT CHARSET = utf8mb4
    COMMENT = 'Правила для поиска отелей';

ALTER TABLE `filter_rule` ADD UNIQUE `idx___filter_rule___description_agency` (`agency_id`, `description`);
