create table player_profile (
	player int unsigned not null,
	foreign key(player) references player(id) on update cascade on delete cascade,
	profile int unsigned,
	foreign key(profile) references profile(id) on update cascade on delete cascade,
	primary key(player, profile)
) comment 'Players can manually add profiles to their account to show as links but not authentication';
