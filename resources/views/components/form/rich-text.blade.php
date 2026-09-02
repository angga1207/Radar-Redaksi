@props(['id', 'property', 'value' => '', 'allowImages' => false])

<div wire:ignore>
    <input id="{{ $id }}-input" type="hidden" value="{{ $value }}">
    <trix-editor id="{{ $id }}" input="{{ $id }}-input" data-rich-text data-livewire-property="{{ $property }}" @if($allowImages) data-image-upload-url="{{ route('admin.articles.body-images.store') }}" @endif class="trix-content"></trix-editor>
    @if($allowImages)<p data-image-upload-status class="mt-2 hidden text-sm" role="status" aria-live="polite"></p>@endif
</div>
