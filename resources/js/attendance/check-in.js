document.addEventListener('DOMContentLoaded', function () {
    const btn     = document.getElementById('onSiteBtn');
    const form    = document.getElementById('onSiteForm');
    const errBox  = document.getElementById('geoErrorBox');

    if (!btn || !form) return;

    const officeLat    = btn.dataset.lat    ? parseFloat(btn.dataset.lat)    : null;
    const officeLng    = btn.dataset.lng    ? parseFloat(btn.dataset.lng)    : null;
    const officeRadius = btn.dataset.radius ? parseFloat(btn.dataset.radius) : 2;

    function haversine(lat1, lon1, lat2, lon2) {
        const R    = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a    = Math.sin(dLat / 2) ** 2
                   + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
                   * Math.sin(dLon / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function showError(msg) {
        errBox.textContent = msg;
        errBox.classList.remove('hidden');
    }

    function setLoading(loading) {
        btn.disabled     = loading;
        btn.querySelector('[data-label]').textContent = loading ? 'Checking location…' : 'On Site';
    }

    btn.addEventListener('click', function () {
        errBox.classList.add('hidden');

        if (!officeLat || !officeLng) {
            form.submit();
            return;
        }

        if (!navigator.geolocation) {
            showError('Geolocation is not supported by your browser. Please use WFH instead.');
            return;
        }

        setLoading(true);

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                console.log("Current Position: ", pos.coords.latitude, pos.coords.longitude);
                console.log("Office Position: ", officeLat, officeLng);
                const dist = haversine(
                    pos.coords.latitude, pos.coords.longitude,
                    officeLat, officeLng
                );
                setLoading(false);
                if (dist <= officeRadius) {
                    form.submit();
                } else {
                    showError(
                        'You are ' + dist.toFixed(1) + ' km from the office (limit: ' + officeRadius + ' km). ' +
                        'Please use Work from Home instead.'
                    );
                }
            },
            function (err) {
                setLoading(false);
                showError('Unable to get your location (' + err.message + '). Please use WFH or enable location access.');
            },
            { timeout: 10000, maximumAge: 60000 }
        );
    });
});
