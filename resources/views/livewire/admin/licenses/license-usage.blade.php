<div>
    <div>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">License Management</h2>
            </div>

            <!-- License Status Card -->
            <div class="p-6">
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-700">License Status</h3>
                        <span class="px-3 py-1 rounded-full text-sm font-medium {{ $this->getLicenseStatusClass() }}">
                            {{ $this->getLicenseStatusText() }}
                        </span>
                    </div>

                    @if($licenseStatus['has_license'])
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="text-sm text-blue-600 mb-1">License Type</div>
                                <div class="font-semibold text-gray-800">{{ $licenseStatus['license_type'] ?? 'Standard' }}</div>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <div class="text-sm text-purple-600 mb-1">Expiration Date</div>
                                <div class="font-semibold text-gray-800">{{ $licenseStatus['expiry_date'] ?? 'N/A' }}</div>
                            </div>
                            <div class="bg-emerald-50 p-4 rounded-lg">
                                <div class="text-sm text-emerald-600 mb-1">Days Remaining</div>
                                <div class="font-semibold text-gray-800">{{ $licenseStatus['days_remaining'] ?? 'N/A' }}</div>
                            </div>
                        </div>
                    @else
                        <div class="bg-gray-50 rounded-lg p-6 text-center border border-dashed border-gray-300">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <h3 class="text-gray-700 font-medium mb-2">No Active License</h3>
                            <p class="text-gray-500 text-sm">Activate a license to unlock all features</p>
                        </div>
                    @endif
                </div>

                <!-- License Usage Stats -->
                @if($licenseStatus['has_license'] && $licenseStatus['is_valid'])
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-700 mb-4">License Usage</h3>

                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                            <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y md:divide-y-0 divide-gray-200">
                                @foreach($licenseUsage as $key => $usage)
                                    <div class="p-4">
                                        <div class="text-sm text-gray-500 mb-1">{{ ucwords(str_replace('_', ' ', $key)) }}</div>
                                        <div class="flex items-end">
                                            <div class="text-xl font-semibold text-gray-800">{{ $usage['used'] }}</div>
                                            <div class="text-sm text-gray-500 ml-1">/ {{ $usage['limit'] }}</div>
                                        </div>

                                        <!-- Progress bar -->
                                        @php
                                            $percentage = $usage['limit'] > 0 ? min(100, ($usage['used'] / $usage['limit']) * 100) : 0;
                                            $colorClass = $percentage > 90 ? 'bg-red-500' : ($percentage > 70 ? 'bg-yellow-500' : 'bg-green-500');
                                        @endphp

                                        <div class="w-full h-2 bg-gray-200 rounded-full mt-2">
                                            <div class="h-2 rounded-full {{ $colorClass }}" style="width: {{ $percentage }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- License Activation Form -->
                <div>
                    <h3 class="text-lg font-medium text-gray-700 mb-4">
                        @if($licenseStatus['has_license'] && $licenseStatus['is_valid'])
                            Update License
                        @else
                            Activate License
                        @endif
                    </h3>

                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <form wire:submit.prevent="activateLicense" class="space-y-4">
                            <div>
                                <label for="licenseKey" class="block text-sm font-medium text-gray-700 mb-1">License Key</label>
                                <div class="flex">
                                    <input
                                        type="text"
                                        id="licenseKey"
                                        wire:model.defer="licenseKey"
                                        placeholder="Enter your license key"
                                        class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        :disabled="isActivating"
                                    >
                                    <button
                                        type="submit"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-r-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                                        :disabled="isActivating"
                                    >
                                        <span wire:loading.remove wire:target="activateLicense">
                                            Activate
                                        </span>
                                        <span wire:loading wire:target="activateLicense">
                                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Active Licenses Table -->
                @if($licenseStatus['has_license'] && $licenseStatus['is_valid'])
                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-700 mb-4">Active Licenses</h3>

                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                License ID
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Type
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Expiration
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($licenseStatus['licenses'] ?? [['id' => 1, 'type' => 'Standard', 'status' => 'Active', 'expiry' => $licenseStatus['expiry_date']]] as $license)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ $license['id'] }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $license['type'] }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        {{ $license['status'] ?? 'Active' }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $license['expiry'] }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                                    <button
                                                        wire:click="deactivateLicense('{{ $license['id'] }}')"
                                                        class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                                    >
                                                        Deactivate
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
