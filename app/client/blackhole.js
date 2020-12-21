function getSize(e) {
	var { width, height } = window.getComputedStyle(e);
	width = +width.slice(0, -2) / window.devicePixelRatio;
	height = +height.slice(0, -2) / window.devicePixelRatio;
	return { width, height };
}
function setStyle(e, style) {
	for (var k in style)
		Reflect.set(e.style, k, style[k]);
}
function padSize(size, padding) {
	const WIDTH_MIDDLE = 1920, MAX = (1 - 1/Math.E);

	const W = - WIDTH_MIDDLE / Math.log(1 / (Math.E * MAX) - 1);
	var { top, left, width, height } = size;
	var paddings = padding.split(' ').map((s) => +s);
	var paddingWidth = (1 - Math.exp(- width / W)) * MAX * width / 2;
	var paddingHeight = (1 - Math.exp(- height / W)) * MAX * height / 2;
	var newTop =
		top + paddingHeight * paddings[0 % paddings.length];
	var newRight =
		left +
		width -
		paddingWidth * paddings[1 % paddings.length];
	var newBottom =
		top +
		height -
		paddingHeight * paddings[2 % paddings.length];
	var newLeft =
		left + paddingWidth * paddings[3 % paddings.length];
	return {
		top: newTop,
		left: newLeft,
		width: newRight - newLeft,
		height: newBottom - newTop,
	};
}

function render(e, size, changeStyle=true) {
	var t;
	if ((t = e.getAttribute('padding'))) {
		size = padSize(size, t);
	}
	var { top, left, width, height } = size;
	if (changeStyle)
		e.style.position = 'absolute';
		setStyle(e, {
			top: `${top * window.devicePixelRatio}px`,
			left: `${left * window.devicePixelRatio}px`,
			width: `${width * window.devicePixelRatio}px`,
			height: `${height * window.devicePixelRatio}px`,
		});

	switch(e.localName){
		case 'body':
			render(e.children[0], size);
			break;
		case 'form':
			var children = [...e.children];
			if (children.length) {
				var { width, height } = size;
				var top = 0,
					left = 0;
				height = height / children.length;
				for (c of children) {
					render(c, { top, left, width, height });
					top += height;
				}
			}
			break;
		default:
			console.log(`Unimplemented element ${e.localName}`);
	}
}

var lastWidth, lastHeight;
var listener = (event) => {
	var width = innerWidth / window.devicePixelRatio;
	var height = innerHeight / window.devicePixelRatio;
	if (width !== lastWidth || height !== lastHeight){
		render(
			document.body,
			{
				top: 0,
				left: 0,
				width,
				height
			},
			false
		);
		lastWidth = width;
		lastHeight = height;
	}
};
document.addEventListener('DOMContentLoaded', () => {
	var style = document.createElement('style');
	style.id = '--blackhole';
	document.head.append(style);

	var styleSheet = style.sheet;
	styleSheet.insertRule(`
		* {
			box-sizing: border-box;
			margin: 0;
			max-width: 100%;
			/*max-height: 100%;*/
		}
	`);
	styleSheet.insertRule(`
		html, body {
			width: 100%;
			height: 100%;
		}
	`);

	listener();
	window.addEventListener('resize', listener);
	new MutationObserver(listener).observe(
		document.documentElement,
		{
			subtree: true,
			childList: true,
		}
	);
});
