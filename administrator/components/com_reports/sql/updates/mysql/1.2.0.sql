drop table if exists `#__mkv_reports`;
create table `#__mkv_reports`
(
    id           smallint           not null auto_increment primary key,
    managerID    int                not null,
    title        text               not null,
    type         enum ('companies') not null,
    type_show    text               not null,
    params       text               null     default null,
    day_1        tinyint            not null default 0,
    day_2        tinyint            not null default 0,
    day_3        tinyint            not null default 0,
    day_4        tinyint            not null default 0,
    day_5        tinyint            not null default 0,
    day_6        tinyint            not null default 0,
    day_7        tinyint            not null default 0,
    cron_hour    tinyint unsigned            default 0,
    cron_minute  tinyint unsigned            default 0,
    cron_enabled tinyint                     default 0,
    constraint `#__mkv_reports_#__users_managerID_id_fk` foreign key (managerID) references `#__users` (id) on update cascade on delete cascade,
    index `#__mkv_reports_type_index` (type),
    index `#__mkv_reports_cron_status_1_index` (cron_enabled, day_1, cron_hour, cron_minute),
    index `#__mkv_reports_cron_status_2_index` (cron_enabled, day_2, cron_hour, cron_minute),
    index `#__mkv_reports_cron_status_3_index` (cron_enabled, day_3, cron_hour, cron_minute),
    index `#__mkv_reports_cron_status_4_index` (cron_enabled, day_4, cron_hour, cron_minute),
    index `#__mkv_reports_cron_status_5_index` (cron_enabled, day_5, cron_hour, cron_minute),
    index `#__mkv_reports_cron_status_6_index` (cron_enabled, day_6, cron_hour, cron_minute),
    index `#__mkv_reports_cron_status_7_index` (cron_enabled, day_7, cron_hour, cron_minute)
) character set utf8
  collate utf8_general_ci;
