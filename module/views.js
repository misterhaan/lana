const Views = {
	Home: {
		Name: "home",
		Title: ""
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
