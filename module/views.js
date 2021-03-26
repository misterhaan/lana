const Views = {
	Home: {
		Name: "home",
		Title: ""
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
