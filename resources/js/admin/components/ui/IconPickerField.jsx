import React, { useCallback, useMemo, useState } from 'react';
import { Button, Col, Input, Modal, Row, Tooltip } from 'antd';

// ─── Danh sách icon FontAwesome 6 Solid phổ biến ────────────────────────────

const FA_ICONS = [
    // Navigation & UI
    'fa-solid fa-house',
    'fa-solid fa-bars',
    'fa-solid fa-xmark',
    'fa-solid fa-chevron-right',
    'fa-solid fa-chevron-left',
    'fa-solid fa-chevron-up',
    'fa-solid fa-chevron-down',
    'fa-solid fa-angle-right',
    'fa-solid fa-angle-left',
    'fa-solid fa-arrow-right',
    'fa-solid fa-arrow-left',
    'fa-solid fa-arrow-up',
    'fa-solid fa-arrow-down',
    'fa-solid fa-circle-chevron-right',
    'fa-solid fa-circle-arrow-right',
    'fa-solid fa-angles-right',
    'fa-solid fa-angles-left',

    // Actions
    'fa-solid fa-pen-to-square',
    'fa-solid fa-trash-can',
    'fa-solid fa-floppy-disk',
    'fa-solid fa-plus',
    'fa-solid fa-minus',
    'fa-solid fa-circle-plus',
    'fa-solid fa-circle-minus',
    'fa-solid fa-circle-xmark',
    'fa-solid fa-check',
    'fa-solid fa-circle-check',
    'fa-solid fa-ban',
    'fa-solid fa-rotate-right',
    'fa-solid fa-rotate-left',
    'fa-solid fa-upload',
    'fa-solid fa-download',
    'fa-solid fa-share',
    'fa-solid fa-copy',
    'fa-solid fa-scissors',
    'fa-solid fa-paste',
    'fa-solid fa-filter',
    'fa-solid fa-sort',
    'fa-solid fa-sliders',
    'fa-solid fa-gear',
    'fa-solid fa-gears',
    'fa-solid fa-wrench',
    'fa-solid fa-screwdriver-wrench',
    'fa-solid fa-lock',
    'fa-solid fa-unlock',
    'fa-solid fa-shield-halved',
    'fa-solid fa-eye',
    'fa-solid fa-eye-slash',
    'fa-solid fa-magnifying-glass',
    'fa-solid fa-magnifying-glass-plus',
    'fa-solid fa-magnifying-glass-minus',

    // Commerce
    'fa-solid fa-cart-shopping',
    'fa-solid fa-cart-plus',
    'fa-solid fa-bag-shopping',
    'fa-solid fa-store',
    'fa-solid fa-box-open',
    'fa-solid fa-boxes-stacked',
    'fa-solid fa-tag',
    'fa-solid fa-tags',
    'fa-solid fa-receipt',
    'fa-solid fa-ticket',
    'fa-solid fa-barcode',
    'fa-solid fa-qrcode',
    'fa-solid fa-truck',
    'fa-solid fa-truck-fast',
    'fa-solid fa-warehouse',
    'fa-solid fa-money-bill-wave',
    'fa-solid fa-credit-card',
    'fa-solid fa-wallet',
    'fa-solid fa-coins',
    'fa-solid fa-dollar-sign',
    'fa-solid fa-percent',
    'fa-solid fa-gift',
    'fa-solid fa-star',
    'fa-solid fa-star-half-stroke',
    'fa-solid fa-heart',
    'fa-solid fa-thumbs-up',
    'fa-solid fa-thumbs-down',

    // Content
    'fa-solid fa-file',
    'fa-solid fa-file-lines',
    'fa-solid fa-file-image',
    'fa-solid fa-file-pdf',
    'fa-solid fa-folder',
    'fa-solid fa-folder-open',
    'fa-solid fa-folder-tree',
    'fa-solid fa-newspaper',
    'fa-solid fa-book',
    'fa-solid fa-book-open',
    'fa-solid fa-bookmark',
    'fa-solid fa-image',
    'fa-solid fa-images',
    'fa-solid fa-photo-film',
    'fa-solid fa-video',
    'fa-solid fa-play',
    'fa-solid fa-pause',
    'fa-solid fa-stop',
    'fa-solid fa-music',
    'fa-solid fa-headphones',

    // Communication
    'fa-solid fa-envelope',
    'fa-solid fa-envelope-open',
    'fa-solid fa-phone',
    'fa-solid fa-phone-flip',
    'fa-solid fa-comment',
    'fa-solid fa-comments',
    'fa-solid fa-message',
    'fa-solid fa-bell',
    'fa-solid fa-megaphone',
    'fa-solid fa-bullhorn',

    // People & Identity
    'fa-solid fa-user',
    'fa-solid fa-user-plus',
    'fa-solid fa-user-check',
    'fa-solid fa-users',
    'fa-solid fa-person',
    'fa-solid fa-address-card',
    'fa-solid fa-id-card',
    'fa-solid fa-id-badge',

    // Location
    'fa-solid fa-location-dot',
    'fa-solid fa-location-pin',
    'fa-solid fa-map',
    'fa-solid fa-map-location-dot',
    'fa-solid fa-globe',
    'fa-solid fa-earth-asia',
    'fa-solid fa-building',
    'fa-solid fa-city',
    'fa-solid fa-landmark',
    'fa-solid fa-flag',

    // Technology
    'fa-solid fa-laptop',
    'fa-solid fa-desktop',
    'fa-solid fa-mobile-screen',
    'fa-solid fa-tablet-screen-button',
    'fa-solid fa-keyboard',
    'fa-solid fa-print',
    'fa-solid fa-wifi',
    'fa-solid fa-bluetooth',
    'fa-solid fa-signal',
    'fa-solid fa-database',
    'fa-solid fa-server',
    'fa-solid fa-code',
    'fa-solid fa-terminal',
    'fa-solid fa-bug',
    'fa-solid fa-robot',
    'fa-solid fa-microchip',
    'fa-solid fa-plug',
    'fa-solid fa-cloud',
    'fa-solid fa-cloud-upload',
    'fa-solid fa-cloud-download',

    // Social & Brand
    'fa-solid fa-link',
    'fa-solid fa-link-slash',
    'fa-solid fa-share-nodes',
    'fa-solid fa-rss',
    'fa-solid fa-hashtag',
    'fa-solid fa-at',

    // Misc
    'fa-solid fa-info-circle',
    'fa-solid fa-circle-info',
    'fa-solid fa-triangle-exclamation',
    'fa-solid fa-circle-exclamation',
    'fa-solid fa-question',
    'fa-solid fa-circle-question',
    'fa-solid fa-lightbulb',
    'fa-solid fa-fire',
    'fa-solid fa-bolt',
    'fa-solid fa-sun',
    'fa-solid fa-moon',
    'fa-solid fa-snowflake',
    'fa-solid fa-leaf',
    'fa-solid fa-seedling',
    'fa-solid fa-tree',
    'fa-solid fa-paw',
    'fa-solid fa-trophy',
    'fa-solid fa-medal',
    'fa-solid fa-award',
    'fa-solid fa-certificate',
    'fa-solid fa-puzzle-piece',
    'fa-solid fa-palette',
    'fa-solid fa-paintbrush',
    'fa-solid fa-pen',
    'fa-solid fa-pencil',
    'fa-solid fa-eraser',
    'fa-solid fa-scissors',
    'fa-solid fa-ruler',
    'fa-solid fa-ruler-combined',
    'fa-solid fa-clock',
    'fa-solid fa-calendar',
    'fa-solid fa-calendar-days',
    'fa-solid fa-calendar-check',
    'fa-solid fa-chart-bar',
    'fa-solid fa-chart-line',
    'fa-solid fa-chart-pie',
    'fa-solid fa-gauge-high',
    'fa-solid fa-list',
    'fa-solid fa-list-check',
    'fa-solid fa-table',
    'fa-solid fa-table-cells',
    'fa-solid fa-th-large',
    'fa-solid fa-grip',
    'fa-solid fa-ellipsis',
    'fa-solid fa-ellipsis-vertical',
    'fa-solid fa-spinner',
    'fa-solid fa-circle-notch',
    'fa-solid fa-arrows-rotate',
    'fa-solid fa-language',
    'fa-solid fa-text-height',
    'fa-solid fa-font',
    'fa-solid fa-align-left',
    'fa-solid fa-align-center',
    'fa-solid fa-align-right',
    'fa-solid fa-indent',
    'fa-solid fa-outdent',
    'fa-solid fa-quote-left',
    'fa-solid fa-quote-right',
];

// Lấy tên đẹp từ class
function iconLabel(cls) {
    return cls.replace('fa-solid fa-', '').replace(/-/g, ' ');
}

// ─── Component chính ─────────────────────────────────────────────────────────

/**
 * IconPickerField
 *
 * Dùng làm custom control trong Ant Design Form.Item.
 * Props: value, onChange (truyền tự động bởi Form.Item)
 */
export default function IconPickerField({ value, onChange }) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');

    const filteredIcons = useMemo(() => {
        const q = search.trim().toLowerCase().replace(/\s+/g, '-');

        if (!q) return FA_ICONS;

        return FA_ICONS.filter((cls) => cls.includes(q));
    }, [search]);

    const handleSelect = useCallback((cls) => {
        onChange?.(cls);
        setOpen(false);
        setSearch('');
    }, [onChange]);

    const handleClear = useCallback(() => {
        onChange?.('');
    }, [onChange]);

    const handleOpen = useCallback(() => {
        setOpen(true);
        setSearch('');
    }, []);

    return (
        <>
            <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                {/* Preview icon đã chọn */}
                <div
                    style={{
                        width: 36,
                        height: 36,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        border: '1px solid #d9d9d9',
                        borderRadius: 6,
                        background: '#fafafa',
                        fontSize: 18,
                        color: '#595959',
                        flexShrink: 0,
                    }}
                >
                    {value ? <i className={value} aria-hidden="true" /> : <i className="fa-solid fa-icons" style={{ color: '#bbb' }} aria-hidden="true" />}
                </div>

                {/* Tên icon đang chọn */}
                <div
                    style={{
                        flex: 1,
                        fontSize: 13,
                        color: value ? '#333' : '#aaa',
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                        whiteSpace: 'nowrap',
                    }}
                >
                    {value || 'Chưa chọn icon'}
                </div>

                {/* Nút chọn */}
                <Button size="small" onClick={handleOpen} icon={<i className="fa-solid fa-icons" aria-hidden="true" />}>
                    Chọn icon
                </Button>

                {/* Nút xóa */}
                {value && (
                    <Tooltip title="Xóa icon">
                        <Button size="small" danger onClick={handleClear} icon={<i className="fa-solid fa-xmark" aria-hidden="true" />} />
                    </Tooltip>
                )}
            </div>

            {/* Popup chọn icon */}
            <Modal
                title={
                    <span>
                        <i className="fa-solid fa-icons" aria-hidden="true" style={{ marginRight: 8 }} />
                        Chọn icon
                    </span>
                }
                open={open}
                onCancel={() => { setOpen(false); setSearch(''); }}
                footer={null}
                width={700}
                styles={{ body: { padding: '12px 16px 16px' } }}
            >
                {/* Search */}
                <Input
                    prefix={<i className="fa-solid fa-magnifying-glass" aria-hidden="true" />}
                    placeholder="Tìm kiếm icon... (VD: house, cart, user)"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    allowClear
                    style={{ marginBottom: 12 }}
                    autoFocus
                />

                <div style={{ fontSize: 12, color: '#999', marginBottom: 8 }}>
                    {filteredIcons.length} icon — click để chọn
                </div>

                {/* Grid icon */}
                <div
                    style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fill, minmax(60px, 1fr))',
                        gap: 4,
                        maxHeight: 420,
                        overflowY: 'auto',
                        paddingRight: 4,
                    }}
                >
                    {filteredIcons.map((cls) => {
                        const isSelected = value === cls;

                        return (
                            <Tooltip key={cls} title={iconLabel(cls)} placement="top">
                                <button
                                    type="button"
                                    onClick={() => handleSelect(cls)}
                                    style={{
                                        display: 'flex',
                                        flexDirection: 'column',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        gap: 4,
                                        padding: '8px 4px',
                                        border: isSelected ? '2px solid #1677ff' : '1px solid #e8e8e8',
                                        borderRadius: 6,
                                        background: isSelected ? '#e6f4ff' : 'transparent',
                                        cursor: 'pointer',
                                        transition: 'all 0.15s',
                                        minHeight: 56,
                                    }}
                                    onMouseEnter={(e) => {
                                        if (!isSelected) {
                                            e.currentTarget.style.background = '#f5f5f5';
                                            e.currentTarget.style.borderColor = '#1677ff';
                                        }
                                    }}
                                    onMouseLeave={(e) => {
                                        if (!isSelected) {
                                            e.currentTarget.style.background = 'transparent';
                                            e.currentTarget.style.borderColor = '#e8e8e8';
                                        }
                                    }}
                                >
                                    <i
                                        className={cls}
                                        aria-hidden="true"
                                        style={{ fontSize: 18, color: isSelected ? '#1677ff' : '#444' }}
                                    />
                                    <span
                                        style={{
                                            fontSize: 9,
                                            color: '#999',
                                            textAlign: 'center',
                                            lineHeight: 1.2,
                                            maxWidth: 52,
                                            overflow: 'hidden',
                                            textOverflow: 'ellipsis',
                                            whiteSpace: 'nowrap',
                                        }}
                                    >
                                        {iconLabel(cls)}
                                    </span>
                                </button>
                            </Tooltip>
                        );
                    })}

                    {filteredIcons.length === 0 && (
                        <div
                            style={{
                                gridColumn: '1 / -1',
                                textAlign: 'center',
                                padding: 32,
                                color: '#999',
                            }}
                        >
                            <i className="fa-solid fa-face-frown" style={{ fontSize: 24, marginBottom: 8 }} aria-hidden="true" />
                            <p style={{ margin: 0 }}>Không tìm thấy icon phù hợp</p>
                        </div>
                    )}
                </div>
            </Modal>
        </>
    );
}
