/**
 * Der Kalender (FullCalendar, ~150 KB) wird nur geladen, wenn die Seite wirklich einen Kalender zeigt.
 */
function loadCalendarIfNeeded() {
    if (!document.getElementById('mochi-event-calendar')) {
        return;
    }

    import('./mochi-calendar-view').then(({ initMochiEventCalendar }) => initMochiEventCalendar());
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadCalendarIfNeeded);
} else {
    loadCalendarIfNeeded();
}
