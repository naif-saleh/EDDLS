<div id="hs-modal-recover-account"
    class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto"
    role="dialog" tabindex="-1" aria-labelledby="hs-modal-recover-account-label"
    wire:ignore.self>

    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
        <div class="bg-white border border-gray-200 rounded-xl shadow-2xs dark:bg-neutral-900 dark:border-neutral-800">
            <div class="p-4 sm:p-7">
                <div class="text-center">
                    <h3 id="hs-modal-recover-account-label"
                        class="block text-2xl font-bold text-gray-800 dark:text-neutral-200">
                        {{ $editMode ? 'Update Tenant' : 'Create New Tenant' }}
                    </h3>
                </div>

                <div class="mt-5">
                    <!-- Form -->
                    <form wire:submit.prevent="{{ $editMode ? 'updateTenant' : 'createTenant' }}">
                        <div class="grid gap-y-4">
                            <div>
                                <x-input-form :wiremodel='"tenantName"' type='text' :placeholder='"Enter Tenant Name ..."' lable=' Name' />
                                <x-input-form :wiremodel='"tenantEmail"' type='text' :placeholder='"Enter Tenant Email ..."' lable=' Email' />
                                <x-input-form :wiremodel='"tenantPhone"' type='text' :placeholder='"Enter Tenant Phone ..."' lable=' Phone' />

                                <label for="hs-select-label" class="block text-sm font-medium mb-2 dark:text-white">
                                    Status
                                </label>
                                <select id="hs-select-label"
                                    class="py-3 px-4 pe-9 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                                    wire:model='tenantStatus'>
                                    <option value="">Set Tenant Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <x-confirm-button :value='($editMode ? "Update Tenant" : "Create Tenant")'/>
                        </div>
                    </form>
                    <!-- End Form -->
                </div>
            </div>
        </div>
    </div>
</div>
