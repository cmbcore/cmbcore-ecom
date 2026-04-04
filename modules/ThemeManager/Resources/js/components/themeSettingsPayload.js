function createUploadToken(counter) {
    return `theme_upload_${counter}_${Date.now()}`;
}

export function buildThemeSettingsPayload(values = {}) {
    const uploads = [];
    let uploadCounter = 0;

    function serialize(value) {
        if (value instanceof File) {
            const token = createUploadToken(uploadCounter);
            uploadCounter += 1;
            uploads.push({ token, file: value });

            return { upload_token: token };
        }

        if (Array.isArray(value)) {
            return value.map((item) => serialize(item));
        }

        if (value && typeof value === 'object') {
            return Object.fromEntries(
                Object.entries(value).map(([key, item]) => [key, serialize(item)]),
            );
        }

        return value;
    }

    return {
        settings: serialize(values.settings ?? {}),
        menus: serialize(values.menus ?? []),
        uploads,
    };
}
