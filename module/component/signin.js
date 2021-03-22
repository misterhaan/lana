import ClosePopup from "../mixin/closePopup.js";
import AppName from "../appName.js";
import AuthApi from "../api/auth.js";

export default {
	props: [
		"auths"
	],
	data() {
		return {
			showMenu: false,
			chosenAuth: false,
			remember: false,
			working: false
		};
	},
	computed: {
		buttonTooltip() {
			return this.chosenAuth
				? `Visit ${this.chosenAuth.name} to sign in to ${AppName.Short} using your ${this.chosenAuth.name} account`
				: "Choose an account type to enable sign in";
		}
	},
	created() {
		if(this.auths && this.auths.length)
			this.chosenAuth = this.auths[0];
	},
	watch: {
		auths() {
			if(this.chosenAuth)
				for(const auth in this.auths)
					if(auth.id == this.chosenAuth.id) {
						this.chosenAuth = auth;
						return;
					}
			if(this.auths && this.auths.length)
				this.chosenAuth = this.auths[0];
		}
	},
	methods: {
		HideMenu() {
			this.showMenu = false;
		},
		SignIn() {
			if(!this.working && this.chosenAuth) {
				this.working = true;
				AuthApi.GetSignInUrl(this.chosenAuth.id, location.hash, this.remember).done(result => {
					location = result;
				}).fail(this.Error).always(() => {
					this.working = false;
				});
			}
		}
	},
	mixins: [
		ClosePopup
	],
	template: /*html*/ `
		<div id=userstatus>
			<button id=usertrigger @click.prevent.stop="showMenu = !showMenu" :class="{open: showMenu}">Sign in</button>
			<div id=signin v-if=showMenu v-close-popup=HideMenu>
				<p>Sign in securely with your account from one of these sites:</p>
				<div id=authChoices class=filledIcons role=radiogroup>
					<button v-for="auth in auths" :class=auth.id :title="'Sign in with your ' + auth.name + ' account'"
						role=radio :aria-checked="auth == chosenAuth" @click="chosenAuth = auth"></button>
				</div>
				<button id=remember :class="{checked: remember, unchecked: !remember}" title="Save a secure key in this browser to sign in automatically"
					aria-role=switch :aria-checked=remember @click="remember = !remember">Remember me
				</button>
				<nav class=calltoaction><button role=link :disabled="!chosenAuth || working" :title=buttonTooltip :class="{working: working}" @click=SignIn>
					{{chosenAuth ? "Sign in with " + chosenAuth.name : "Sign in"}}
				</button></nav>
			</div>
		</div>
	`
}
