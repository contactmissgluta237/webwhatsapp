var options = {
    series: [{
        name: 'Revenues',
        data: [200000, 300000, 250000, 450000, 400000]
    },
    {
        name: 'Dépenses',
        data: [120000, 150000, 130000, 200000, 170000]
    }],
    chart: {
        type: 'bar',
        height: 350,
    },
    colors: ['rgba(var(--primary),.8)', 'rgba(var(--warning),.8)'],
    xaxis: {
        categories: ['Jan', 'Fev', 'Mar', 'Avr', 'Mai'],
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: '55%',
        endingShape: 'rounded'
      },
    },
    dataLabels: {
      enabled: false
    },
    stroke: {
      show: true,
      width: 2,
      colors: ['transparent']
    },
};

var chart = new ApexCharts(document.querySelector("#revenue-expenses-chart"), options);
chart.render();
