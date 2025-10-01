@php
    use App\Filament\Resources\NotificationResource;

    $user   = filament()->auth()->user() ?? auth()->user();
    $unread = $user?->unreadNotifications()->count() ?? 0;
    $items  = $user
        ? $user->notifications()->latest()->limit(10)->get()
        : collect();
@endphp

<x-filament::dropdown placement="bottom-end" shift teleport class="fi-topbar-item">
    <x-slot name="trigger">
        <x-filament::icon-button
            icon="heroicon-o-bell"
            label="الإشعارات"
            :badge="$unread ?: null"
            color="gray"
        />
    </x-slot>

    <div class="w-80">
        <div class="flex items-center justify-between px-3 py-2 border-b dark:border-white/10">
            <div class="font-medium">الإشعارات</div>
            <a href="{{ NotificationResource::getUrl() }}"
               class="text-xs text-primary-600 hover:underline">
                فتح الكل
            </a>
        </div>

        <ul class="max-h-96 overflow-auto divide-y dark:divide-white/10">
            @forelse($items as $n)
                @php
                    $title = data_get($n, 'data.title', 'إشعار');
                    $body  = data_get($n, 'data.body');
                @endphp

                <li class="p-3">
                    <div class="text-sm font-medium">{{ $title }}</div>
                    @if($body)
                        <div class="text-xs opacity-75 mt-0.5">{{ $body }}</div>
                    @endif
                    <div class="text-[11px] opacity-60 mt-1">
                        {{ $n->created_at->diffForHumans() }}
                    </div>
                </li>
            @empty
                <li class="p-4 text-sm text-center opacity-60">لا توجد إشعارات</li>
            @endforelse
        </ul>
    </div>
</x-filament::dropdown>
