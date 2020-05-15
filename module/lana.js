import Vue from "../external/vue.esm.browser.min.js";
import AppName from "./appName.js";
import TitleBar from "./component/titlebar.js";
import StatusBar from "./component/statusbar.js";
import Views from "./views.js";
import Home from "./component/home.js";

new Vue({
	el: "#lana",
	data: {
		view: Views.Home,
		subView: false,
		params: false
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
						const paramlist = hash.join("!").split("/");
						for(const p of paramlist) {
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
		}
	},
	components: {
		titlebar: TitleBar,
		statusbar: StatusBar,
		[Views.Home.Name]: Home
	},
	template: /*html*/ `
		<div id=lana>
			<titlebar></titlebar>
			<component :is=view.Name :view=subView :params=params></component>
			<statusbar></statusbar>
		</div>
	`
});
