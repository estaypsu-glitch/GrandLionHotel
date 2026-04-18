@if(config('app.reload_on_history_back'))
    <script>
        (() => {
            const cameFromBackForwardCache = (event) => {
                if (event.persisted) {
                    return true;
                }

                if (
                    typeof window.performance === 'undefined'
                    || typeof window.performance.getEntriesByType !== 'function'
                ) {
                    return false;
                }

                const navigationEntries = window.performance.getEntriesByType('navigation');
                return navigationEntries.length > 0 && navigationEntries[0].type === 'back_forward';
            };

            window.addEventListener('pageshow', (event) => {
                if (!cameFromBackForwardCache(event)) {
                    return;
                }

                window.location.reload();
            });
        })();
    </script>
@endif
