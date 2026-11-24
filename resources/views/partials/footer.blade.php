<footer class="site-footer">
    <div class="container">
        <div class="row gx-4 gy-5">
            <div class="col-lg-4">
                <x-brand-logo variant="light" />
                @if ($company->about())
                    <p class="site-footer__about">{{ \Illuminate\Support\Str::limit($company->about(), 220) }}</p>
                @endif
                @if (count($company->socialLinks()))
                    <ul class="social-list" aria-label="Follow us">
                        @foreach ($company->socialLinks() as $link)
                            <li>
                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener" aria-label="{{ $link['label'] }}">
                                    <i class="bi {{ $link['icon'] }}" aria-hidden="true"></i>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="col-6 col-lg-2">
                <h2 class="site-footer__heading">Shop</h2>
                <ul class="site-footer__links">
                    <li><a href="{{ route('shop.index') }}">All chocolates</a></li>
                    @foreach ($navCategories as $navCategory)
                        <li><a href="{{ route('shop.index', $navCategory->slug) }}">{{ $navCategory->name }}</a></li>
                    @endforeach
                    <li><a href="{{ route('custom-orders.create') }}">Custom orders</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-2">
                <h2 class="site-footer__heading">Help</h2>
                <ul class="site-footer__links">
                    <li><a href="{{ route('track.index') }}">Track your order</a></li>
                    <li><a href="{{ route('pages.show', 'faq') }}">FAQ</a></li>
                    <li><a href="{{ route('pages.show', 'shipping-delivery-policy') }}">Shipping &amp; delivery</a></li>
                    <li><a href="{{ route('pages.show', 'cancellation-refund-policy') }}">Cancellations &amp; refunds</a></li>
                    <li><a href="{{ route('pages.show', 'privacy-policy') }}">Privacy policy</a></li>
                    <li><a href="{{ route('pages.show', 'terms-and-conditions') }}">Terms &amp; conditions</a></li>
                </ul>
            </div>

            <div class="col-lg-4">
                <h2 class="site-footer__heading">Visit or call</h2>
                <address class="site-footer__address">
                    @foreach ($company->publicAddressLines() as $line)
                        {{ $line }}<br>
                    @endforeach
                </address>
                <ul class="site-footer__contact">
                    @if ($company->phone())
                        <li><i class="bi bi-telephone" aria-hidden="true"></i><a href="{{ $company->phoneHref() }}">{{ $company->phone() }}</a></li>
                    @endif
                    @if ($whatsapp->isConfigured())
                        <li><i class="bi bi-whatsapp" aria-hidden="true"></i><a href="{{ $whatsapp->generalLink() }}" target="_blank" rel="noopener">Chat on WhatsApp</a></li>
                    @endif
                    @if ($company->email())
                        <li><i class="bi bi-envelope" aria-hidden="true"></i><a href="mailto:{{ $company->email() }}">{{ $company->email() }}</a></li>
                    @endif
                </ul>
                @if (count($company->businessHours()))
                    <p class="site-footer__hours">
                        @foreach ($company->businessHours() as $line)
                            {{ $line }}<br>
                        @endforeach
                    </p>
                @endif
            </div>
        </div>

        <div class="site-footer__bottom">
            <p>© {{ date('Y') }} {{ $company->legalName() }}. Handmade in {{ $company->city() }}.</p>
            <p class="site-footer__ids">
                @if ($company->showFssai())
                    <span>FSSAI Lic. No. {{ $company->fssai() }}</span>
                @endif
                @if ($company->gstin())
                    <span>GSTIN {{ $company->gstin() }}</span>
                @endif
            </p>
        </div>
    </div>
</footer>
