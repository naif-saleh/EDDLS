@props(['placeholder' => '', 'wiremodel'=> '', 'type' => '', 'lable' => ''])

<label for="email" class="block text-sm mb-2 dark:text-white">{{ $lable }}</label>
<div class="relative">
  <input type='{{ $type }}' id="email" wire:model='{{ $wiremodel }}' class="py-2.5 sm:py-3 px-4 block w-full border-gray-200 rounded-lg sm:text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-gray-200 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600" placeholder='{{ $placeholder }}' aria-describedby="email-error">
  <div class="hidden absolute inset-y-0 end-0 pointer-events-none pe-3">
    <svg class="size-5 text-red-500" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
    </svg>
  </div>
</div>
