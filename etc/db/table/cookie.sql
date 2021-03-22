create table cookie (
	series char(16) primary key not null comment 'A series starts whenever a player signs in with remember selected',
	tokenHash char(88) not null comment 'Hash of the current token for this series',
	expires datetime not null comment 'Cookies past their expire date will be deleted',
	player int unsigned not null comment 'Player who can log in with this token and series',
	foreign key(player) references player(id) on update cascade on delete cascade
) comment 'Cookies allow a player to log in automatically';
