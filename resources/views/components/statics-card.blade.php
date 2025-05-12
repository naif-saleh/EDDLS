{{--
    Dashboard Card Component

    Props:
    - title: Card title (string)
    - count: Total count (number)
    - active: Active count (number)
    - inactive: Inactive count (number)
    - icon: Icon name (string) - Uses heroicon names
    - color: Card accent color (string) - tailwind color name
--}}

@props([
    'title' => '',
    'count' => 0,
    'active' => 0,
    'inactive' => 0,
    'icon' => 'users',
    'color' => 'blue',
])

@php
    $colorClasses = [
        'blue' => [
            'bg' => 'bg-blue-100 dark:bg-blue-900/30',
            'text' => 'text-blue-600 dark:text-blue-400'
        ],
        'green' => [
            'bg' => 'bg-green-100 dark:bg-green-900/30',
            'text' => 'text-green-600 dark:text-green-400'
        ],
        'indigo' => [
            'bg' => 'bg-indigo-100 dark:bg-indigo-900/30',
            'text' => 'text-indigo-600 dark:text-indigo-400'
        ],
        'purple' => [
            'bg' => 'bg-purple-100 dark:bg-purple-900/30',
            'text' => 'text-purple-600 dark:text-purple-400'
        ],
        'orange' => [
            'bg' => 'bg-orange-100 dark:bg-orange-900/30',
            'text' => 'text-orange-600 dark:text-orange-400'
        ],
    ];

    $selectedColor = $colorClasses[$color] ?? $colorClasses['blue'];

    $icons = [
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />',
        'building' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />',
        'campaign' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />',
        'chart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />',
    ];

    $selectedIcon = $icons[$icon] ?? $icons['users'];
@endphp

<div class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-800 overflow-hidden">
    <div class="p-5">
        <div class="flex items-center gap-x-4">
            <div class="{{ $selectedColor['bg'] }} rounded-full p-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="size-6 {{ $selectedColor['text'] }}">
                    {!! $selectedIcon !!}
                </svg>
            </div>

            <div class="grow">
                <p class="text-xs uppercase font-medium text-gray-800 dark:text-neutral-200">
                    {{ $title }}
                </p>
                <h3 class="mt-1 text-2xl font-semibold {{ $selectedColor['text'] }}">
                    {{ $count }}
                </h3>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mt-4">
            <div class="flex items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30 py-2 px-3">
                <svg class="size-4 text-green-600 dark:text-green-400 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Active</p>
                    <p class="text-sm font-semibold text-green-700 dark:text-green-400">{{ $active }}</p>
                </div>
            </div>

            <div class="flex items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30 py-2 px-3">
                <svg class="size-4 text-orange-600 dark:text-orange-400 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Inactive</p>
                    <p class="text-sm font-semibold text-orange-700 dark:text-orange-400">{{ $inactive }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
