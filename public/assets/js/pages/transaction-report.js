var options = {
    series: [
      {
        name: "Total des ventes",
        data: [1500000, 1800000, 1200000, 2000000, 1700000, 2200000, 1900000, 1600000, 2500000, 2100000, 2300000, 2400000]
      },
      {
        name: "Total des achats",
        data: [900000, 850000, 950000, 1000000, 970000, 1100000, 980000, 1020000, 990000, 930000, 870000, 1050000]
      },
      {
        name: "Total des transactions",
        data: [2400000, 2650000, 2150000, 3000000, 2670000, 3300000, 2880000, 2620000, 3490000, 3030000, 3170000, 3450000]
      }
    ],
    chart: {
      fontFamily: 'Montserrat", system-ui',
      height: 350,
      type: 'line',
      zoom: { enabled: false },
    },
    colors: ['rgba(var(--success),1)', 'rgba(var(--warning),1)', 'rgba(var(--info),1)'],
    dataLabels: { enabled: false },
    stroke: {
      width: [4, 4, 4],
      curve: 'smooth',
      dashArray: [0, 0, 0]
    },
    legend: {
      tooltipHoverFormatter: function (val, opts) {
        return val + ' - <strong>' + opts.w.globals.series[opts.seriesIndex][opts.dataPointIndex] + ' CFA</strong>';
      }
    },
    markers: {
      size: 0,
      hover: {
        sizeOffset: 6
      }
    },
    xaxis: {
      categories: ['Jan', 'Fév', 'Mars', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
      labels: {
        style: {
          fontSize: '14px',
          fontWeight: 500
        }
      }
    },
    yaxis: {
      labels: {
        formatter: function (value) {
          return value.toLocaleString('fr-FR') + ' CFA';
        },
        style: {
          fontSize: '14px',
          fontWeight: 500
        }
      }
    },
    tooltip: {
      style: {
        fontSize: '14px'
      },
      y: {
        formatter: function (val) {
          return val.toLocaleString('fr-FR') + " CFA";
        }
      }
    },
    grid: {
      show: true,
      borderColor: 'rgba(var(--dark),.2)',
      strokeDashArray: 2,
      xaxis: { lines: { show: false } },
      yaxis: { lines: { show: true } }
    }
  };

  var chart = new ApexCharts(document.querySelector("#transaction-report-chart"), options);
  chart.render();
