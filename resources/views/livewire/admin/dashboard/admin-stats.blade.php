<!-- File: resources/views/livewire/admin/dashboard/admin-stats.blade.php -->

<div>
    <!-- Card Section -->
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        <!-- Grid -->
        <div class="grid sm:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6">
            <!-- Agents Card -->
            

            <!-- Tenants Card -->
            <div
                class="flex flex-col gap-y-3 lg:gap-y-5 p-4 md:p-5 bg-white border border-gray-200 shadow-2xs rounded-xl dark:bg-neutral-900 dark:border-neutral-800">
                <div class="inline-flex justify-center items-center">
                    <span class="size-2 inline-block bg-indigo-500 rounded-full me-2"></span>
                    <span class="text-xs font-semibold uppercase text-gray-600 dark:text-neutral-400">Tenants</span>
                </div>

                <div class="text-center">
                    <h3 class="text-3xl sm:text-4xl lg:text-5xl font-semibold text-gray-800 dark:text-neutral-200">
                        {{ $tenantCount }}
                    </h3>
                </div>

                <dl class="flex justify-center items-center divide-x divide-gray-200 dark:divide-neutral-800">
                    <dt class="pe-3">
                        <span class="text-green-600">
                            <svg class="inline-block size-4 self-center" xmlns="http://www.w3.org/2000/svg" width="16"
                                height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path fill-rule="evenodd"
                                    d="m7.247 4.86-4.796 5.481c-.566.647-.106 1.659.753 1.659h9.592a1 1 0 0 0 .753-1.659l-4.796-5.48a1 1 0 0 0-1.506 0z" />
                            </svg>
                            <span class="inline-block text-sm">
                                {{ rand(1, 3) }}%
                            </span>
                        </span>
                        <span class="block text-sm text-gray-500 dark:text-neutral-500">change</span>
                    </dt>
                    <dd class="text-start ps-3">
                        <span class="text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ rand(1, 3) }}</span>
                        <span class="block text-sm text-gray-500 dark:text-neutral-500">last week</span>
                    </dd>
                </dl>
            </div>

            <!-- Licenses Card -->
            <div
                class="flex flex-col gap-y-3 lg:gap-y-5 p-4 md:p-5 bg-white border border-gray-200 shadow-2xs rounded-xl dark:bg-neutral-900 dark:border-neutral-800">
                <div class="inline-flex justify-center items-center">
                    <span class="size-2 inline-block bg-yellow-500 rounded-full me-2"></span>
                    <span class="text-xs font-semibold uppercase text-gray-600 dark:text-neutral-400">Licenses</span>
                </div>

                <div class="text-center">
                    <div class="flex justify-center items-center space-x-4">
                        <div class="text-center">
                            <h4 class="text-lg font-semibold text-green-600">{{ $licenseStats['active'] }}</h4>
                            <span class="text-xs text-gray-500">Active</span>
                        </div>
                        <div class="text-center">
                            <h4 class="text-lg font-semibold text-gray-600">{{ $licenseStats['inactive'] }}</h4>
                            <span class="text-xs text-gray-500">Inactive</span>
                        </div>
                        <div class="text-center">
                            <h4 class="text-lg font-semibold text-red-600">{{ $licenseStats['expired'] }}</h4>
                            <span class="text-xs text-gray-500">Expired</span>
                        </div>
                    </div>
                </div>

                <dl class="flex justify-center items-center divide-x divide-gray-200 dark:divide-neutral-800">
                    <dt class="pe-3">
                        <span class="text-gray-600">
                            <span class="inline-block text-sm">
                                Total: {{ array_sum($licenseStats) }}
                            </span>
                        </span>
                    </dt>
                </dl>
            </div>
        </div>

        <!-- Chart Card -->
        <div
            class="mt-6 p-4 md:p-5 min-h-102.5 flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl dark:bg-neutral-800 dark:border-neutral-700">
            <!-- Header -->
            <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
                <div>
                    <h2 class="text-xl font-medium text-gray-800 dark:text-neutral-200">
                        Monthly Statistics
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-neutral-500">
                        Growth trends over the past year
                    </p>
                </div>
            </div>
            <!-- End Header -->

            <div id="admin-stats-chart" class="h-80"></div>
        </div>
        <!-- End Chart Card -->
    </div>
    <!-- End Card Section -->

     <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('livewire:load', function () {
            const statsData = monthlyStats;

            const options = {
                series: [
                    {
                        name: 'Agents',
                        data: statsData.map(item => item.agents)
                    },
                    {
                        name: 'Campaigns',
                        data: statsData.map(item => item.campaigns)
                    },
                    {
                        name: 'Tenants',
                        data: statsData.map(item => item.tenants)
                    }
                ],
                chart: {
                    type: 'bar',
                    height: 320,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '16px',
                        borderRadius: 2
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 8,
                    colors: ['transparent']
                },
                xaxis: {
                    categories: statsData.map(item => item.month),
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    },
                    labels: {
                        style: {
                            colors: "#9ca3af",
                            fontSize: "13px",
                            fontFamily: "Inter, ui-sans-serif"
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: "#9ca3af",
                            fontSize: "13px",
                            fontFamily: "Inter, ui-sans-serif"
                        }
                    }
                },
                fill: {
                    opacity: 1
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right'
                },
                colors: ['#3b82f6', '#10b981', '#6366f1'],
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val
                        }
                    }
                }
            };

            const chart = new ApexCharts(document.querySelector("#admin-stats-chart"), options);
            chart.render();

            // Listen for Livewire events to update chart
            Livewire.on('statsUpdated', (data) => {
                chart.updateOptions({
                    series: [
                        {
                            data: data.map(item => item.agents)
                        },
                        {
                            data: data.map(item => item.campaigns)
                        },
                        {
                            data: data.map(item => item.tenants)
                        }
                    ],
                    xaxis: {
                        categories: data.map(item => item.month)
                    }
                });
            });
        });
    </script>
 </div>
