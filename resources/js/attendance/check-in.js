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

    const labelEl      = btn.querySelector('[data-label]');
    const originalLabel = labelEl ? labelEl.textContent : 'Tại văn phòng';

    function showError(msg) {
        errBox.textContent = msg;
        errBox.classList.remove('hidden');
    }

    function setLoading(loading) {
        btn.disabled = loading;
        if (labelEl) labelEl.textContent = loading ? 'Đang kiểm tra vị trí…' : originalLabel;
    }

    btn.addEventListener('click', function () {
        errBox.classList.add('hidden');

        if (!officeLat || !officeLng) {
            form.submit();
            return;
        }

        if (!navigator.geolocation) {
            showError('Trình duyệt không hỗ trợ định vị. Vui lòng chọn WFH hoặc liên hệ với HR/Admin.');
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
                        'Bạn đang ở vị trí cách công ty ' + dist.toFixed(1) + ' km (khoảng cách tối đa: ' + officeRadius + ' km). ' +
                        'Vui lòng chọn WFH hoặc liên hệ với HR/Admin.'
                    );
                }
            },
            function (err) {
                setLoading(false);
                showError('Không thể tìm vị trí của bạn (' + err.message + '). Vui lòng bật Định vị, chọn WFH hoặc liên hệ với HR/Admin.');
            },
            { timeout: 10000, maximumAge: 60000 }
        );
    });
});
