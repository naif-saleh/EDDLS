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
                        {{ $editMode ? 'Update User' : 'Create New User' }}
                    </h3>
                </div>

                <div class="mt-5">
                    <!-- Form -->
                    <form wire:submit.prevent="{{ $editMode ? 'updateUser' : 'createUser' }}">
                        <div class="grid gap-y-4">
                            <div>
                                <x-input-form :wiremodel='"userName"' type='text' :placeholder='"enter user name ..."' lable='user name' />
                                <x-input-form :wiremodel='"userEmail"' type='email' :placeholder='"enter user email ..."' lable='user email' />
                                <x-input-form :wiremodel='"userPassword"' type='password' :placeholder='"enter user password ..."' lable='user password' />


                            </div>

                            <x-confirm-button :value='($editMode ? "Update User" : "Create User")'/>
                        </div>
                    </form>
                    <!-- End Form -->
                </div>
            </div>
        </div>
    </div>
</div>
