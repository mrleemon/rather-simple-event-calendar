document.addEventListener( 'DOMContentLoaded', function() {

    var calendarEl = document.getElementById( 'fullcalendar' );
    var calendar = new FullCalendar.Calendar( calendarEl, {
        locale: RSECAjax.locale.lang,
        height: 'auto',
        contentHeight: 'auto',
        editable: false,
        selectable: false,
        headerToolbar: {
            left: 'basicWeek,basicDay',
            center: 'title',
            right: 'today prev,next'
        },
        views: {
            dayGrid: {
                //'dddd D MMMM, YYYY'
                titleFormat: { year: 'numeric', month: 'long', day: 'numeric' },
                dayHeaderFormat: { weekday: 'long' }
            },
            week: {
                //week: 'ddd D',
                //day: 'dddd D'
                titleFormat: { year: 'numeric', month: 'long', day: 'numeric' },
                dayHeaderFormat: { weekday: 'long' }
            }
        },
        initialView: 'dayGridWeek',
        fixedWeekCount: false,
        firstDay: 1,
        buttonText: {
            today: RSECAjax.locale.today,
            month: RSECAjax.locale.month,
            week: RSECAjax.locale.week,
            day: RSECAjax.locale.day,
        },
        lazyFetching: 'true',
        eventTextColor: '#fff',
        defaultAllDay: true,
        events: function( info, successCallback, failureCallback ) {
            jQuery.ajax({
                url: RSECAjax.ajaxurl + '?action=rsec-fullcal',
                dataType: 'JSON',
                data: {
                    start: FullCalendar.formatDate( info.start.valueOf(), { year: 'numeric', month: '2-digit', day: '2-digit' } ),
                    end: FullCalendar.formatDate( info.end.valueOf(), { year: 'numeric', month: '2-digit', day: '2-digit' } )
                },
                success: function( data ) {
                    successCallback( data );
                }
            })
        }
    });
    calendar.render();

});
