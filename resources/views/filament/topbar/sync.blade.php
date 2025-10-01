{{-- يظهر فقط لمن معه صلاحية "مزامنة البيانات" --}}
@php $canSync = auth()->user()?->can('مزامنة البيانات'); @endphp
@if ($canSync)
    <livewire:topbar.sync-button />
@endif
