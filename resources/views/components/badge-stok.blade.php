@props(['status'])
<span class="badge text-bg-{{ $status->warna() }}">{{ $status->label() }}</span>
