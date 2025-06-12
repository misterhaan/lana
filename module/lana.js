import { createApp } from "../external/vue.esm-browser.prod.js";
import AppName from "./appName.js";
import TitleBar from "./component/titleBar.js";
import StatusBar from "./component/statusBar.js";
import Views from "./views.js";
import Home from "./component/home.js";
import Settings from "./component/settings.js";
import AuthApi from "./api/auth.js";

const lana = createApp({
	name: "LANA",
	data() {
		return {
			view: Views.Home,
			subView: false,
			params: false,
			auths: false,
			player: false,
			error: false
		}
	},
	watch: {
		view(val) {
			let title = val.Title;
			if(title) {
				title += " - " + AppName.Short;
				if(val.SubViews)
					if(this.subView)
						title = this.subView.Title + " - " + title;
					else
						for(const v in val.SubViews)
							if(val.SubViews[v].Name == val.DefaultSubViewName) {
								title = val.SubViews[v].Title + " - " + title;
								break;
							}
				document.title = title;
			} else
				document.title = AppName.Short;
		}
	},
	created() {
		this.ParseHash();
		$(window).on("hashchange", this.ParseHash);
		AuthApi.List().done(result => {
			this.auths = result;
		}).fail(this.Error);
		AuthApi.Player().done(result => {
			this.player = result;
		}).fail(this.Error);
	},
	methods: {
		ParseHash() {
			if(location.hash == "" || location.hash == "#")
				this.ChangeView(Views.Home);
			else {
				let hash = location.hash.substring(1).split("!");
				let viewPieces = hash.shift().split("/");
				const viewName = viewPieces.shift();
				let view = false;
				for(const v in Views)
					if(Views[v].Name == viewName) {
						view = Views[v];
						break;
					}
				if(view) {
					let subView = false;
					if(view.SubViews) {
						const subViewName = viewPieces.shift() || view.DefaultSubViewName;
						for(const sv in view.SubViews)
							if(view.SubViews[sv].Name == subViewName) {
								subView = view.SubViews[sv];
								break;
							}
					}

					let params = false;
					if(hash.length) {
						params = {};
						const paramList = hash.join("!").split("/");
						for(const p of paramList) {
							let pair = p.split("=");
							if(pair.length > 1)
								params[decodeURIComponent(pair.shift())] = decodeURIComponent(pair.join("="));
						}
					}

					if(this.view != view || this.subView != subView || (params === false) != (this.params === false) || new URLSearchParams(this.params).toString() != new URLSearchParams(params).toString())
						this.ChangeView(view, subView, params);
				}
			}
		},
		ChangeView(view, subView = false, params = false) {
			this.params = params;
			if(this.view != view || this.subView != subView) {
				this.subView = subView;
				this.view = view;
			}
		},
		Error(error) {
			this.error = typeof error == "string"
				? new Error(error)
				: error;
		}
	},
	template: /*html*/ `
		<div id=lana>
			<titleBar :auths=auths :player=player></titleBar>
			<component :is=view.Name :view=subView :params=params :auths=auths :player=player @error="error = $event"></component>
			<statusBar :last-error=error></statusBar>
		</div>
	`
}).component("titleBar", TitleBar)
	.component("statusBar", StatusBar)
	.component(Views.Home.Name, Home)
	.component(Views.Settings.Name, Settings)
	.mount("#lana");

/**
 * Define sign out function on the document object so that it can happen
 * generically from the API level.
 */
document.SignOut = function() {
	lana.player = false;
};
