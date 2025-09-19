(function () {
    const settings = window.pspaMSProfilePicture;

    if (!settings || !settings.fieldKey) {
        return;
    }

    const allowedExtensions = Array.isArray(settings.allowedExtensions)
        ? settings.allowedExtensions.map((extension) => String(extension).toLowerCase())
        : [];

    const ready = (callback) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    };

    ready(() => {
        const field = document.querySelector(
            '.acf-field[data-key="' + settings.fieldKey + '"]'
        );

        if (!field) {
            return;
        }

        field.classList.add('pspa-profile-picture-field--customized');

        field
            .querySelectorAll('.acf-image-uploader, .acf-basic-uploader')
            .forEach((uploader) => {
                uploader.style.display = 'none';
            });

        const acfValueInput = field.querySelector('input[type="hidden"][name^="acf"]');
        const acfIdInput = field.querySelector('input[type="hidden"][data-name="id"]');
        const acfUrlInput = field.querySelector('input[type="hidden"][data-name="url"]');
        const acfAltInput = field.querySelector('input[type="hidden"][data-name="alt"]');

        const container = document.createElement('div');
        container.className = 'pspa-profile-picture-uploader';

        const previewWrapper = document.createElement('div');
        previewWrapper.className = 'pspa-profile-picture-preview';

        const previewImage = document.createElement('img');
        const fallbackImage =
            (settings && settings.defaultImage) || '';
        const placeholderText =
            (settings.strings && settings.strings.placeholder) || '';

        previewImage.className = 'pspa-profile-picture-image';
        previewImage.alt = placeholderText;

        if (fallbackImage) {
            previewImage.src = fallbackImage;
        } else {
            previewImage.setAttribute('hidden', 'hidden');
        }

        previewWrapper.appendChild(previewImage);

        const controls = document.createElement('div');
        controls.className = 'pspa-profile-picture-controls';

        const uploadButton = document.createElement('button');
        uploadButton.type = 'button';
        uploadButton.className = 'button pspa-profile-picture-upload';
        uploadButton.textContent =
            (settings.strings && settings.strings.upload) ||
            'Επιλογή εικόνας';

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'button pspa-profile-picture-remove';
        removeButton.textContent =
            (settings.strings && settings.strings.remove) ||
            'Αφαίρεση εικόνας';

        const status = document.createElement('p');
        status.className = 'pspa-profile-picture-status';
        status.setAttribute('role', 'status');
        status.setAttribute('aria-live', 'polite');

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/*';
        fileInput.className = 'pspa-profile-picture-file';
        fileInput.style.display = 'none';

        controls.appendChild(uploadButton);
        controls.appendChild(removeButton);
        controls.appendChild(status);
        controls.appendChild(fileInput);

        container.appendChild(previewWrapper);
        container.appendChild(controls);

        const customHiddenInput = document.createElement('input');
        customHiddenInput.type = 'hidden';
        customHiddenInput.name = 'pspa_profile_picture_attachment';
        container.appendChild(customHiddenInput);

        const inputWrapper = field.querySelector('.acf-input') || field;
        const instructions = inputWrapper.querySelector(
            '.acf-field-instructions, .description'
        );

        if (instructions) {
            instructions.parentNode.insertBefore(container, instructions);
        } else {
            inputWrapper.appendChild(container);
        }

        const setPreview = (url) => {
            const effectiveUrl = url || fallbackImage;

            if (effectiveUrl) {
                if (previewImage.getAttribute('src') !== effectiveUrl) {
                    previewImage.src = effectiveUrl;
                }
                previewImage.removeAttribute('hidden');
            } else {
                previewImage.removeAttribute('src');
                previewImage.setAttribute('hidden', 'hidden');
            }
        };

        const setStatus = (message, isError) => {
            status.textContent = message || '';
            status.classList.toggle('is-error', Boolean(isError));
        };

        const setUploading = (isUploading) => {
            container.classList.toggle('is-uploading', isUploading);
            uploadButton.disabled = isUploading;
            removeButton.disabled = isUploading || !customHiddenInput.value;
        };

        const applyValue = (attachment) => {
            const id = attachment && attachment.id ? parseInt(attachment.id, 10) : 0;
            const value = id > 0 ? String(id) : '';
            const previewUrl =
                (attachment && attachment.url) ||
                (attachment && attachment.fullUrl) ||
                '';
            const altText = (attachment && attachment.alt) || '';

            customHiddenInput.value = value;

            if (acfValueInput) {
                acfValueInput.value = value;
                acfValueInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (acfIdInput) {
                acfIdInput.value = value;
            }

            if (acfUrlInput) {
                acfUrlInput.value = previewUrl;
            }

            if (acfAltInput) {
                acfAltInput.value = altText;
            }

            previewImage.alt = altText || placeholderText;
            setPreview(previewUrl);
            container.classList.toggle('has-image', Boolean(value));
            removeButton.disabled = !value || container.classList.contains('is-uploading');
        };

        const getDefaultError = () =>
            (settings.strings && settings.strings.error) ||
            'Η μεταφόρτωση απέτυχε.';

        const validateFile = (file) => {
            if (!file) {
                return { valid: false, message: '' };
            }

            if (settings.maxFileSize && file.size && file.size > settings.maxFileSize) {
                return {
                    valid: false,
                    message:
                        (settings.strings && settings.strings.tooBig) ||
                        getDefaultError(),
                };
            }

            if (file.type && file.type.indexOf('image/') === 0) {
                return { valid: true };
            }

            if (!allowedExtensions.length) {
                return { valid: true };
            }

            const name = file.name ? file.name.toLowerCase() : '';
            const match = name.match(/\.([a-z0-9]+)$/);

            if (!match) {
                return {
                    valid: false,
                    message:
                        (settings.strings && settings.strings.invalidType) ||
                        getDefaultError(),
                };
            }

            if (allowedExtensions.indexOf(match[1]) === -1) {
                return {
                    valid: false,
                    message:
                        (settings.strings && settings.strings.invalidType) ||
                        getDefaultError(),
                };
            }

            return { valid: true };
        };

        uploadButton.addEventListener('click', () => {
            fileInput.click();
        });

        removeButton.addEventListener('click', () => {
            if (!customHiddenInput.value || container.classList.contains('is-uploading')) {
                return;
            }

            applyValue({});
            setStatus(
                (settings.strings && settings.strings.removeSuccess) || '',
                false
            );
        });

        fileInput.addEventListener('change', () => {
            const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;

            if (!file) {
                return;
            }

            const validation = validateFile(file);

            if (!validation.valid) {
                setStatus(validation.message, true);
                fileInput.value = '';
                return;
            }

            setStatus((settings.strings && settings.strings.uploading) || '', false);
            setUploading(true);

            const formData = new FormData();
            formData.append('file', file);

            fetch(settings.restUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-WP-Nonce': settings.nonce || '',
                },
                body: formData,
            })
                .then(async (response) => {
                    let payload = null;

                    try {
                        payload = await response.json();
                    } catch (error) {
                        // Ignore JSON parse errors; handled below.
                    }

                    if (!response.ok) {
                        const message =
                            (payload && payload.message) || getDefaultError();
                        throw new Error(message);
                    }

                    return payload;
                })
                .then((payload) => {
                    applyValue(payload || {});
                    setStatus(
                        (settings.strings && settings.strings.success) || '',
                        false
                    );
                })
                .catch((error) => {
                    setStatus(error.message || getDefaultError(), true);
                })
                .finally(() => {
                    setUploading(false);
                    fileInput.value = '';
                });
        });

        applyValue(settings.current || {});
        setStatus('', false);
    });
})();
