// Image Cropper Script
// This script enables cropping functionality for avatar and cover image uploads

let avatarCropper = null;
let coverCropper = null;

function destroyCropper(cropperInstance) {
    if (cropperInstance) {
        cropperInstance.destroy();
    }
}

function bindCropperInput(inputId, modalId, imageId, hiddenInputId, previewId, aspectRatio, cropWidth, cropHeight, cropperKey) {
    const input = document.getElementById(inputId);
    const modal = document.getElementById(modalId);
    const image = document.getElementById(imageId);
    const hiddenInput = document.getElementById(hiddenInputId);
    const preview = document.getElementById(previewId);
    const cropButton = document.getElementById(modalId.replace('-modal', '-crop-button'));

    if (!input || !modal || !image || !hiddenInput || !cropButton) {
        return;
    }

    input.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) {
            return;
        }

        if (!window.Cropper) {
            console.error('CropperJS is not loaded.');
            return;
        }

        hiddenInput.value = '';

        const reader = new FileReader();
        reader.onload = function (event) {
            image.src = event.target.result;
            modal.style.display = 'block';

            destroyCropper(cropperKey === 'avatar' ? avatarCropper : coverCropper);

            const cropperInstance = new Cropper(image, {
                aspectRatio: aspectRatio,
                viewMode: 1,
                autoCropArea: 1,
                responsive: true,
                background: false,
            });

            if (cropperKey === 'avatar') {
                avatarCropper = cropperInstance;
            } else {
                coverCropper = cropperInstance;
            }
        };
        reader.readAsDataURL(file);
    });

    cropButton.addEventListener('click', function () {
        const cropperInstance = cropperKey === 'avatar' ? avatarCropper : coverCropper;
        if (!cropperInstance) {
            return;
        }

        const canvas = cropperInstance.getCroppedCanvas({
            width: cropWidth,
            height: cropHeight,
        });

        const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
        hiddenInput.value = dataUrl;

        if (preview) {
            preview.src = dataUrl;
            preview.style.display = 'block';
        }

        modal.style.display = 'none';
        destroyCropper(cropperInstance);

        if (cropperKey === 'avatar') {
            avatarCropper = null;
        } else {
            coverCropper = null;
        }
    });
}

if (window.Cropper) {
    bindCropperInput('avatar-input', 'avatar-modal', 'avatar-crop-image', 'cropped-avatar-data', 'avatar-preview-img', 1, 200, 200, 'avatar');
    bindCropperInput('cover-input', 'cover-modal', 'cover-crop-image', 'cropped-cover-data', 'cover-preview-img', 16 / 9, 1200, 300, 'cover');
}