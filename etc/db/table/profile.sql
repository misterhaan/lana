create table profile (
	id int unsigned primary key auto_increment,
	name varchar(100) not null default '' comment 'Display name of this profile',
	url varchar(200) not null default '' comment 'URL to this profile on the external site',
	avatar varchar(200) not null default 'URL to the avatar for this profile'
) comment 'Players can use an avatar from their profiles on other websites';
