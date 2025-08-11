// basic chart //

var options = {
    series: [{
      name: 'Entrées',
      data: [76, 85, 101, 98, 87, 105, 91, 114, 94]
    }, {
      name: 'Sorties',
      data: [44, 55, 57, 56, 61, 58, 63, 60, 66]
    }, {
      name: 'Stock Net',
      data: [32, 30, 44, 42, 26, 47, 28, 54, 28]
    }],
    chart: {
      fontFamily: 'Montserrat, system-ui',
      type: 'bar',
      height: 350
    },
    colors: ['rgba(var(--primary),.8)', 'rgba(var(--warning),.8)', 'rgba(var(--success),.8)'],
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
    xaxis: {
      categories: ['Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct'],
      labels: {
        style: {
          fontSize: '14px',
          fontWeight: 500,
        },
      },
    },
    yaxis: {
      labels: {
        style: {
          fontSize: '14px',
          fontWeight: 500,
        },
      },
    },
    fill: {
      opacity: 1
    },
    grid: {
      show: true,
      borderColor: 'rgba(var(--dark),.2)',
      strokeDashArray: 2,
    },
    tooltip: {
      y: {
        formatter: function (val) {
          return " FCFA " + val + " milliers"
        },
      }
    },
    tooltip: {
      x: {
        show: false,
      },
      style: {
        fontSize: '16px',
      },
    },
  };

  var chart = new ApexCharts(document.querySelector("#basic-colum"), options);
  chart.render();
