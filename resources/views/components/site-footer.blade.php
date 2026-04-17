@props([
    'linkHref' => '#',
    'linkLabel' => __('common.links.about'),
])

<footer class="site-footer" role="contentinfo">
    <div class="container footer-inner">
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

        <a href="{{ $linkHref }}" class="footer-link">
            {{ $linkLabel }}
        </a>
    </div>
</footer>
