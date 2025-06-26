import AppName from "../appName.js";
import ClosePopup from "../mixin/closePopup.js";

const StatusBar = {
	data() {
		return {
			errors: [],
			toastError: null,
			showErrors: false
		};
	},
	created() {
		window.addEventListener("error", e => {
			this.NewError(e.error);
			e.preventDefault();
			e.stopPropagation();
		});
		window.addEventListener("unhandledrejection", e => {
			this.NewError(e.reason);
			e.preventDefault();
			e.stopPropagation();
		});
	},
	methods: {
		NewError(error) {
			if(error) {
				this.errors.push(error);
				this.toastError = error;
			}
		},
		ToggleErrors() {
			if(!this.showErrors && this.toastError)
				this.ClearToast();
			this.showErrors = !this.showErrors;
		},
		HideErrors() {
			if(this.showErrors)
				this.ToggleErrors();
		},
		ClearToast() {
			this.toastError = null;
		},
		ClearErrors() {
			this.errors.splice(0, this.errors.length);
			this.showErrors = false;
		},
		DismissToast() {
			if(this.toastError) {
				const error = this.toastError;
				this.ClearToast();
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
			created(el) {
				el.dataset.timeout = 0;
				$(el).hide();
			},
			updated(el, bind) {
				if(el.dataset.timeout) {
					clearTimeout(el.dataset.timeout);
					el.dataset.timeout = 0;
				}
				if(bind.value.message) {
					$(el).fadeIn();
					el.dataset.timeout = setTimeout(() => {
						el.dataset.timeout = 0;
						$(el).fadeOut(1600, () => {
							bind.value.afterFadeOut?.();
						});
					}, 5000);
				} else
					$(el).hide();
			}
		}
	},
	mixins: [ClosePopup],
	template: /*html*/ `
		<footer id=status-bar>
			<div id=errorToast class=error v-toast="{message: toastError, afterFadeOut: ClearToast}">
				{{toastError?.message}}
				<a class=close title="Dismiss this error" href=#dismissError @click.prevent=DismissToast><span>Dismiss</span></a>
			</div>
			<div id=errors v-if=showErrors v-close-popup=HideErrors>
				<header>
					{{errors.length }} Error{{errors.length > 1 ? "s" : ""}}
					<a class=minimize title="Minimize the error list" href=#hideErrors @click.prevent=HideErrors><span>Minimize</span></a>
					<a class=close title="Dismiss all errors" href=#dismissAllErrors @click.prevent=ClearErrors><span>Dismiss all</span></a>
				</header>
				<ol class=errors>
					<li v-for="error in errors">
						{{error.message}}
						<a class=close title="Dismiss this error" href=#dismissError @click.prevent=Dismiss(error)><span>Dismiss</span></a>
					</li>
				</ol>
			</div>
			<a id=errorcount class=error :title="showErrors ? 'Minimize the error list' : 'Show the error list'" v-if=errors.length href=#showErrors @click.prevent.stop=ToggleErrors>{{errors.length}}</a>
			<div id=copyright>© 2020 - 2025 ${AppName.Full}</div>
		</footer>
	`
}
export default StatusBar;
