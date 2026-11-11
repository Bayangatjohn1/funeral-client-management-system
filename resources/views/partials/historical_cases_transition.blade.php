<style>
    html.historical-route-switching .page-content { view-transition-name: historical-route-content; }
    html.historical-route-switching .historical-cases-nav-link { view-transition-name: historical-cases-nav; }
    html.historical-route-switching::view-transition-old(root),
    html.historical-route-switching::view-transition-new(root) { animation: none; }
    html.historical-route-switching::view-transition-old(historical-route-content) {
        height: 100%;
        animation: historical-route-out 150ms ease both;
    }
    html.historical-route-switching::view-transition-new(historical-route-content) {
        height: 100%;
        animation: historical-route-in 230ms ease 60ms both;
    }
    html.historical-route-switching::view-transition-group(historical-cases-nav) {
        z-index: 1;
        animation-duration: 340ms;
        animation-timing-function: cubic-bezier(.2,.65,.25,1);
    }
    html.historical-route-switching::view-transition-image-pair(historical-cases-nav) {
        overflow: hidden;
        border-radius: 10px;
    }
    @keyframes historical-route-out { to { opacity: 0; } }
    @keyframes historical-route-in { from { opacity: 0; } }
    @media (prefers-reduced-motion: reduce) {
        html.historical-route-switching::view-transition-old(historical-route-content),
        html.historical-route-switching::view-transition-new(historical-route-content),
        html.historical-route-switching::view-transition-group(historical-cases-nav) {
            animation: none;
        }
    }
</style>
