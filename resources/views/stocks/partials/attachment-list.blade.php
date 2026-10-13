<div class="receipt-attachment-items">
    @foreach($attachments as $attachment)
        @if(in_array($attachment->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true))
            <a class="receipt-attachment-preview" href="{{ route('stock-control.attachments.preview', $attachment) }}" target="_blank" rel="noopener" aria-label="Afficher {{ $attachment->original_name }}">
                <img src="{{ route('stock-control.attachments.preview', $attachment) }}" alt="{{ $attachment->original_name }}" loading="lazy">
            </a>
        @else
            <a class="receipt-attachment-file" href="{{ route('stock-control.attachments.download', $attachment) }}"><span class="material-icons">attach_file</span>{{ $attachment->original_name }}</a>
        @endif
    @endforeach
</div>
