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
		async SignOut() {
			await AuthApi.SignOut();
			document.SignOut?.();
		}
	},
	mixins: [
		ClosePopup
	],
	template: /*html*/ `
		<div id=user-status>
			<button id=user-trigger @click.prevent.stop="showMenu = !showMenu" :class="{open: showMenu}">
				<img class=avatar :src=player.avatar>
				{{player.username}}
			</button>
			<nav id=user-actions v-if=showMenu v-close-popup=HideMenu>
				<a :href="'#player/' + player.username" class=profile title="" @click=HideMenu>Profile</a>
				<a href=#settings class=settings title="Change your settings" @click=HideMenu>Settings</a>
				<button class=signout title="Sign out from ${AppName.Full}" @click=SignOut>Sign out</button>
			</nav>
		</div>
	`
};
export default PlayerMenu;
