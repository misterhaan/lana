const Views = {
	Home: {
		Name: "home",
		Title: ""
	},
	Player: {
		Name: "player",
		Title: "Player"
	},
	Players: {
		Name: "players",
		Title: "Players"
	},
	Settings: {
		Name: "settings",
		Title: "Settings",
		DefaultSubViewName: "links",
		SubViews: {
			Links: {
				Name: "links",
				Title: "Links"
			},
			Accounts: {
				Name: "accounts",
				Title: "Accounts"
			}
		}
	}
};
export default Views;
