const ClosePopup = {
	directives: {
		"close-popup": {
			created(el, binding) {
				if(!document.popupStack) {
					document.popupStack = [];
					document.addEventListener("keyup", e => {
						if(e.key == "Escape")
							for(let popup = document.popupStack.pop(); popup; popup = document.popupStack.pop())
								if(document.body.contains(popup)) {
									popup.popupHide(e);
									return;  // leave other popups on the stack
								}
					});
					document.addEventListener("click", e => {
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
			unmounted(el) {
				const index = document.popupStack.indexOf(el);
				if(index > -1)
					document.popupStack.splice(index, 1);
			}
		}
	}
};
export default ClosePopup;
