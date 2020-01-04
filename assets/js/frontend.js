(function($){

    $(document).ready(function() {

        $('#fullcalendar').fullCalendar({
            height: 'auto',
            contentHeight: 'auto',
            editable: false,
            selectable: false,
            header: {
                left: 'basicWeek,basicDay',
                center: 'title',
                right: 'today prev,next'
            },
            titleFormat: {
                week: 'D MMMM, YYYY', 
                day: 'dddd D MMMM, YYYY'
            },
            columnFormat: {
                week: 'ddd D',
                day: 'dddd D'
            },
            defaultView: 'basicWeek',
            weekMode: 'variable',
            firstDay: 1,
            buttonText: {
                today: RSECAjax.locale.today,
                month: RSECAjax.locale.month,
                week: RSECAjax.locale.week,
                day: RSECAjax.locale.day,
            },
            monthNames: RSECAjax.locale.monthNames,
            monthNamesShort: RSECAjax.locale.monthAbbrev,
            dayNames: RSECAjax.locale.dayNames,
            dayNamesShort: RSECAjax.locale.dayAbbrev,
            lazyFetching: 'true',
            eventTextColor: '#fff',
            allDayDefault: true,
            events: function(start, end, timezone, callback) {
                jQuery.ajax({
                    url: RSECAjax.ajaxurl + '?action=rsec-fullcal',
                    dataType: 'JSON',
                    data: {
                        start: moment(start).format('YYYY-MM-DD'),
                        end: moment(end).format('YYYY-MM-DD')
                    },
                    success: function(data) {
                        callback(data);
                    }
                })
            },
            loading: function(bool) {
                loading = $('#fullcalendar_loading');
                if (bool){
                    loadingTimeOut = window.setTimeout(function(){
                                            loading.show();    
                                        }, 100);
                } else { 
                    window.clearTimeout(loadingTimeOut);
                    loading.hide();
                }
            }
        });
        
    });

})(jQuery);
