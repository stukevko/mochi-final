import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';
import deLocale from '@fullcalendar/core/locales/de.js';

import '../css/mochi-fullcalendar.css';

function pickInitialView() {
    return typeof window !== 'undefined' && window.matchMedia('(max-width: 767px)').matches
        ? 'listMonth'
        : 'dayGridMonth';
}

function initMochiEventCalendar() {
    const el = document.getElementById('mochi-event-calendar');
    if (!el) {
        return;
    }

    const feedUrl = el.dataset.feedUrl;
    if (!feedUrl) {
        return;
    }

    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, listPlugin],
        locale: deLocale,
        firstDay: 1,
        initialView: pickInitialView(),
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listMonth',
        },
        buttonText: {
            today: 'Heute',
            month: 'Monat',
            list: 'Liste',
        },
        events: feedUrl,
        displayEventTime: true,
        displayEventEnd: false,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        },
        height: 'auto',
        dayMaxEvents: 4,
        navLinks: true,
        eventDisplay: 'block',
        eventDidMount(info) {
            const viewType = info.view.type;
            if (!viewType.startsWith('list')) {
                return;
            }
            const start = info.event.start;
            if (!start) {
                return;
            }
            const pad = (n) => String(n).padStart(2, '0');
            const timeStr = `${pad(start.getHours())}:${pad(start.getMinutes())}`;
            const titleEl = info.el.querySelector('.fc-list-event-title');
            if (titleEl && !titleEl.dataset.mochiTimeMerged) {
                titleEl.textContent = `${timeStr} · ${info.event.title}`;
                titleEl.dataset.mochiTimeMerged = '1';
            }
        },
        eventClick(info) {
            if (info.event.url) {
                info.jsEvent.preventDefault();
                window.location.assign(info.event.url);
            }
        },
    });

    calendar.render();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMochiEventCalendar);
} else {
    initMochiEventCalendar();
}
