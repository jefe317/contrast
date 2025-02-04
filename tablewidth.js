// Function to check table width and apply class
function checkTableWidth(tableSelector, threshold = 625) {
	const tables = document.querySelectorAll(tableSelector);
	if (tables.length) {
		tables.forEach((table, index) => {
			const tableWidth = table.offsetWidth;
			if (tableWidth > threshold) {
				console.log(`Table ${index + 1} is wider than threshold, adding wide-table class`);
				table.classList.add('wide-table');
			} else {
				console.log(`Table ${index + 1} is narrower than threshold, removing wide-table class`);
				table.classList.remove('wide-table');
			}
		});
	}
}
document.addEventListener('DOMContentLoaded', () => {
	checkTableWidth('.table-wrapper');
});
window.addEventListener('resize', () => {
	checkTableWidth('.table-wrapper');
});