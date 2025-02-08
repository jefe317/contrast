// Function to check table width and apply class
function checkTableWidth(tableSelector) {
	const tables = document.querySelectorAll(tableSelector);
	const viewportWidth = window.innerWidth;
	
	if (tables.length) {
		tables.forEach((wrapper) => {
			const table = wrapper.querySelector('table');
			const tableWidth = table.offsetWidth;
			const containerWidth = wrapper.parentElement.offsetWidth;
			
			console.log('Table width:', tableWidth);
			console.log('Viewport width:', viewportWidth);
			console.log('Container width:', containerWidth);
			
			if (tableWidth > containerWidth) {
				if (tableWidth <= viewportWidth * 0.95) {  // Using 95% of viewport as threshold
					console.log('Table fits viewport - using wide-table class');
					wrapper.classList.add('wide-table');
					wrapper.classList.remove('mobile-table');
				} else {
					console.log('Table exceeds viewport - using mobile-table class');
					wrapper.classList.add('mobile-table');
					wrapper.classList.remove('wide-table');
				}
			} else {
				console.log('Table fits container - removing both classes');
				wrapper.classList.remove('wide-table');
				wrapper.classList.remove('mobile-table');
			}
		});
	}
}

// Run on page load
document.addEventListener('DOMContentLoaded', () => {
	console.log('DOM loaded - checking tables');
	checkTableWidth('.table-wrapper');
});

// Run on window resize with debounce
let resizeTimer;
window.addEventListener('resize', () => {
	clearTimeout(resizeTimer);
	resizeTimer = setTimeout(() => {
		console.log('Window resized - checking tables');
		checkTableWidth('.table-wrapper');
	}, 50);
});