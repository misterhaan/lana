import AppName from "../appName.js";
import Signin from "./signin.js";

export default {
	props: [
		"user"
	],
	methods: {
		GoHome() {
			location.hash = "";
		}
	},
	components: {
		signin: Signin
	},
	template: /*html*/ `
		<header id=titlebar>
			<img id=favicon src=favicon.svg alt="" @click=GoHome>
			<h2 id=sitetitle @click=GoHome>${AppName.Full}</h2>
			<signin v-if=!user></signin>
		</header>
	`
}
