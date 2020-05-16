import ClosePopup from "../mixin/closePopup.js";

export default {
	data() {
		return {
			showMenu: false
		};
	},
	methods: {
		HideMenu() {
			this.showMenu = false;
		}
	},
	mixins: [
		ClosePopup
	],
	template: /*html*/ `
		<div id=userstatus>
			<a id=usertrigger @click.prevent.stop="showMenu = !showMenu" :class="{open: showMenu}">Sign In</a>
			<div id=signin v-if=showMenu v-close-popup=HideMenu>
				<p>sign-in options go here</p>
			</div>
		</div>
	`
}
