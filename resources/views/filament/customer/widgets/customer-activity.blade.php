<div class="space-y-1">
    @if (count($activities) > 0)
        @foreach ($activities as $activity)
            <a href="{{ $activity['url'] }}" class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                <div class="mt-0.5 flex-shrink-0">
                    <x-dynamic-component :component="$activity['icon']" class="w-5 h-5 text-{{ $activity['color'] }}-500 dark:text-{{ $activity['color'] }}-400" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $activity['label'] }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ $activity['description'] }}
                    </p>
                </div>
                <div class="flex-shrink-0 text-xs text-gray-400 dark:text-gray-500">
                    {{ $activity['date']->diffForHumans() }}
                </div>
            </a>
        @endforeach
    @else
        <div class="flex items-center justify-center py-8">
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucune activité récente.</p>
        </div>
    @endif
</div>
