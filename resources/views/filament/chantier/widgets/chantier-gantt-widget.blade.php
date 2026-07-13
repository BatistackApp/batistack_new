<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium">Planning de Chantier (Gantt)</h2>
        </div>
        
        <div wire:ignore>
            <style>
                .gantt-container { overflow-x: auto; }
                .bar-milestone .bar { fill: #f97316; }
                .bar-phase .bar { fill: #3b82f6; }
                .bar-task .bar { fill: #94a3b8; }
                .bar-task-completed .bar { fill: #22c55e; }
            </style>
            <svg id="gantt"></svg>
            
            <div x-data="{
                tasks: @js($this->getTasks()),
                init() {
                    if (typeof Gantt === 'undefined') {
                        let link = document.createElement('link');
                        link.rel = 'stylesheet';
                        link.href = 'https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.css';
                        document.head.appendChild(link);
                        
                        let script = document.createElement('script');
                        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.min.js';
                        script.onload = () => {
                            this.renderGantt();
                        };
                        document.head.appendChild(script);
                    } else {
                        this.renderGantt();
                    }
                },
                renderGantt() {
                    if (this.tasks.length === 0) {
                        document.getElementById('gantt').innerHTML = '<text x=\'10\' y=\'20\'>Aucune donnée pour ce chantier.</text>';
                        return;
                    }
                    
                    var gantt = new Gantt('#gantt', this.tasks, {
                        header_height: 50,
                        column_width: 30,
                        step: 24,
                        view_modes: ['Quarter Day', 'Half Day', 'Day', 'Week', 'Month'],
                        bar_height: 20,
                        bar_corner_radius: 3,
                        arrow_curve: 5,
                        padding: 18,
                        view_mode: 'Day',   
                        date_format: 'YYYY-MM-DD',
                        custom_popup_html: function(task) {
                            return `
                                <div class=\'p-2 bg-white shadow rounded border text-sm\'>
                                    <strong>${task.name}</strong><br>
                                    Début: ${task.start}<br>
                                    Fin: ${task.end}<br>
                                    Progression: ${task.progress}%
                                </div>
                            `;
                        }
                    });
                }
            }"></div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
