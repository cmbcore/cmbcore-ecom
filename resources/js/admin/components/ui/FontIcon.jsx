import React from 'react';

const ICON_MAP = {
    activate: 'fa-solid fa-circle-check',
    admin: 'fa-solid fa-shield-halved',
    appearance: 'fa-solid fa-palette',
    blog: 'fa-solid fa-newspaper',
    categories: 'fa-solid fa-folder-tree',
    configure: 'fa-solid fa-sliders',
    create: 'fa-solid fa-circle-plus',
    dashboard: 'fa-solid fa-gauge-high',
    delete: 'fa-solid fa-trash-can',
    disable: 'fa-solid fa-ban',
    edit: 'fa-solid fa-pen-to-square',
    external: 'fa-solid fa-arrow-up-right-from-square',
    generate: 'fa-solid fa-wand-magic-sparkles',
    home: 'fa-solid fa-house',
    image: 'fa-solid fa-image',
    link: 'fa-solid fa-link',
    locale: 'fa-solid fa-language',
    logout: 'fa-solid fa-right-from-bracket',
    media: 'fa-solid fa-photo-film',
    move_down: 'fa-solid fa-arrow-down',
    move_up: 'fa-solid fa-arrow-up',
    page: 'fa-solid fa-file-lines',
    preview: 'fa-solid fa-eye',
    plugin: 'fa-solid fa-puzzle-piece',
    product: 'fa-solid fa-box-open',
    refresh: 'fa-solid fa-rotate-right',
    save: 'fa-solid fa-floppy-disk',
    seo: 'fa-solid fa-magnifying-glass-chart',
    storefront: 'fa-solid fa-store',
    sku: 'fa-solid fa-barcode',
    theme: 'fa-solid fa-palette',
    update: 'fa-solid fa-floppy-disk',
    upload: 'fa-solid fa-upload',
    video: 'fa-solid fa-video',
};

export default function FontIcon({ name, className = '' }) {
    const normalizedName = typeof name === 'string' ? name.replace(/-/g, '_') : name;
    const resolvedName = typeof name === 'string' && name.includes('fa-')
        ? name
        : (ICON_MAP[normalizedName] ?? 'fa-solid fa-circle');

    return <i aria-hidden="true" className={`font-icon ${resolvedName} ${className}`.trim()} />;
}
