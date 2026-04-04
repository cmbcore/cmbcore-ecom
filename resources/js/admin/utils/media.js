const IMAGE_ASSET_PATTERN = /\.(avif|bmp|gif|ico|jpe?g|png|svg|webp)(?:[?#].*)?$/i;

export function normalizeFolderPath(value) {
    const normalized = String(value ?? '')
        .trim()
        .replace(/\\/g, '/')
        .replace(/^\/+|\/+$/g, '')
        .replace(/\/{2,}/g, '/');

    return normalized === '.' ? '' : normalized;
}

export function dirnameFromPath(value) {
    const normalized = normalizeFolderPath(value);

    if (!normalized.includes('/')) {
        return '';
    }

    return normalized.split('/').slice(0, -1).join('/');
}

export function extFromPath(value) {
    const normalized = String(value ?? '').trim();
    const basename = normalized.split('/').pop() ?? '';
    const parts = basename.split('.');

    return parts.length > 1 ? (parts.pop() ?? '').toLowerCase() : undefined;
}

export function resolveMediaUrl(value) {
    const normalized = String(value ?? '').trim();

    if (!normalized) {
        return '';
    }

    if (
        normalized.startsWith('http://')
        || normalized.startsWith('https://')
        || normalized.startsWith('//')
        || normalized.startsWith('/')
    ) {
        return normalized;
    }

    if (normalized.startsWith('storage/')) {
        return `/${normalized}`;
    }

    return `/storage/${normalized.replace(/^\/+/, '')}`;
}

export function isImageAsset(value) {
    return IMAGE_ASSET_PATTERN.test(String(value ?? '').trim());
}

export function formatFileSize(value) {
    const size = Number(value ?? 0);

    if (!Number.isFinite(size) || size <= 0) {
        return '0 B';
    }

    if (size < 1024) {
        return `${size} B`;
    }

    if (size < 1024 * 1024) {
        return `${(size / 1024).toFixed(1)} KB`;
    }

    if (size < 1024 * 1024 * 1024) {
        return `${(size / (1024 * 1024)).toFixed(1)} MB`;
    }

    return `${(size / (1024 * 1024 * 1024)).toFixed(1)} GB`;
}
