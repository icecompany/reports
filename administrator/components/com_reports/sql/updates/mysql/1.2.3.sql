drop table if exists `#__mkv_sales`;
create table `#__mkv_sales`
(
    id        int unsigned      not null auto_increment primary key,
    projectID smallint unsigned not null,
    dat       date              not null,
    itemID    smallint unsigned not null,
    value     double(11, 2)     not null default 0,
    constraint `#__mkv_sales_#__mkv_projects_projectID_id_fk`
        foreign key (projectID) references `#__mkv_projects` (id)
            on update cascade on delete cascade,
    constraint `#__mkv_sales_#__mkv_price_items_itemID_id_fk`
        foreign key (itemID) references `#__mkv_price_items` (id)
            on update cascade on delete cascade,
    unique index `#__mkv_sales_projectID_dat_itemID_uindex` (projectID, dat, itemID)
) character set utf8
  collate utf8_general_ci;

