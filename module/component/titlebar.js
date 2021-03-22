import AppName from "../appName.js";
import Signin from "./signin.js";
import PlayerMenu from "./playerMenu.js";

export default {
	props: [
		"player",
		"auths"
	],
	methods: {
		GoHome() {
			if(location.hash)
				location.hash = "";
			if(!location.pathname.endsWith("/"))
				location.pathname = location.pathname.substr(0, location.pathname.lastIndexOf("/"));
		}
	},
	components: {
		signin: Signin,
		playerMenu: PlayerMenu
	},
	template: /*html*/ `
		<header id=titlebar>
			<img id=favicon src=favicon.svg alt="" @click=GoHome>
			<h2 id=sitetitle @click=GoHome>${AppName.Full}</h2>
			<signin v-if="!player && auths" :auths=auths></signin>
			<playerMenu v-if=player :player=player></playerMenu>
		</header>
	`
}
