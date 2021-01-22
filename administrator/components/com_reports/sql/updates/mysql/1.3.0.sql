alter table `#__mkv_reports`
    modify `type` enum('companies', 'sentInvites') not null default 'companies';

