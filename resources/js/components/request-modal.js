// Shared submit behaviour for the Leave / OT / WFH request modals.
// While a request is in flight every enabled button inside `scope` is disabled and the clicked one shows a
// spinner + label. On success the page reloads with the busy state still visible; on failure the buttons are
// restored and the error is alerted.

const SPINNER = '<svg class="inline w-4 h-4 mr-1.5 -mt-0.5 animate-spin" fill="none" viewBox="0 0 24 24">'
    + '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>'
    + '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>';

window.sendRequestModal = function (btn, scope, { url, method = 'POST', body = null, label, errMsg, errConn }) {
    if (!scope || scope.dataset.busy === '1') return;
    scope.dataset.busy = '1';

    const disabled = [...scope.querySelectorAll('button:not([disabled])')];
    disabled.forEach((b) => { b.disabled = true; b.classList.add('opacity-60', 'cursor-not-allowed'); });

    const original = btn ? btn.innerHTML : null;
    if (btn) { btn.innerHTML = SPINNER; btn.append(label); }

    const restore = () => {
        delete scope.dataset.busy;
        disabled.forEach((b) => { b.disabled = false; b.classList.remove('opacity-60', 'cursor-not-allowed'); });
        if (btn) btn.innerHTML = original;
    };

    const headers = {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    };
    if (body) headers['Content-Type'] = 'application/json';

    fetch(url, { method, headers, body: body ? JSON.stringify(body) : undefined })
        .then((r) => r.json())
        .then((d) => {
            if (d.success) { location.reload(); return; }
            restore();
            alert(d.message || errMsg);
        })
        .catch(() => { restore(); alert(errConn); });
};
