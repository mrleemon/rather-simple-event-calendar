(function () {

	document.addEventListener('DOMContentLoaded', function () {

		var calendarEl = document.getElementById('fullcalendar');
		if (calendarEl) {
			var calendar = new FullCalendar.Calendar(calendarEl, {
				locale: RSECAjax.locale.lang,
				height: 'auto',
				contentHeight: 'auto',
				editable: false,
				selectable: false,
				headerToolbar: {
					left: (window.innerWidth >= 768) ? 'dayGridWeek,dayGridDay' : 'title',
					center: (window.innerWidth >= 768) ? 'title' : '',
					right: (window.innerWidth >= 768) ? 'today prev,next' : 'prev,next'
				},
				views: {
					dayGrid: {
						titleFormat: { year: 'numeric', month: 'long', day: 'numeric' },
						dayHeaderFormat: { weekday: 'short', day: 'numeric' }
					},
					week: {
						titleFormat: { year: 'numeric', month: 'long', day: 'numeric' },
						dayHeaderFormat: { weekday: 'long', day: 'numeric' }
					}
				},
				initialView: (window.innerWidth >= 768) ? 'dayGridWeek' : 'dayGridDay',
				fixedWeekCount: false,
				firstDay: 1,
				lazyFetching: 'true',
				eventTextColor: '#fff',
				defaultAllDay: true,
				events: function (info, successCallback, failureCallback) {
					jQuery.ajax({
						url: RSECAjax.ajaxurl + '?action=rsec-fullcal',
						dataType: 'JSON',
						data: {
							start: FullCalendar.formatDate(info.start.valueOf(), { year: 'numeric', month: '2-digit', day: '2-digit' }),
							end: FullCalendar.formatDate(info.end.valueOf(), { year: 'numeric', month: '2-digit', day: '2-digit' })
						},
						success: function (data) {
							successCallback(data);
						}
					})
				}
			});
			calendar.render();
		}

	});

})();