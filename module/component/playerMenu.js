import ClosePopup from "../mixin/closePopup.js";
import AppName from "../appName.js";
import AuthApi from "../api/auth.js";

const PlayerMenu = {
	props: [
		"player"
	],
	data() {
		return {
			showMenu: false
		};
	},
	methods: {
		HideMenu() {
			this.showMenu = false;
		},
		SignOut() {
			AuthApi.SignOut().done(() => {
				document.SignOut && document.SignOut();
			});
		}
	},
	mixins: [
		ClosePopup
	],
	template: /*html*/ `
		<div id=userstatus>
			<button id=usertrigger @click.prevent.stop="showMenu = !showMenu" :class="{open: showMenu}">
				<img class=avatar :src=player.avatar>
				{{player.username}}
			</button>
			<nav id=useractions v-if=showMenu v-close-popup=HideMenu>
				<button class=signout title="Sign out from ${AppName.Full}" @click=SignOut>Sign out</button>
			</nav>
		</div>
	`
};
export default PlayerMenu;
