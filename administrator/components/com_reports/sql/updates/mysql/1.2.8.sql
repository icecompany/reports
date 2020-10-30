delete from `#__mkv_managers_stat` where dat = '2020-06-15';
alter table `#__mkv_managers_stat`
    add unique index `#__mkv_managers_stat_dat_manager_project_index` (dat, managerID, projectID);