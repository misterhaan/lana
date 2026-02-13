import Views from "../views.js";
import NeedsPlayer from "./needsPlayer.js";
import Profile from "./profile.js";
import Links from "./links.js";
import Accounts from "./accounts.js";

const Settings = {
	props: [
		"view",
		"params",
		"player",
		"auths"
	],
	components: {
		needsPlayer: NeedsPlayer,
		[Views.Settings.SubViews.Profile.Name]: Profile,
		[Views.Settings.SubViews.Links.Name]: Links,
		[Views.Settings.SubViews.Accounts.Name]: Accounts
	},
	template: /*html*/ `
		<main>
			<h1>Settings</h1>
			<needsPlayer :has-player=player>
				<div class=pages>
					<nav>
						<a class=profile href="#settings/profile" :class="{selected: view.Name == 'profile'}">Profile</a>
						<a class=links href="#settings/links" :class="{selected: view.Name == 'links'}">Links</a>
						<a class=accounts href="#settings/accounts" :class="{selected: view.Name == 'accounts'}">Accounts</a>
					</nav>
					<component :is=view.Name :params=params :auths=auths :player=player></component>
				</div>
			</needsPlayer>
		</main>
	`
};
export default Settings;
