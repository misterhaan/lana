const DragDrop = {
	Data: null,
	Targets: [],
	directives: {
		draggable: {
			created(element, bind) {
				element.draggable = bind.value.disabled ? null : "true";
				element.dragData = bind.value.data;
				element.dataset.dragName = bind.value.name;
				element.addEventListener("dragstart", event => {
					element.classList.add("dragging");
					DragDrop.Data = element.dragData;
					event.dataTransfer.effectAllowed = "move";
					event.dataTransfer.setData("text/plain", element.dataset.dragName);
				});
				element.addEventListener("dragend", () => {
					element.classList.remove("dragging");
				});
				element.addEventListener("click", event => {
					event.stopPropagation();
					document.querySelectorAll("[draggable].dragging").forEach(drag => {
						drag.classList.remove("dragging");
					});
					if(element.dragData == DragDrop.Data) {
						StopClickedDrag();
						DragDrop.Data = null;
					} else {
						element.classList.add("dragging");
						DragDrop.Data = element.dragData;
						DragDrop.Targets.forEach(target => {
							if(!target.contains(element))
								target.classList.add("droptarget");
						});
					}
				});
			},
			updated(element, bind) {
				element.draggable = bind.value.disabled ? null : "true";
				element.dragData = bind.value.data;
				element.dataset.dragName = bind.value.name;
			}
		},
		"drop-target": {
			created(element, bind) {
				DragDrop.Targets.push(element);
				let dragLevel = 0;
				element.dropData = bind.value.data;
				element.addEventListener("dragover", event => {
					event.preventDefault();
					if(element.dropData != DragDrop.Data && event.dataTransfer)
						event.dataTransfer.dropEffect = "move";
				});
				element.addEventListener("dragenter", () => {
					if(element.dropData != DragDrop.Data) {
						dragLevel++;
						element.classList.add("droptarget");
					}
				});
				element.addEventListener("dragleave", () => {
					if(element.dropData != DragDrop.Data && !--dragLevel)
						element.classList.remove("droptarget");
				});
				element.addEventListener("drop", event => {
					if(element.dropData != DragDrop.Data) {
						dragLevel = 0;
						element.classList.remove("droptarget");
						bind.value.onDrop(DragDrop.Data, element.dropData);
						DragDrop.Data = null;
					}
					event.stopPropagation();
					event.preventDefault();
				});
				element.addEventListener("click", () => {
					if(DragDrop.Data && DragDrop.Data != element.dropData) {
						StopClickedDrag();
						bind.value.onDrop(DragDrop.Data, element.dropData);
						DragDrop.Data = null;
					}
				});
			},
			updated(element, bind) {
				element.dropData = bind.value.data;
			},
			unmounted(element) {
				const index = DragDrop.Targets.indexOf(element);
				if(~index)
					DragDrop.Targets.splice(index, 1);
			}
		}
	}
};
export default DragDrop;

function StopClickedDrag() {
	document.querySelectorAll("[draggable].dragging").forEach(drag => {
		drag.classList.remove("dragging");
	});
	DragDrop.Targets.forEach(target => {
		target.classList.remove("droptarget");
	});
}
