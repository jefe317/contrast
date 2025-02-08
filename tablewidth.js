// Function to check table width and apply class
function checkTableWidth(tableSelector) {
	const tables = document.querySelectorAll(tableSelector);
	const viewportWidth = window.innerWidth;
	
	if (tables.length) {
		tables.forEach((wrapper) => {
			const table = wrapper.querySelector('table');
			const tableWidth = table.offsetWidth;
			const containerWidth = wrapper.parentElement.offsetWidth;
			
			if (tableWidth > containerWidth) {
				if (tableWidth <= viewportWidth * 0.95) {  // Using 95% of viewport as threshold
					wrapper.classList.add('wide-table');
					wrapper.classList.remove('mobile-table');
				} else {
					wrapper.classList.add('mobile-table');
					wrapper.classList.remove('wide-table');
				}
			} else {
				wrapper.classList.remove('wide-table');
				wrapper.classList.remove('mobile-table');
			}
		});
	}
}

// Run on page load
document.addEventListener('DOMContentLoaded', () => {
	checkTableWidth('.table-wrapper');
});

// Run on window resize with debounce
let resizeTimer;
window.addEventListener('resize', () => {
	clearTimeout(resizeTimer);
	resizeTimer = setTimeout(() => {
		checkTableWidth('.table-wrapper');
	}, 50);
});