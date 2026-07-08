@props([
    'linkHref' => '#',
    'linkLabel' => __('common.links.about'),
])

<footer class="site-footer wk-footer" role="contentinfo">
    <div class="container wk-footer__inner">
        @php
            $yandexMetrikaId = trim((string) config('app.analytics.yandex_metrika_id'));
            $shouldRenderYandexMetrika = $yandexMetrikaId !== ''
                && request()->path() !== '/'
                && ! request()->routeIs('login', 'register');
        @endphp

        @if ($shouldRenderYandexMetrika)
            <!-- Yandex.Metrika counter -->
            <script type="text/javascript">
                (function(m,e,t,r,i,k,a){
                    m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
                    m[i].l=1*new Date();
                    for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
                    k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
                })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id={{ $yandexMetrikaId }}', 'ym');

                ym({{ $yandexMetrikaId }}, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
            </script>
            <noscript>
                <div><img src="https://mc.yandex.ru/watch/{{ $yandexMetrikaId }}" style="position:absolute; left:-9999px;" alt="" /></div>
            </noscript>
            <!-- /Yandex.Metrika counter -->
        @endif

        <div class="wk-footer__brand">
            <div class="wk-footer__mark" aria-hidden="true">WK</div>
            <div>
                <p class="wk-footer__title">WordKeeper</p>
                <p class="wk-footer__text">Save words. Practice them. Remember more.</p>
            </div>
        </div>

        <nav class="wk-footer__nav" aria-label="Footer navigation">
            <a href="{{ url('/') }}" class="wk-footer__link">Home</a>
            <a href="{{ route('ready-dictionaries.index') }}" class="wk-footer__link">{{ __('common.links.ready_dictionaries') }}</a>
            <a href="{{ route('remainder') }}" class="wk-footer__link">{{ __('common.links.remainder') }}</a>
            <a href="{{ $linkHref }}" class="wk-footer__link">{{ $linkLabel }}</a>
        </nav>
    </div>
</footer>
