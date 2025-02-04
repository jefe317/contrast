// Function to check table width and apply class
function checkTableWidth(tableSelector, threshold = 625) {
	const tables = document.querySelectorAll(tableSelector);
	if (tables.length) {
		tables.forEach((table, index) => {
			const tableWidth = table.offsetWidth;
			if (tableWidth > threshold) {
				table.classList.add('wide-table');
			} else {
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