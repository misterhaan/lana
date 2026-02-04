create view profile_link as
	select
		l.id,
		coalesce(a.player, e.player, pp.player) as player,
		case
			when a.player is not null then a.site
			when e.player is not null then 'email'
			when pp.player is not null then 'web'
		end as type,
		l.name,
		l.url,
		l.visibility
	from profile as l
		left join account as a on a.profile=l.id
		left join email as e on e.profile=l.id
		left join player_profile as pp on pp.profile=l.id
	where coalesce(a.player, e.player, pp.player) is not null
	order by coalesce(a.player, e.player, pp.player), visibility desc, type, name;
