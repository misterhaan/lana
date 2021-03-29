import Views from "../views.js";
import NeedsPlayer from "./needsPlayer.js";
import Accounts from "./accounts.js";
import ReportError from "../mixin/reportError.js";

const Settings = {
	props: [
		"view",
		"params",
		"player",
		"auths"
	],
	mixins: [ReportError],
	components: {
		needsPlayer: NeedsPlayer,
		[Views.Settings.SubViews.Accounts.Name]: Accounts
	},
	template: /*html*/ `
		<main>
			<h1>Settings</h1>
			<needsPlayer :has-player=player>
				<component :is=view.Name :params=params :auths=auths @error=Error></component>
			</needsPlayer>
		</main>
	`
};
export default Settings;
