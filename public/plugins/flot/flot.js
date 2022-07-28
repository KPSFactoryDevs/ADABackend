$(function () {
	'use strict';

	/**************** PIE CHART *******************/
	var piedata = [{
		label: 'Series 1',
		data: [
			[1, 10]
		],
		color: '#3366ff'
	}, {
		label: 'Series 2',
		data: [
			[1, 50]
		],
		color: '#fe7f00'
	}, {
		label: 'Series 3',
		data: [
			[1, 30]
		],
		color: '#ffad00'
	}, {
		label: 'Series 4',
		data: [
			[1, 30]
		],
		color: '#2dcbf7'
	}, {
		label: 'Series 5',
		data: [
			[1, 60]
		],
		color: '#01c353'
	}];

	$.plot('#flotPie2', piedata, {
		series: {
			pie: {
				show: true,
				radius: 1,
				innerRadius: 0.5,
				label: {
					show: true,
					radius: 2 / 3,
					formatter: labelFormatter,
					threshold: 0.1
				}
			}
		},
		grid: {
			hoverable: false,
			clickable: true
		}
	});

	function labelFormatter(label, series) {
		return '<div style="font-size:8pt; text-align:center; padding:2px; color:white;">' + label + '<br/>' + Math.round(series.percent) + '%</div>';
	}
});