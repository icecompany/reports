create table `#__mkv_reports`
(
    id        smallint           not null auto_increment primary key,
    managerID int                not null,
    title     text               not null,
    type      enum ('companies') not null,
    params    text               null default null,
    constraint `#__mkv_reports_#__users_managerID_id_fk` foreign key (managerID) references `#__users` (id) on update cascade on delete cascade,
    index `#__mkv_reports_type_index` (type)
) character set utf8
  collate utf8_general_ci;
