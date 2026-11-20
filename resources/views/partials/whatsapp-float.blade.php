@if ($whatsapp->isConfigured())
    <a href="{{ $whatsapp->generalLink() }}" class="wa-float d-none d-lg-flex" target="_blank" rel="noopener"
       aria-label="Chat with us on WhatsApp">
        <i class="bi bi-whatsapp" aria-hidden="true"></i>
    </a>
@endif
