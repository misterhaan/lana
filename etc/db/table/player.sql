create table player (
	id int unsigned primary key not null auto_increment,
	username varchar(20) not null,
	realName varchar(64) null comment 'Real name is optional and only shown to certain friends',
	avatarProfile int unsigned comment 'Profile that contains the avatar for this player',
	foreign key(avatarProfile) references profile(id) on update cascade on delete set null,

	firstLogin datetime not null default now(),
	lastLogin datetime not null default now(),
	lastRequest datetime not null default now()
) comment 'Players are the site users';
