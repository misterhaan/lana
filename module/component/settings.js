import Views from "../views.js";
import NeedsPlayer from "./needsPlayer.js";
import Accounts from "./accounts.js";
import Links from "./links.js";

const Settings = {
	props: [
		"view",
		"params",
		"player",
		"auths"
	],
	components: {
		needsPlayer: NeedsPlayer,
		[Views.Settings.SubViews.Accounts.Name]: Accounts,
		[Views.Settings.SubViews.Links.Name]: Links
	},
	template: /*html*/ `
		<main>
			<h1>Settings</h1>
			<needsPlayer :has-player=player>
				<div class=pages>
					<nav>
						<a class=links href="#settings/links" :class="{selected: view.Name == 'links'}">Links</a>
						<a class=accounts href="#settings/accounts" :class="{selected: view.Name == 'accounts'}">Accounts</a>
					</nav>
					<component :is=view.Name :params=params :auths=auths></component>
				</div>
			</needsPlayer>
		</main>
	`
};
export default Settings;
