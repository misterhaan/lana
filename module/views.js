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
		DefaultSubViewName: "profile",
		SubViews: {
			Profile: {
				Name: "profile",
				Title: "Profile"
			},
			Links: {
				Name: "links",
				Title: "Links"
			},
			Email: {
				Name: "email",
				Title: "Email"
			},
			Accounts: {
				Name: "accounts",
				Title: "Accounts"
			}
		}
	}
};
export default Views;
