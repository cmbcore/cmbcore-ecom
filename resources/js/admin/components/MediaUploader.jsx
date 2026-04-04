import React from 'react';
import { Button, Card, Input, Select, Space, Tag, Upload, message } from 'antd';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';
import { showDeleteConfirm } from '@admin/utils/confirm';

function createMediaKey() {
    if (window.crypto?.randomUUID) {
        return `media-${window.crypto.randomUUID()}`;
    }

    return `media-${Date.now()}-${Math.round(Math.random() * 100000)}`;
}

function resolveMediaType(file) {
    if (file.type?.startsWith('image/')) {
        return 'image';
    }

    if (file.type?.startsWith('video/')) {
        return 'video';
    }

    return null;
}

function normalizeItems(items = []) {
    return items.map((item, index) => ({
        ...item,
        position: index,
    }));
}

function resolveSkuReference(item) {
    if (item.sku_key) {
        return `key:${item.sku_key}`;
    }

    if (item.product_sku_id) {
        return `id:${item.product_sku_id}`;
    }

    return 'product';
}

function applySkuReference(item, reference) {
    if (reference === 'product') {
        return {
            ...item,
            sku_key: null,
            product_sku_id: null,
        };
    }

    if (reference.startsWith('key:')) {
        return {
            ...item,
            sku_key: reference.replace(/^key:/, ''),
            product_sku_id: null,
        };
    }

    if (reference.startsWith('id:')) {
        return {
            ...item,
            product_sku_id: Number(reference.replace(/^id:/, '')),
            sku_key: null,
        };
    }

    return item;
}

function formatFileSize(size = 0) {
    if (size >= 1024 * 1024) {
        return `${(size / (1024 * 1024)).toFixed(1)} MB`;
    }

    return `${Math.max(1, Math.round(size / 1024))} KB`;
}

function validateItems(items, t) {
    const imageCount = items.filter((item) => item.type === 'image').length;
    const videoItems = items.filter((item) => item.type === 'video');

    if (imageCount > 9) {
        message.error(t('media_uploader.validation.max_images'));
        return false;
    }

    if (videoItems.length > 1) {
        message.error(t('media_uploader.validation.max_videos'));
        return false;
    }

    if (videoItems.some((item) => (item.file?.size ?? item.size ?? 0) > 50 * 1024 * 1024)) {
        message.error(t('media_uploader.validation.video_too_large'));
        return false;
    }

    return true;
}

function createNewMediaItem(file) {
    const type = resolveMediaType(file);

    return {
        uid: createMediaKey(),
        type,
        file,
        filename: file.name,
        size: file.size,
        url: type === 'image' ? window.URL.createObjectURL(file) : null,
        alt_text: '',
        sku_key: null,
        product_sku_id: null,
        resize_settings: {
            widths: [200, 400, 800],
        },
    };
}

export default function MediaUploader({ value = [], onChange, skuOptions = [] }) {
    const { t } = useLocale();
    const items = Array.isArray(value) ? value : [];

    function updateItems(nextItems) {
        onChange?.(normalizeItems(nextItems));
    }

    function handleBeforeUpload(file) {
        const nextItem = createNewMediaItem(file);

        if (!nextItem.type) {
            message.error(t('media_uploader.validation.invalid_type'));
            return Upload.LIST_IGNORE;
        }

        const nextItems = [...items, nextItem];

        if (!validateItems(nextItems, t)) {
            return Upload.LIST_IGNORE;
        }

        updateItems(nextItems);

        return Upload.LIST_IGNORE;
    }

    function handleRemove(uid) {
        updateItems(items.filter((item) => item.uid !== uid));
    }

    function handleMove(index, direction) {
        const targetIndex = index + direction;

        if (targetIndex < 0 || targetIndex >= items.length) {
            return;
        }

        const nextItems = [...items];
        const [movedItem] = nextItems.splice(index, 1);
        nextItems.splice(targetIndex, 0, movedItem);
        updateItems(nextItems);
    }

    function handleChange(uid, changes) {
        updateItems(items.map((item) => (item.uid === uid ? { ...item, ...changes } : item)));
    }

    return (
        <div className="media-uploader">
            <Upload beforeUpload={handleBeforeUpload} showUploadList={false} multiple accept="image/*,video/*">
                <Button icon={<FontIcon name="media" />}>
                    {t('media_uploader.actions.add_files')}
                </Button>
            </Upload>

            <Space direction="vertical" size={16} style={{ display: 'flex', marginTop: 16 }}>
                {items.map((item, index) => (
                    <Card
                        key={item.uid ?? `media-${index}`}
                        size="small"
                        title={item.filename}
                        extra={<Tag color={item.type === 'video' ? 'purple' : 'blue'}>{t(`media_uploader.types.${item.type}`)}</Tag>}
                    >
                        <div className="media-uploader__item">
                            <div className={`media-uploader__preview media-uploader__preview--${item.type}`}>
                                {item.type === 'image' && item.url ? (
                                    <img src={item.url} alt={item.alt_text || item.filename} />
                                ) : (
                                    <span className="media-uploader__icon">
                                        <FontIcon name={item.type === 'video' ? 'video' : 'image'} />
                                    </span>
                                )}
                            </div>

                            <div className="media-uploader__fields">
                                <Input
                                    value={item.alt_text ?? ''}
                                    onChange={(event) => handleChange(item.uid, { alt_text: event.target.value })}
                                    placeholder={t('media_uploader.placeholders.alt_text')}
                                />

                                <Select
                                    value={resolveSkuReference(item)}
                                    onChange={(reference) => handleChange(item.uid, applySkuReference(item, reference))}
                                    options={[
                                        { label: t('media_uploader.product_level'), value: 'product' },
                                        ...skuOptions,
                                    ]}
                                />

                                <div className="media-uploader__meta">
                                    <span>{formatFileSize(item.file?.size ?? item.size ?? 0)}</span>
                                    <span>{item.type === 'video' ? t('media_uploader.messages.video_only_one') : t('media_uploader.messages.image_gallery')}</span>
                                </div>
                            </div>

                            <Space direction="vertical">
                                <Button size="small" icon={<FontIcon name="move-up" />} onClick={() => handleMove(index, -1)} />
                                <Button size="small" icon={<FontIcon name="move-down" />} onClick={() => handleMove(index, 1)} />
                                <Button
                                    size="small"
                                    danger
                                    icon={<FontIcon name="delete" />}
                                    onClick={() => showDeleteConfirm({
                                        title: 'Xóa media khỏi sản phẩm?',
                                        content: `${item.filename} sẽ bị gỡ khỏi form hiện tại.`,
                                        onConfirm: () => handleRemove(item.uid),
                                    })}
                                >
                                    {t('media_uploader.actions.remove')}
                                </Button>
                            </Space>
                        </div>
                    </Card>
                ))}
            </Space>
        </div>
    );
}
