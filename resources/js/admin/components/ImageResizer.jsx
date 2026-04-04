import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Button, Popconfirm, Slider, Space, Spin, message } from 'antd';
import MediaPickerModal from '@admin/components/media/MediaPickerModal';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';
import { deletePopconfirmProps } from '@admin/utils/confirm';
import { resolveMediaUrl } from '@admin/utils/media';

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}

function cropToBlob(imgEl, crop, outputSize) {
    return new Promise((resolve, reject) => {
        const canvas = document.createElement('canvas');
        canvas.width = outputSize.width;
        canvas.height = outputSize.height;
        const ctx = canvas.getContext('2d');

        ctx.drawImage(
            imgEl,
            crop.x,
            crop.y,
            crop.w,
            crop.h,
            0,
            0,
            outputSize.width,
            outputSize.height,
        );

        canvas.toBlob(
            (blob) => {
                if (blob) {
                    resolve(blob);
                } else {
                    reject(new Error('Canvas toBlob failed'));
                }
            },
            'image/jpeg',
            0.92,
        );
    });
}

const PRESETS = [
    { key: '1:1', label: '1:1', ratio: 1 },
    { key: '16:9', label: '16:9', ratio: 16 / 9 },
    { key: '4:3', label: '4:3', ratio: 4 / 3 },
    { key: '3:2', label: '3:2', ratio: 3 / 2 },
    { key: '21:9', label: '21:9', ratio: 21 / 9 },
    { key: 'free', label: 'Free', ratio: null },
];

export default function ImageResizer({
    value,
    onChange,
    existingUrl,
    presets: customPresets,
    defaultPreset = '1:1',
    outputWidth = 800,
    outputHeight: fixedOutputHeight,
    accept = 'image/*',
}) {
    const { t } = useLocale();
    const availablePresets = customPresets || PRESETS;

    const viewportRef = useRef(null);
    const imgRef = useRef(null);
    const fileInputRef = useRef(null);

    const [rawSrc, setRawSrc] = useState(null);
    const [previewSrc, setPreviewSrc] = useState(null);
    const [editing, setEditing] = useState(false);
    const [pickerOpen, setPickerOpen] = useState(false);
    const [selectedPreset, setSelectedPreset] = useState(defaultPreset);
    const [containerWidth, setContainerWidth] = useState(400);

    const [scale, setScale] = useState(1);
    const [offset, setOffset] = useState({ x: 0, y: 0 });
    const [dragging, setDragging] = useState(false);
    const [dragStart, setDragStart] = useState({ x: 0, y: 0, ox: 0, oy: 0 });
    const [loading, setLoading] = useState(false);
    const [naturalSize, setNaturalSize] = useState({ w: 1, h: 1 });

    const currentPreset = availablePresets.find((preset) => preset.key === selectedPreset) || availablePresets[0];
    const aspectRatio = currentPreset?.ratio || (naturalSize.w / naturalSize.h);
    const viewportHeight = Math.round(containerWidth / aspectRatio);

    const outputDimensions = {
        width: outputWidth,
        height: fixedOutputHeight || Math.round(outputWidth / aspectRatio),
    };

    useEffect(() => {
        const element = viewportRef.current;

        if (!element) {
            return undefined;
        }

        const updateSize = () => {
            const nextWidth = Math.max(280, Math.min(500, Math.floor(element.parentElement?.offsetWidth || 400)));
            setContainerWidth((current) => (current === nextWidth ? current : nextWidth));
        };

        updateSize();

        if (typeof window.ResizeObserver === 'function') {
            const observer = new window.ResizeObserver(() => updateSize());
            observer.observe(element.parentElement || element);
            return () => observer.disconnect();
        }

        window.addEventListener('resize', updateSize);
        return () => window.removeEventListener('resize', updateSize);
    }, [editing]);

    useEffect(() => {
        if (value instanceof File) {
            const url = window.URL.createObjectURL(value);
            setPreviewSrc((current) => {
                if (current?.startsWith('blob:')) {
                    window.URL.revokeObjectURL(current);
                }

                return url;
            });

            return undefined;
        }

        if (typeof value === 'string' && value.trim() !== '') {
            setPreviewSrc(resolveMediaUrl(value));
            return undefined;
        }

        if (value === null) {
            setPreviewSrc(null);
            return undefined;
        }

        setPreviewSrc(existingUrl ? resolveMediaUrl(existingUrl) : null);
        return undefined;
    }, [existingUrl, value]);

    useEffect(() => () => {
        if (rawSrc?.startsWith('blob:')) {
            window.URL.revokeObjectURL(rawSrc);
        }
    }, [rawSrc]);

    function fitImageToViewport(width, height, viewportWidth, viewportHeightOverride) {
        const currentViewportWidth = viewportWidth || containerWidth;
        const currentViewportHeight = viewportHeightOverride || Math.round(currentViewportWidth / aspectRatio);

        const scaleX = currentViewportWidth / width;
        const scaleY = currentViewportHeight / height;
        const initialScale = Math.max(scaleX, scaleY);

        setScale(initialScale);
        setOffset({
            x: (currentViewportWidth - width * initialScale) / 2,
            y: (currentViewportHeight - height * initialScale) / 2,
        });
    }

    function handleImageLoad(event) {
        const { naturalWidth, naturalHeight } = event.target;
        setNaturalSize({ w: naturalWidth, h: naturalHeight });
        fitImageToViewport(naturalWidth, naturalHeight);
        setLoading(false);
    }

    useEffect(() => {
        if (!editing || naturalSize.w <= 1) {
            return;
        }

        fitImageToViewport(naturalSize.w, naturalSize.h);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedPreset, containerWidth, editing]);

    const clampOffset = useCallback(
        (offsetX, offsetY, currentScale) => {
            const currentViewportHeight = Math.round(containerWidth / aspectRatio);
            const displayWidth = naturalSize.w * currentScale;
            const displayHeight = naturalSize.h * currentScale;

            return {
                x: clamp(offsetX, containerWidth - displayWidth, 0),
                y: clamp(offsetY, currentViewportHeight - displayHeight, 0),
            };
        },
        [naturalSize, containerWidth, aspectRatio],
    );

    function onMouseDown(event) {
        event.preventDefault();
        setDragging(true);
        setDragStart({ x: event.clientX, y: event.clientY, ox: offset.x, oy: offset.y });
    }

    function onMouseMove(event) {
        if (!dragging) {
            return;
        }

        const deltaX = event.clientX - dragStart.x;
        const deltaY = event.clientY - dragStart.y;
        setOffset(clampOffset(dragStart.ox + deltaX, dragStart.oy + deltaY, scale));
    }

    function onMouseUp() {
        setDragging(false);
    }

    function onTouchStart(event) {
        const touch = event.touches[0];
        setDragging(true);
        setDragStart({ x: touch.clientX, y: touch.clientY, ox: offset.x, oy: offset.y });
    }

    function onTouchMove(event) {
        if (!dragging) {
            return;
        }

        const touch = event.touches[0];
        const deltaX = touch.clientX - dragStart.x;
        const deltaY = touch.clientY - dragStart.y;
        setOffset(clampOffset(dragStart.ox + deltaX, dragStart.oy + deltaY, scale));
    }

    function handleZoomChange(nextScale) {
        const minScale = Math.max(containerWidth / naturalSize.w, viewportHeight / naturalSize.h);
        const clampedScale = clamp(nextScale, minScale, 5);
        setScale(clampedScale);
        setOffset((current) => clampOffset(current.x, current.y, clampedScale));
    }

    function openEditor(src) {
        if (rawSrc?.startsWith('blob:')) {
            window.URL.revokeObjectURL(rawSrc);
        }

        setRawSrc(src);
        setEditing(true);
        setLoading(true);
    }

    function handleFileSelect(event) {
        const file = event.target.files?.[0];

        if (!file || !file.type.startsWith('image/')) {
            message.error(t('image_uploader.invalid_type'));
            return;
        }

        openEditor(window.URL.createObjectURL(file));
        event.target.value = '';
    }

    async function handleCrop() {
        if (!imgRef.current) {
            return;
        }

        const cropX = -offset.x / scale;
        const cropY = -offset.y / scale;
        const cropW = containerWidth / scale;
        const cropH = viewportHeight / scale;

        try {
            const blob = await cropToBlob(
                imgRef.current,
                { x: cropX, y: cropY, w: cropW, h: cropH },
                outputDimensions,
            );
            const croppedFile = new File([blob], 'image.jpg', { type: 'image/jpeg' });

            if (previewSrc?.startsWith('blob:')) {
                window.URL.revokeObjectURL(previewSrc);
            }

            setPreviewSrc(window.URL.createObjectURL(blob));

            if (rawSrc?.startsWith('blob:')) {
                window.URL.revokeObjectURL(rawSrc);
            }

            setRawSrc(null);
            setEditing(false);

            onChange?.(croppedFile);
        } catch {
            message.error(t('image_uploader.crop_failed'));
        }
    }

    function handleCancel() {
        if (rawSrc?.startsWith('blob:')) {
            window.URL.revokeObjectURL(rawSrc);
        }

        setRawSrc(null);
        setEditing(false);
    }

    function handleClear() {
        if (previewSrc?.startsWith('blob:')) {
            window.URL.revokeObjectURL(previewSrc);
        }

        setPreviewSrc(null);
        onChange?.(null);
    }

    function handleSelectFromMedia(file) {
        if (previewSrc?.startsWith('blob:')) {
            window.URL.revokeObjectURL(previewSrc);
        }

        onChange?.(file.path);
        setPreviewSrc(resolveMediaUrl(file.path));
        setPickerOpen(false);
    }

    const minScale = naturalSize.w > 1
        ? Math.max(containerWidth / naturalSize.w, viewportHeight / naturalSize.h)
        : 0.5;

    return (
        <div className="image-resizer">
            {previewSrc && !editing ? (
                <div className="image-resizer__preview">
                    <img src={previewSrc} alt="preview" />
                    <div className="image-resizer__preview-actions">
                        <Button
                            size="small"
                            icon={<FontIcon name="media" />}
                            onClick={() => setPickerOpen(true)}
                        >
                            Media
                        </Button>
                        <Button
                            size="small"
                            icon={<FontIcon name="edit" />}
                            onClick={() => fileInputRef.current?.click()}
                        >
                            {t('image_uploader.change')}
                        </Button>
                        <Popconfirm {...deletePopconfirmProps(handleClear)}>
                            <Button
                                danger
                                size="small"
                                icon={<FontIcon name="delete" />}
                            >
                                {t('image_uploader.remove')}
                            </Button>
                        </Popconfirm>
                    </div>
                </div>
            ) : null}

            {editing && rawSrc ? (
                <div className="image-resizer__editor">
                    {loading ? (
                        <div className="image-resizer__loading">
                            <Spin />
                        </div>
                    ) : null}

                    <div className="image-resizer__presets">
                        {availablePresets.map((preset) => (
                            <Button
                                key={preset.key}
                                type={selectedPreset === preset.key ? 'primary' : 'default'}
                                size="small"
                                onClick={() => setSelectedPreset(preset.key)}
                            >
                                {preset.label}
                            </Button>
                        ))}
                    </div>

                    <div
                        ref={viewportRef}
                        className="image-resizer__viewport"
                        style={{ width: containerWidth, height: viewportHeight }}
                        onMouseMove={onMouseMove}
                        onMouseUp={onMouseUp}
                        onMouseLeave={onMouseUp}
                        onTouchMove={onTouchMove}
                        onTouchEnd={onMouseUp}
                    >
                        <img
                            ref={imgRef}
                            src={rawSrc}
                            alt="crop-source"
                            onLoad={handleImageLoad}
                            style={{
                                position: 'absolute',
                                left: offset.x,
                                top: offset.y,
                                width: naturalSize.w * scale,
                                height: naturalSize.h * scale,
                                cursor: dragging ? 'grabbing' : 'grab',
                                userSelect: 'none',
                                touchAction: 'none',
                            }}
                            draggable={false}
                            onMouseDown={onMouseDown}
                            onTouchStart={onTouchStart}
                        />

                        <div className="image-resizer__grid">
                            <div className="image-resizer__grid-line image-resizer__grid-line--h1" />
                            <div className="image-resizer__grid-line image-resizer__grid-line--h2" />
                            <div className="image-resizer__grid-line image-resizer__grid-line--v1" />
                            <div className="image-resizer__grid-line image-resizer__grid-line--v2" />
                        </div>

                        <div className="image-resizer__size-badge">
                            {outputDimensions.width} x {outputDimensions.height}
                        </div>
                    </div>

                    <div className="image-resizer__zoom">
                        <FontIcon name="zoom_out" />
                        <Slider
                            min={minScale}
                            max={5}
                            step={0.01}
                            value={scale}
                            onChange={handleZoomChange}
                            tooltip={{ formatter: (value) => `${Math.round(value * 100)}%` }}
                            style={{ flex: 1 }}
                        />
                        <FontIcon name="zoom_in" />
                    </div>

                    <Space className="image-resizer__actions">
                        <Button onClick={handleCancel}>
                            {t('common.cancel')}
                        </Button>
                        <Button type="primary" icon={<FontIcon name="crop" />} onClick={handleCrop}>
                            {t('image_uploader.crop')}
                        </Button>
                    </Space>
                </div>
            ) : null}

            {!editing ? (
                <>
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept={accept}
                        onChange={handleFileSelect}
                        style={{ display: 'none' }}
                    />

                    {!previewSrc ? (
                        <Space wrap>
                            <Button
                                icon={<FontIcon name="upload" />}
                                onClick={() => fileInputRef.current?.click()}
                            >
                                {t('image_uploader.select')}
                            </Button>
                            <Button
                                icon={<FontIcon name="media" />}
                                onClick={() => setPickerOpen(true)}
                            >
                                Media
                            </Button>
                        </Space>
                    ) : null}
                </>
            ) : null}

            <p className="image-resizer__hint">
                {t('image_uploader.hint_resize', {
                    width: outputDimensions.width,
                    height: outputDimensions.height,
                })}
            </p>

            <MediaPickerModal
                open={pickerOpen}
                title="Chon anh tu media"
                onCancel={() => setPickerOpen(false)}
                onSelect={handleSelectFromMedia}
            />
        </div>
    );
}
