document.addEventListener('DOMContentLoaded', function() {
    let dateRangePicker = $('#dateRangePicker');
    
    if (dateRangePicker.length) {
        dateRangePicker.daterangepicker({
            locale: {
                format: 'YYYY-MM-DD',
                separator: ' à ',
                applyLabel: 'Appliquer',
                cancelLabel: 'Annuler',
                fromLabel: 'Du',
                toLabel: 'Au',
                customRangeLabel: 'Personnalisé',
                daysOfWeek: ['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'],
                monthNames: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
                firstDay: 1
            },
            opens: 'left',
            autoUpdateInput: false
        });

        dateRangePicker.on('apply.daterangepicker', function(ev, picker) {
            let range = picker.startDate.format('YYYY-MM-DD') + ' à ' + picker.endDate.format('YYYY-MM-DD');
            $(this).val(range);
            Livewire.emit('dateRangeSelected', range);
        });

        dateRangePicker.on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            Livewire.emit('dateRangeSelected', '');
        });
    }
});
