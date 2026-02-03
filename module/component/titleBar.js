import AppName from "../appName.js";
import Signin from "./signin.js";
import PlayerMenu from "./playerMenu.js";

const TitleBar = {
	props: [
		"player",
		"auths"
	],
	mounted() {
		window.addEventListener('scroll', () => {
			document.body.classList.toggle("scrolled", window.scrollY >= 24);
		});
	},
	components: {
		signin: Signin,
		playerMenu: PlayerMenu
	},
	template: /*html*/ `
		<header id=titleBar>
			<a href=. id=goHome>
				<img id=favicon src=favicon.svg alt=""><span id=siteTitle>${AppName.Full}</span>
			</a>
			<signin v-if="!player && auths" :auths=auths></signin>
			<playerMenu v-if=player :player=player></playerMenu>
		</header>
	`
};
export default TitleBar;
