<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header with Export Button -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Distributor Call Logs Report</h2>
            <div class="flex items-center space-x-3">
                <button wire:click="resetFilters" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl shadow-2xs text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset Filters
                </button>
                <button wire:click="export" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl shadow-2xs text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Report
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <!-- Total Calls -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-2xs overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 p-3 rounded-lg bg-blue-100 dark:bg-blue-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-neutral-400 truncate">
                                    Total Calls
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($statistics['total_calls']) }}
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Answered Calls -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-2xs overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 p-3 rounded-lg bg-green-100 dark:bg-green-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600 dark:text-green-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-neutral-400 truncate">
                                    Answered Calls
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($statistics['answered']) }}
                                    </div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600 dark:text-green-300">
                                        {{ number_format(($statistics['answered'] / ($statistics['total_calls'] ?: 1)) * 100, 1) }}%
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unanswered Calls -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-2xs overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 p-3 rounded-lg bg-red-100 dark:bg-red-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600 dark:text-red-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-neutral-400 truncate">
                                    Unanswered Calls
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($statistics['unanswered']) }}
                                    </div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold text-red-600 dark:text-red-300">
                                        {{ number_format(($statistics['unanswered'] / ($statistics['total_calls'] ?: 1)) * 100, 1) }}%
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agent Unanswered -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-2xs overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 p-3 rounded-lg bg-yellow-100 dark:bg-yellow-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-600 dark:text-yellow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-neutral-400 truncate">
                                    Agent Unanswered
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($statistics['agent_unanswered']) }}
                                    </div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold text-yellow-600 dark:text-yellow-300">
                                        {{ number_format(($statistics['agent_unanswered'] / ($statistics['total_calls'] ?: 1)) * 100, 1) }}%
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-2xs overflow-hidden dark:bg-neutral-900 dark:border-neutral-700 mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300">Search</label>
                        <input type="text" wire:model.live.debounce.300ms="searchTerm" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-neutral-800 dark:border-neutral-600 dark:text-white" placeholder="Search...">
                    </div>

                    <!-- Campaign Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300">Campaign</label>
                        <select wire:model.live="selectedCampaign" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-neutral-800 dark:border-neutral-600 dark:text-white">
                            <option value="">All Campaigns</option>
                            @foreach($campaigns as $campaign)
                                <option value="{{ $campaign }}">{{ $campaign }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Agent Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300">Agent</label>
                        <select wire:model.live="selectedAgent" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-neutral-800 dark:border-neutral-600 dark:text-white">
                            <option value="">All Agents</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent }}">{{ $agent }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Provider Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300">Provider</label>
                        <select wire:model.live="selectedProvider" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-neutral-800 dark:border-neutral-600 dark:text-white">
                            <option value="">All Providers</option>
                            @foreach($providers as $provider)
                                <option value="{{ $provider }}">{{ $provider }}</option>
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

                    <!-- Start Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300">Start Date</label>
                        <input type="date" wire:model.live="startDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-neutral-800 dark:border-neutral-600 dark:text-white">
                    </div>

                    <!-- End Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300">End Date</label>
                        <input type="date" wire:model.live="endDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-neutral-800 dark:border-neutral-600 dark:text-white">
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
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider cursor-pointer" wire:click="sortBy('date_time')">
                                Date/Time
                                @if($sortField === 'date_time')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Campaign</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Agent</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Provider</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Mobile</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Dialing</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Talking</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Called</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Ended</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-neutral-900 dark:divide-neutral-700">
                        @forelse($calls as $call)
                            <tr class="hover:bg-gray-50 dark:hover:bg-neutral-800">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ \Carbon\Carbon::parse($call->date_time)->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ $call->campaign ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ $call->agent ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ $call->provider ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ $call->phone_number ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ $call->call_status === 'Talking' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' :
                                           ($call->call_status === 'Initiating' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' :
                                           'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300') }}">
                                        {{ $reportService->getCallStatus($call->call_status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ $call->dial_duration ?? '00:00:00' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ $call->talking_duration ?? '00:00:00' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ \Carbon\Carbon::parse($call->date_time)->format('H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                                    {{ \Carbon\Carbon::parse($call->end_time)->format('H:i:s') ?? 'N/A' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-4 text-center text-gray-500 dark:text-neutral-400">
                                    No calls found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-white px-4 py-3 border-t border-gray-200 dark:bg-neutral-900 dark:border-neutral-700 sm:px-6">
                {{ $calls->links() }}
            </div>
        </div>
    </div>
</div>
