$(function () {
    "use strict";
    var status_filter = $("#status_filter"),
        customer_filter = $("#customer_filter"),
        date_filter = $('#daterange-btn');

    /*var table = $("#bookings").DataTable({
        paging: true,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        dom: "Bfrtip",
        buttons: ["copy", "csv", "excel", "pdf", "print"],
        buttons: [
            {
                extend: 'copy',
                title: 'liste_des_reservations'
            },
            {
                extend: 'csv',
                title: 'liste_des_reservations'
            },
            {
                extend: 'excel',
                title: 'liste_des_reservations'
            },
            {
                extend: 'pdf',
                title: 'liste_des_reservations'
            },
            {
                extend: 'print',
                title: 'liste_des_reservations'
            }
        ],
        language: {
            url: "/js/i18n/datatables/fr-FR.json",
        },
        ajax: {
            url: "/company/bookings/json?type=5&key=users",
            data: function (d) {
                d.status = status_filter.val();
                d.customer_id = customer_filter.val();
                let dateRange = date_filter.data('daterangepicker');
                if (dateRange) {
                    d.date_range = `${dateRange.startDate.format('YYYY-MM-DD')} - ${dateRange.endDate.format('YYYY-MM-DD')}`;
                }
            },
        },
        processing: true,
        serverSide: true,
        columns: [
            { data: "customer_id", name: "customer_id" },
            { data: "number", name: "number" },
            { data: "order_id", name: "order_id" },
            { data: "tables", name: "tables"},
            { data: "seats", name: "seats" },
            { data: "created_at", name: "created_at" },
            { data: "time_range", name: "time_range"},
            { data: "total_price", name: "total_price"},
            { data: "status", name: "status" },
            {
                data: "action",
                name: "action",
                orderable: false,
                searchable: false,
            },
        ],
    });

    $('#status_filter, #customer_filter').on('change keyup', function () {
        table.draw();
    });

    date_filter.on('apply.daterangepicker', function(ev, picker) {
        table.draw();
    });*/

    console.log("daterange", $("#daterange-btn"));

    $("#daterange-btn").daterangepicker(
        {
            ranges: {
                "Aujourd'hui": [moment(), moment()],
                Hier: [
                    moment().subtract(1, "days"),
                    moment().subtract(1, "days"),
                ],
                "7 derniers jours": [moment().subtract(6, "days"), moment()],
                "30 derniers jours": [moment().subtract(29, "days"), moment()],
                "Le mois en cours": [
                    moment().startOf("month"),
                    moment().endOf("month"),
                ],
                "Le mois dernier": [
                    moment().subtract(1, "month").startOf("month"),
                    moment().subtract(1, "month").endOf("month"),
                ],
            },
            startDate: moment().subtract(29, "days"),
            endDate: moment(),
        },
        function (start, end) {
            console.log("date span");
            console.log(start.format("MMMM D, YYYY"), end.format("MMMM D, YYYY"));
            $("#daterange-btn span").html(
                start.format("MMMM D, YYYY") +
                    " - " +
                    end.format("MMMM D, YYYY")
            );
        }
    );
});
// End of use strict

// totalSales chart
options = {
    series: [44, 55, 41, 17, 15],
    chart: {
      fontFamily: 'Montserrat, system-ui',
      height: 320,
      type: 'donut',
      dropShadow: {
        enabled: false,
        color: '#111',
        top: -1,
        left: 3,
        blur: 3,
        opacity: 0.2
      }
    },
    stroke: {
      width: 0,
    },

    legend: {
      position: 'bottom',
      fontSize: '14px',
      // fontFamily: '"Poppins", sans-serif',
      fontWeight: 500,
      labels: {
        colors: 'rgba(var(--secondary),1)',
        useSeriesColors: false
      },
      markers: {
        width: 15,
        height: 15,
        radius: 5,
        offsetX: -4,
      },
    },
    plotOptions: {
      pie: {
        donut: {
          labels: {
            show: false,
            total: {
              showAlways: false,
              show: false
            }
          }
        }
      }
    },
    labels: ["Point A", "Point B", "Point C", "Point D", "Point E"],

    dataLabels: {
    enabled: false,
      dropShadow: {
        blur: 3,
        opacity: 0.8
      }
    },
    colors: ['rgba(var(--primary-dark),1)','rgba(var(--primary),1)','rgba(var(--danger-dark),1)','rgba(var(--danger),.3)','rgba(var(--warning),1)'],
    fill: {
      // type: 'pattern',
      type: ['pattern', 'solid', 'pattern', 'solid', 'solid'],
      opacity: 1,
      pattern: {
        enabled: true,
        style: ['verticalLines', 'horizontalLines', 'horizontalLines', 'circles','horizontalLines'],
      },
    },
    states: {
      hover: {
        filter: 'none'
      }
    },
    theme: {
      palette: 'palette2'
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

  chart = new ApexCharts(document.querySelector("#order-sale-chart"), options);
  chart.render();
