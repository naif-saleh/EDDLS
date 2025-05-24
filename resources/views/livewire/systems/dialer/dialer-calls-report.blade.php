<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header with Export Button -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Dialer Call Logs Report</h2>
            <div class="flex items-center space-x-3">
                <button wire:click="resetFilters" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl shadow-2xs text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset Filters
                </button>
                <button wire:click="exportCallLogs" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl shadow-2xs text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Report
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            @foreach(['total', 'answered', 'unanswered'] as $stat)
                <div class="bg-white border border-gray-200 rounded-xl shadow-2xs overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 p-3 rounded-lg 
                                @if($statistics[$stat]['color'] === 'blue') bg-blue-100 dark:bg-blue-900
                                @elseif($statistics[$stat]['color'] === 'green') bg-green-100 dark:bg-green-900
                                @else bg-red-100 dark:bg-red-900
                                @endif">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8
                                    @if($statistics[$stat]['color'] === 'blue') text-blue-600 dark:text-blue-300
                                    @elseif($statistics[$stat]['color'] === 'green') text-green-600 dark:text-green-300
                                    @else text-red-600 dark:text-red-300
                                    @endif" 
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $statistics[$stat]['icon'] }}" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-neutral-400 truncate">
                                        {{ $statistics[$stat]['label'] }}
                                    </dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                            {{ number_format($statistics[$stat]['count']) }}
                                        </div>
                                        @if($stat !== 'total')
                                            <div class="ml-2 flex items-baseline text-sm font-semibold
                                                @if($stat === 'answered') text-green-600 dark:text-green-300
                                                @else text-red-600 dark:text-red-300
                                                @endif">
                                                {{ number_format(($statistics[$stat]['count'] / ($statistics['total']['count'] ?: 1)) * 100, 1) }}%
                                            </div>
                                        @endif
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Filters Section -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-2xs overflow-hidden dark:bg-neutral-900 dark:border-neutral-700 mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300">Search</label>
                        <input type="text" wire:model.live="search" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-neutral-800 dark:border-neutral-600 dark:text-white" placeholder="Search phone or provider...">
                    </div>

                    <!-- Provider Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300">Provider</label>
                        <select wire:model.live="provider" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-neutral-800 dark:border-neutral-600 dark:text-white">
                            <option value="">All Providers</option>
                            @foreach($providers as $provider)
                                <option value="{{ $provider }}">{{ $provider }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Campaign Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300">Campaign</label>
                        <select wire:model.live="campaign" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-neutral-800 dark:border-neutral-600 dark:text-white">
                            <option value="">All Campaigns</option>
                            @foreach($campaigns as $campaign)
                                <option value="{{ $campaign }}">{{ $campaign }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300">Call Status</label>
                        <select wire:model.live="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-neutral-800 dark:border-neutral-600 dark:text-white">
                            <option value="">All Statuses</option>
                            @foreach($statuses as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300">From Date</label>
                        <input type="date" wire:model.live="date_from" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-neutral-800 dark:border-neutral-600 dark:text-white">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300">To Date</label>
                        <input type="date" wire:model.live="date_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-neutral-800 dark:border-neutral-600 dark:text-white">
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-2xs overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                    <thead class="bg-gray-50 dark:bg-neutral-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Date Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Provider</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Campaign</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Phone Number</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Talk Duration</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Dial Duration</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Call At</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">End At</th>                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-neutral-900 dark:divide-neutral-700">
                        @forelse($callLogs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-neutral-800">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ $log->created_at ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ $log->provider ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ $log->campaign ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ $log->phone_number ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $reportService->getCallStatus($log->call_status) === 'Answered' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                        {{ $reportService->getCallStatus($log->call_status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ $log->talking_duration ?? '00:00:00' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ $log->dialing_duration ?? '00:00:00' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ $log->call_at ? \Carbon\Carbon::parse($log->call_at)->format('H:i:s') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ $log->updated_at ? \Carbon\Carbon::parse($log->updated_at)->format('H:i:s') : 'N/A' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-neutral-400">
                                    No call logs found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-white px-4 py-3 border-t border-gray-200 dark:bg-neutral-900 dark:border-neutral-700 sm:px-6">
                {{ $callLogs->links() }}
            </div>
        </div>
    </div>
</div>