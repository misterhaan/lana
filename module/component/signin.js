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
	directives: {
		"close-popup": {
			bind(el, binding) {
				if(!document.popupStack) {
					document.popupStack = [];
					$(document).keyup(e => {
						if(e.key == "Escape")
							for(let popup = document.popupStack.pop(); popup; popup = document.popupStack.pop())
								if(document.body.contains(popup)) {
									popup.popupHide(e);
									return;  // leave other popups on the stack
								}
					}).click(e => {
						for(let popup = document.popupStack.pop(); popup; popup = document.popupStack.pop())
							if(document.body.contains(popup)) {
								if(popup == e.target || popup.contains(e.target))
									document.popupStack.push(popup);  // clicked the popup, so keep it on the stack and don't hide it
								else
									popup.popupHide(e);
								return;  // leave the other popups on the stack
							}
					});
				}
				el.popupHide = binding.value;
				document.popupStack.push(el);
			},
			unbind(el) {
				const index = document.popupStack.indexOf(el);
				if(index > -1)
					document.popupStack.splice(index, 1);
			}
		}
	},
	template: /*html*/ `
		<div id=userstatus>
			<a id=usertrigger @click.prevent.stop="showMenu = !showMenu" :class="{open: showMenu}">Sign In</a>
			<div id=signin v-if=showMenu v-close-popup=HideMenu>
				<p>sign-in options go here</p>
			</div>
		</div>
	`
}
