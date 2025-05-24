<!-- resources/views/livewire/tenant/employee-form.blade.php -->
<div>
    <!-- Card Section -->
    <div class="max-w-2xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        <!-- Card -->
        <div class="bg-white rounded-xl shadow-xs p-4 sm:p-7 dark:bg-neutral-900">
            <div class="text-center mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-neutral-200">
                    Updated Employee {{ $employeeName }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-neutral-400">
                    Add Your Employee Information
                </p>
            </div>

            <!-- Flash Messages -->
            @if (session()->has('message'))
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg">
                {{ session('message') }}
            </div>
            @endif

            @if (session()->has('error'))
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg">
                {{ session('error') }}
            </div>
            @endif

            <form wire:submit.prevent="updateEmployee">
                <div class="py-6 first:pt-0 last:pb-0 border-t first:border-transparent border-gray-200 dark:border-neutral-700 dark:first:border-transparent">
                    <div class="space-y-4">
                        <!-- Employee Name -->
                        <div>
                            <label for="employeeName" class="block text-sm font-medium text-gray-700 dark:text-neutral-300">
                                Employee Name
                            </label>
                            <input type="text" id="employeeName" wire:model="employeeName"
                                class="p-2 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                placeholder="Enter Employee Name">
                            @error('employeeName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Employee Email -->
                        <div>
                            <label for="employeeEmail" class="block text-sm font-medium text-gray-700 dark:text-neutral-300">
                                Employee Email
                            </label>
                            <input type="email" id="employeeEmail" wire:model="employeeEmail"
                                class="p-2 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                placeholder="Enter Employee Email">
                            @error('employeeEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Employee Password -->
                        <div>
                            <label for="employeePassword" class="block text-sm font-medium text-gray-700 dark:text-neutral-300">
                                Employee Password
                            </label>
                            <input type="password" id="employeePassword" wire:model="employeePassword"
                                class="p-2 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                placeholder="Enter Employee Password">
                            @error('employeePassword') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- <!-- Employee Phone -->
                        <div>
                            <label for="employeePhone" class="block text-sm font-medium text-gray-700 dark:text-neutral-300">
                                Employee Phone
                            </label>
                            <input type="text" id="employeePhone" wire:model="employeePhone"
                                class="p-2 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white"
                                placeholder="Enter Employee Phone">
                            @error('employeePhone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div> --}}

                        <!-- Employee Role -->
                        <div>
                            <label for="employeeRole" class="block text-sm font-medium text-gray-700 dark:text-neutral-300">
                                Employee Role
                            </label>
                            <select id="employeeRole" wire:model="employeeRole"
                                class="p-2 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white">
                                <option value="">Select Role</option>
                                <option value="tenant_admin">Tenant Admin</option>
                                <option value="agent">Agent</option>
                            </select>
                            @error('employeeRole') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex justify-end gap-x-2">
                    <button type="submit"
                        class="py-2 px-4 bg-gray-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:bg-gray-700 dark:hover:bg-gray-800 cursor-pointer">
                        Update Employee
                    </button>
                </div>
            </form>
        </div>
        <!-- End Card -->
    </div>
    <!-- End Card Section -->
</div>
