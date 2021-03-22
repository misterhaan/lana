create table email (
	address varchar(64) primary key not null comment 'Should be a valid email address',
	player int unsigned not null,
	foreign key(player) references player(id) on update cascade on delete cascade,
	profile int unsigned,
	foreign key(profile) references profile(id) on update cascade on delete cascade,
	isPrimary bit not null default 1 comment 'Each player with any rows in this database must have exactly one where this column is true'
) comment 'Players with an email linked can get emails from LANA and be found by friends searching by email';
