{{-- Page preloader: only shown when JavaScript runs (html.js), fades out on load, never longer than 4s. --}}
<div id="preloader" @class(["preloader-content" => ($scope ?? null) === "content"]) role="status" aria-label="Loading CareSenda">
    <div class="traffic-loader" aria-hidden="true"></div>
    <p class="preloader-word">caresenda</p>
</div>
<script>
    (function () {
        var el = document.getElementById('preloader');
        var done = false;
        function hide() {
            if (done || !el) return;
            done = true;
            el.classList.add('is-hidden');
            setTimeout(function () { el.remove(); }, 450);
        }
        if (document.readyState === 'complete') hide();
        else window.addEventListener('load', hide);
        setTimeout(hide, 4000);
        // Pages restored from the back/forward cache are already loaded.
        window.addEventListener('pageshow', function (e) { if (e.persisted) hide(); });
    })();
</script>
