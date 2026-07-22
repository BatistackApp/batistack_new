<x-filament-panels::page>
    <div wire:ignore x-data="productionCalendar()" x-init="initCalendar" class="bg-white dark:bg-gray-900 rounded-xl shadow p-4 border border-gray-200 dark:border-white/10">
        <div id="calendar"></div>
    </div>

    <!-- Scripts FullCalendar CDN -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/fr.global.min.js"></script>

    <style>
        .fc-theme-standard td, .fc-theme-standard th {
            border-color: rgba(156, 163, 175, 0.2) !important;
        }
        .fc-day-today {
            background-color: rgba(59, 130, 246, 0.05) !important;
        }
        .fc-event {
            cursor: pointer;
            border: none;
            padding: 2px 4px;
            border-radius: 4px;
            font-weight: bold;
        }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productionCalendar', () => ({
                calendar: null,
                initCalendar() {
                    let calendarEl = document.getElementById('calendar');
                    let events = @json($this->events);

                    this.calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        locale: 'fr',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,timeGridDay'
                        },
                        events: events,
                        editable: true,
                        eventDrop: function(info) {
                            @this.updateEventDates(info.event.id, info.event.startStr, info.event.endStr);
                        },
                        eventResize: function(info) {
                            @this.updateEventDates(info.event.id, info.event.startStr, info.event.endStr);
                        },
                        eventClick: function(info) {
                            // On empêche la navigation par défaut (car on pourrait vouloir ouvrir une modale)
                            // Mais ici l'URL contient la page d'édition de l'OF, on la laisse s'ouvrir
                            if (info.event.url) {
                                window.location.href = info.event.url;
                                info.jsEvent.preventDefault();
                            }
                        }
                    });

                    this.calendar.render();
                }
            }));
        });
    </script>
</x-filament-panels::page>
