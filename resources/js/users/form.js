import Croppie from 'croppie';
import 'croppie/croppie.css';

let croppie = null;

const fileInput      = document.getElementById('profilePictureInput');
const cropModal      = document.getElementById('cropModal');
const cropElement    = document.getElementById('cropElement');
const cropConfirmBtn = document.getElementById('cropConfirmBtn');
const cropCancelBtn  = document.getElementById('cropCancelBtn');
const currentPicture = document.getElementById('currentPicture');
const hiddenInput    = document.getElementById('profilePictureCropped');

function openModal(url) {
    cropModal.classList.remove('hidden');

    if (croppie) {
        croppie.destroy();
        croppie = null;
    }

    croppie = new Croppie(cropElement, {
        viewport: { width: 200, height: 200, type: 'circle' },
        boundary: { width: 280, height: 280 },
        enableZoom: true,
        showZoomer: true,
        enableResize: false,
    });

    croppie.bind({ url });
}

function closeModal() {
    cropModal.classList.add('hidden');
    if (croppie) {
        croppie.destroy();
        croppie = null;
    }
    fileInput.value = '';
}

if (fileInput) {
    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (ev) => openModal(ev.target.result);
        reader.readAsDataURL(file);
    });

    cropConfirmBtn.addEventListener('click', () => {
        if (!croppie) return;
        croppie.result({
            type: 'canvas',
            size: { width: 300, height: 300 },
            format: 'jpeg',
            quality: 0.85,
            circle: false,
        }).then((dataUrl) => {
            hiddenInput.value = dataUrl;

            // Replace current picture element with the new image
            const img = document.createElement('img');
            img.id = 'currentPicture';
            img.src = dataUrl;
            img.className = 'w-24 h-24 rounded-full object-cover border-2 border-gray-300';
            currentPicture.replaceWith(img);

            closeModal();
        });
    });

    cropCancelBtn.addEventListener('click', closeModal);

    // Close on backdrop click
    cropModal.addEventListener('click', (e) => {
        if (e.target === cropModal) closeModal();
    });
}
