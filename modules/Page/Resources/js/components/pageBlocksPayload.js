function createUploadToken(counter) {
    return `page_block_upload_${counter}_${Date.now()}`;
}

export function buildPagePayload(values = {}) {
    const uploads = [];
    let uploadCounter = 0;
    const directFiles = {};

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

    const sourceValues = { ...values };

    if (sourceValues.featured_image_file instanceof File) {
        directFiles.featured_image_file = sourceValues.featured_image_file;
        delete sourceValues.featured_image_file;
    }

    // Serialize puck_data as JSON string if present
    if (sourceValues.puck_data && typeof sourceValues.puck_data === 'object') {
        sourceValues.puck_data = JSON.stringify(sourceValues.puck_data);
    }

    return {
        values: serialize(sourceValues),
        uploads,
        directFiles,
    };
}
