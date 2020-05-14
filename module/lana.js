import Vue from "../external/vue.esm.browser.min.js";
import AppName from "./appName.js";
import TitleBar from "./component/titlebar.js";
import StatusBar from "./component/statusbar.js";

new Vue({
	el: "#lana",
	components: {
		titlebar: TitleBar,
		statusbar: StatusBar
	},
	template: /*html*/ `
		<div id=lana>
			<titlebar></titlebar>
			<main>
			<h1>Welcome!</h1>
			<p>
				It’s easier to game together when you LAN Ahead!
			</p>
			</main>
			<statusbar :last-error=error></statusbar>
		</div>
	`
});
