create table account (
	site varchar(20) not null comment 'Site ID that owns the account',
	id varchar(64) not null comment 'Unique account identifier within the owning site',
	primary key(site, id),
	player int unsigned not null,
	foreign key(player) references player(id) on update cascade on delete cascade,
	profile int unsigned,
	foreign key(profile) references profile(id) on update cascade on delete cascade
) comment 'Players link their LANA account to sign-in accounts on other sites';
