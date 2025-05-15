<div class="bg-white border border-gray-200 rounded-xl shadow-2xs overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
    <!-- Header Section -->
    <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-neutral-700">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">System Logs</h2>
        <div class="flex space-x-3">
            <button wire:click="exportLogs" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-neutral-800 dark:border-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export Logs
            </button>
            <button wire:click="resetFilters" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-neutral-800 dark:border-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Reset Filters
            </button>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-gray-50 dark:bg-neutral-800 p-4 border-b border-gray-200 dark:border-neutral-700">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Search</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce="search" type="text" id="search" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-neutral-700 dark:border-neutral-600 dark:text-white" placeholder="Search logs...">
                </div>
            </div>

            <!-- Log Type -->
            <div>
                <label for="logType" class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Log Type</label>
                <select wire:model.live="logType" id="logType" class="block w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-neutral-700 dark:border-neutral-600 dark:text-white">
                    <option value="">All Types</option>
                    @foreach($availableLogTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Model Type -->
            <div>
                <label for="modelType" class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Model Type</label>
                <select wire:model.live="modelType" id="modelType" class="block w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-neutral-700 dark:border-neutral-600 dark:text-white">
                    <option value="">All Models</option>
                    @foreach($availableModelTypes as $type)
                        <option value="{{ $type }}">{{ $this->getFormattedModelType($type) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- User -->
            <div>
                <label for="userId" class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">User</label>
                <select wire:model.live="userId" id="userId" class="block w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-neutral-700 dark:border-neutral-600 dark:text-white">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Action -->
            <div>
                <label for="action" class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Action</label>
                <select wire:model.live="action" id="action" class="block w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-neutral-700 dark:border-neutral-600 dark:text-white">
                    <option value="">All Actions</option>
                    @foreach($availableActions as $action)
                        <option value="{{ $action }}">{{ $action }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label for="dateFrom" class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Date From</label>
                <input wire:model.live="dateFrom" type="date" id="dateFrom" class="block w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-neutral-700 dark:border-neutral-600 dark:text-white">
            </div>

            <!-- Date To -->
            <div>
                <label for="dateTo" class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Date To</label>
                <input wire:model.live="dateTo" type="date" id="dateTo" class="block w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-neutral-700 dark:border-neutral-600 dark:text-white">
            </div>

            <!-- Per Page -->
            <div>
                <label for="perPage" class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-1">Per Page</label>
                <select wire:model.live="perPage" id="perPage" class="block w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-neutral-700 dark:border-neutral-600 dark:text-white">
                    <option>15</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
            <thead class="bg-gray-50 dark:bg-neutral-800">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-neutral-400">
                        <div class="flex items-center space-x-1 cursor-pointer" wire:click="sortBy('created_at')">
                            <span>Date & Time</span>
                            @if($sortField === 'created_at')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @endif
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-neutral-400">
                        <div class="flex items-center space-x-1 cursor-pointer" wire:click="sortBy('log_type')">
                            <span>Type</span>
                            @if($sortField === 'log_type')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @endif
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-neutral-400">
                        <div class="flex items-center space-x-1 cursor-pointer" wire:click="sortBy('action')">
                            <span>Action</span>
                            @if($sortField === 'action')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @endif
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-neutral-400">
                        <div class="flex items-center space-x-1 cursor-pointer">
                            <span>Description</span>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-neutral-400">
                        <div class="flex items-center space-x-1 cursor-pointer" wire:click="sortBy('user_id')">
                            <span>User</span>
                            @if($sortField === 'user_id')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @endif
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-neutral-400">
                        <div class="flex items-center space-x-1 cursor-pointer" wire:click="sortBy('ip_address')">
                            <span>IP Address</span>
                            @if($sortField === 'ip_address')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @endif
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-neutral-400">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 dark:bg-neutral-900 dark:divide-neutral-700">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-neutral-800 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                            {{ $log->created_at->format('M d, Y H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                @if($log->log_type == 'info') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                @elseif($log->log_type == 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                @elseif($log->log_type == 'error') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                @elseif($log->log_type == 'success') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                @endif">
                                {{ $log->log_type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                @if($log->action == 'create') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                @elseif($log->action == 'update') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                @elseif($log->action == 'delete') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                @elseif($log->action == 'login') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300
                                @elseif($log->action == 'logout') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                @endif">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white max-w-md truncate">
                            {{ $log->description }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            @if($log->user)
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-gray-200 dark:bg-neutral-700 flex items-center justify-center text-gray-700 dark:text-neutral-300 font-medium text-sm">
                                        {{ substr($log->user->name, 0, 1) }}
                                    </div>
                                    <div class="ml-2">
                                        <div class="text-sm font-medium">{{ $log->user->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-neutral-400">{{ $log->user->email }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="text-gray-500 dark:text-neutral-400">System</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-neutral-400">
                            {{ $log->ip_address }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="viewLogDetails({{ $log->id }})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                View Details
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500 dark:text-neutral-400">
                            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            <p class="mt-2 text-sm font-medium">No logs found</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-neutral-500">Try adjusting your search or filter criteria</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="bg-white dark:bg-neutral-900 px-4 py-3 border-t border-gray-200 dark:border-neutral-700 sm:px-6">
        {{ $logs->links() }}
    </div>

    <!-- Log Details Modal -->
    <div wire:ignore>
        <div id="log-details-modal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full dark:bg-neutral-800">
                    @if($selectedLog)
                        <div class="bg-white dark:bg-neutral-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                        Log Details
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-500 dark:text-neutral-400">Log Type</h4>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedLog->log_type }}</p>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-500 dark:text-neutral-400">Date & Time</h4>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedLog->created_at->format('F d, Y H:i:s') }}</p>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-500 dark:text-neutral-400">User</h4>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                                @if($selectedLog->user)
                                                    {{ $selectedLog->user->name }} ({{ $selectedLog->user->email }})
                                                @else
                                                    System
                                                @endif
                                            </p>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-500 dark:text-neutral-400">IP Address</h4>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedLog->ip_address }}</p>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-500 dark:text-neutral-400">Action</h4>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedLog->action }}</p>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-500 dark:text-neutral-400">Model Type</h4>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedLog->model_type ? $this->getFormattedModelType($selectedLog->model_type) : 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-500 dark:text-neutral-400">Model ID</h4>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedLog->model_id ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-500 dark:text-neutral-400">Description</h4>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedLog->description }}</p>
                                        </div>
                                        @if($selectedLog->properties)
                                            <div>
                                                <h4 class="text-sm font-medium text-gray-500 dark:text-neutral-400">Properties</h4>
                                                <div class="mt-1 bg-gray-50 dark:bg-neutral-700 rounded-md p-3 overflow-auto max-h-64">
                                                    <pre class="text-xs text-gray-900 dark:text-white whitespace-pre-wrap">{{ json_encode($selectedLog->properties, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-neutral-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button wire:click="closeLogDetails" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Close
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', function () {
            Livewire.on('open-modal', (modalId) => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.remove('hidden');
                }
            });

            Livewire.on('close-modal', (modalId) => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('hidden');
                }
            });
        });
    </script>
</div>
