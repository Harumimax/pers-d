@props([
    'linkHref' => '#',
    'linkLabel' => __('common.links.about'),
])

<footer class="site-footer" role="contentinfo">
    <div class="container footer-inner">
        <a href="{{ $linkHref }}" class="footer-link">
            {{ $linkLabel }}
        </a>
    </div>
</footer>
