function getSize(e) {
	var { width, height } = window.getComputedStyle(e.parentElement);
	width = +width.slice(0, -2);
	height = +height.slice(0, -2);
	return { width, height };
}

function zoomElements() {
	document.documentElement.style.zoom = window.devicePixelRatio;
}
function fitText() {
	for (var e of [document.body, ...document.body.querySelectorAll('*')]) {
		if (
			!(e instanceof HTMLInputElement || e instanceof HTMLHeadingElement)
		) {
			var width = getSize(e).width;
			var fontSize = Math.max(12, width / 16);
			e.style.fontSize = `${fontSize}px`;
		}
	}
}
