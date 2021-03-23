create table steamAccount (
	id varchar(64) primary key not null,
	player int unsigned not null,
	foreign key(player) references player(id) on update cascade on delete cascade,
	profile int unsigned,
	foreign key(profile) references profile(id) on update cascade on delete cascade
) comment 'Most players will link their LANA account to a Steam account';
