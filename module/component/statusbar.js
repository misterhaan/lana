import AppName from "../appName.js";
import ClosePopup from "../mixin/closePopup.js";

let toastTimeout = false;

export default {
	props: [
		"lastError"
	],
	data() {
		return {
			errors: [],
			toastError: false,
			showErrors: false
		};
	},
	watch: {
		lastError(error) {
			if(error) {
				this.errors.push(error);
				this.toastError = error;
			}
		}
	},
	methods: {
		ToggleErrors() {
			if(!this.showErrors && this.toastError) {
				if(toastTimeout) {
					clearTimeout(toastTimeout);
					toastTimeout = false;
				}
				this.toastError = false;
			}
			this.showErrors = !this.showErrors;
		},
		HideErrors() {
			if(this.showErrors)
				this.ToggleErrors();
		},
		ClearErrors() {
			this.errors.splice(0, this.errors.length);
			this.showErrors = false;
		},
		DismissToast() {
			if(this.toastError) {
				const error = this.toastError;
				this.toastError = false;
				if(toastTimeout) {
					clearTimeout(toastTimeout);
					toastTimeout = false;
				}
				this.Dismiss(error);
			}
		},
		Dismiss(error) {
			this.errors.splice(this.errors.indexOf(error), 1);
			if(!this.errors.length)
				this.showErrors = false;
		}
	},
	directives: {
		toast: {
			bind(el) {
				$(el).hide();
			},
			update(el, bind, vnode) {
				if(bind.value) {
					if(toastTimeout) {
						clearTimeout(toastTimeout);
						toastTimeout = false;
					}
					$(el).fadeIn();
					toastTimeout = setTimeout(() => {
						toastTimeout = false;
						$(el).fadeOut(1600, () => {
							vnode.context[bind.expression] = false;
						});
					}, 5000);
				} else
					$(el).hide();
			}
		}
	},
	mixins: [ClosePopup],
	template: /*html*/ `
		<footer id=statusbar>
			<div id=errorToast v-toast=toastError>
				{{toastError.message}}
				<a class=close title="Dismiss this error" href=#dismissError @click.prevent=DismissToast><span>Dismiss</span></a>
			</div>
			<div id=errors v-if=showErrors v-close-popup=HideErrors>
				<header>
					{{errors.length }} Error{{errors.length > 1 ? "s" : ""}}
					<a class=minimize title="Minimize the error list" href=#hideErrors @click.prevent=HideErrors><span>Minimize</span></a>
					<a class=close title="Dismiss all errors" href=#dismissAllErrrors @click.prevent=ClearErrors><span>Dismiss all</span></a>
				</header>
				<ol class=errors>
					<li v-for="error in errors">
						{{error.message}}
						<a class=close title="Dismiss this error" href=#dismissError @click.prevent=Dismiss(error)><span>Dismiss</span></a>
					</li>
				</ol>
			</div>
			<a id=errorcount :title="showErrors ? 'Minimize the error list' : 'Show the error list'" v-if=errors.length href=#showErrors @click.prevent.stop=ToggleErrors>{{errors.length}}</a>
			<div id=copyright>© 2020 - 2021 ${AppName.Full}</div>
		</footer>
	`
}
