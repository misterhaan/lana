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
		DefaultSubViewName: "accounts",
		SubViews: {
			Accounts: {
				Name: "accounts",
				Title: "Accounts"
			}
		}
	}
};
export default Views;
