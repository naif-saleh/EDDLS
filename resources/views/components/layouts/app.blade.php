@php
$sidebar_type = 'sidebar';
if(Auth::user()->isSuperAdmin()){
$sidebar_type = 'admin-sidebar';
}
@endphp

<x-dynamic-component :component="'layouts.app.' . $sidebar_type" :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-dynamic-component>
