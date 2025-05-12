{{-- Campaign Chart Blade Template --}}
<div class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-800 overflow-hidden">
    <div class="p-5">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-neutral-200 mb-4">
            Campaign Type Analysis
        </h3>

        {{-- Tabs --}}
        <div class="border-b border-gray-200 dark:border-neutral-700">
            <nav class="flex space-x-6" aria-label="Tabs">
                <a href="#"
                   class="py-2 px-1 text-sm font-medium text-blue-600 border-b-2 border-blue-600 dark:text-blue-400 dark:border-blue-400"
                   aria-current="page">
                    Distribution
                </a>
                <a href="#"
                   class="py-2 px-1 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:border-gray-300 dark:text-neutral-400 dark:hover:border-neutral-600">
                    Status
                </a>
                <a href="#"
                   class="py-2 px-1 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:border-gray-300 dark:text-neutral-400 dark:hover:border-neutral-600">
                    Completion
                </a>
            </nav>
        </div>

        {{-- Chart Area --}}
        <div class="mt-4">
            {{-- Distribution Chart (Pie Chart Visualization using CSS) --}}
            <div>
                <div class="flex justify-center mb-4">
                    <div class="relative w-44 h-44">
                        @php
                            $totalCampaigns = $campaignData['dialer']['total'] + $campaignData['distributor']['total'];
                            $dialerPercentage = $totalCampaigns > 0 ? ($campaignData['dialer']['total'] / $totalCampaigns) * 100 : 0;
                            // Calculate pie chart angles
                            $dialerDegrees = 3.6 * $dialerPercentage;
                        @endphp

                        {{-- Circle background --}}
                        <div class="absolute inset-0 rounded-full bg-gray-200 dark:bg-neutral-700"></div>

                        {{-- Dialer portion (blue) --}}
                        <div class="absolute inset-0 rounded-full bg-blue-500 dark:bg-blue-600"
                             style="clip-path: polygon(50% 50%, 50% 0%, {{ 50 + 50 * cos(deg2rad($dialerDegrees)) }}% {{ 50 - 50 * sin(deg2rad($dialerDegrees)) }}%, 50% 50%)"></div>

                        {{-- Distributor portion (indigo) --}}
                        <div class="absolute inset-0 rounded-full bg-indigo-500 dark:bg-indigo-600"
                             style="clip-path: polygon(50% 50%, {{ 50 + 50 * cos(deg2rad($dialerDegrees)) }}% {{ 50 - 50 * sin(deg2rad($dialerDegrees)) }}%, 100% 0%, 100% 100%, 0% 100%, 0% 0%, 50% 0%, 50% 50%)"></div>

                        {{-- Center circle (for donut effect) --}}
                        <div class="absolute inset-0 m-4 rounded-full bg-white dark:bg-neutral-900"></div>

                        {{-- Total count in center --}}
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-lg font-bold text-gray-800 dark:text-neutral-200">{{ $totalCampaigns }}</span>
                        </div>
                    </div>
                </div>

                {{-- Legend --}}
                <div class="flex justify-center gap-x-6">
                    <div class="flex items-center">
                        <span class="w-3 h-3 rounded-full bg-blue-500 dark:bg-blue-600 mr-2"></span>
                        <span class="text-sm text-gray-700 dark:text-neutral-300">Dialer ({{ $campaignData['dialer']['total'] }})</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-3 h-3 rounded-full bg-indigo-500 dark:bg-indigo-600 mr-2"></span>
                        <span class="text-sm text-gray-700 dark:text-neutral-300">Distributor ({{ $campaignData['distributor']['total'] }})</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detailed Comparison Table --}}
        <div class="mt-6">
            <h4 class="text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">
                Detailed Comparison
            </h4>

            <div class="overflow-hidden bg-white dark:bg-neutral-900 shadow-sm rounded-lg border border-gray-200 dark:border-neutral-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                    <thead class="bg-gray-50 dark:bg-neutral-800">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-400 uppercase tracking-wider">
                                Metric
                            </th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-blue-500 dark:text-blue-400 uppercase tracking-wider">
                                Dialer
                            </th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-indigo-500 dark:text-indigo-400 uppercase tracking-wider">
                                Distributor
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-neutral-900 divide-y divide-gray-200 dark:divide-neutral-700">
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-neutral-300">
                                Total
                            </td>
                            <td class="px-4 py-2 text-center text-sm font-medium text-gray-800 dark:text-neutral-200">
                                {{ $campaignData['dialer']['total'] }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm font-medium text-gray-800 dark:text-neutral-200">
                                {{ $campaignData['distributor']['total'] }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-neutral-300">
                                Active
                            </td>
                            <td class="px-4 py-2 text-center text-sm font-medium text-green-600 dark:text-green-400">
                                {{ $campaignData['dialer']['active'] }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm font-medium text-green-600 dark:text-green-400">
                                {{ $campaignData['distributor']['active'] }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-neutral-300">
                                Inactive
                            </td>
                            <td class="px-4 py-2 text-center text-sm font-medium text-orange-600 dark:text-orange-400">
                                {{ $campaignData['dialer']['inactive'] }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm font-medium text-orange-600 dark:text-orange-400">
                                {{ $campaignData['distributor']['inactive'] }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-neutral-300">
                                Completed
                            </td>
                            <td class="px-4 py-2 text-center text-sm font-medium text-gray-800 dark:text-neutral-200">
                                {{ $campaignData['dialer']['completed'] }}
                            </td>
                            <td class="px-4 py-2 text-center text-sm font-medium text-gray-800 dark:text-neutral-200">
                                {{ $campaignData['distributor']['completed'] }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Completion Rate Bars --}}
        <div class="mt-6">
            <h4 class="text-sm font-medium text-gray-700 dark:text-neutral-300 mb-3">
                Completion Rate
            </h4>

            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-blue-600 dark:text-blue-400 font-medium">Dialer</span>
                        <span class="text-gray-700 dark:text-neutral-300">{{ $campaignData['dialer']['completionRate'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-neutral-700">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $campaignData['dialer']['completionRate'] }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-indigo-600 dark:text-indigo-400 font-medium">Distributor</span>
                        <span class="text-gray-700 dark:text-neutral-300">{{ $campaignData['distributor']['completionRate'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-neutral-700">
                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $campaignData['distributor']['completionRate'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
