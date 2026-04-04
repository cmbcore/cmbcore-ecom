import React, { useRef, useState, useEffect, useCallback } from 'react';
import { Button, Popconfirm, Spin, Upload, message } from 'antd';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';
import { deletePopconfirmProps } from '@admin/utils/confirm';

// ─── Canvas crop helpers ────────────────────────────────────────────────────

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}

/**
 * Crop an HTMLImageElement to a square Blob using the Canvas API.
 *
 * @param {HTMLImageElement} imgEl
 * @param {{ x: number, y: number, size: number }} crop  – natural-pixel coords
 * @param {number} outputSize – px for the output square (default 800)
 * @returns {Promise<Blob>}
 */
function cropToBlob(imgEl, crop, outputSize = 800) {
    return new Promise((resolve, reject) => {
        const canvas = document.createElement('canvas');
        canvas.width = outputSize;
        canvas.height = outputSize;
        const ctx = canvas.getContext('2d');

        ctx.drawImage(
            imgEl,
            crop.x,
            crop.y,
            crop.size,
            crop.size,
            0,
            0,
            outputSize,
            outputSize,
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

// ─── CropOverlay sub-component ───────────────────────────────────────────────

/**
 * Inline crop overlay rendered **below** the upload button.
 * The user drags the image within a fixed-size square viewport to reframe,
 * and uses +/- buttons to zoom.
 */
function CropOverlay({ src, outputSize, onCrop, onCancel }) {
    const { t } = useLocale();
    const viewportRef = useRef(null);
    const [containerSize, setContainerSize] = useState(320);

    const imgRef = useRef(null);
    const [scale, setScale] = useState(1);
    const [offset, setOffset] = useState({ x: 0, y: 0 });
    const [dragging, setDragging] = useState(false);
    const [dragStart, setDragStart] = useState({ x: 0, y: 0, ox: 0, oy: 0 });
    const [loading, setLoading] = useState(true);
    const [naturalSize, setNaturalSize] = useState({ w: 1, h: 1 });

    useEffect(() => {
        const element = viewportRef.current;

        if (!element) {
            return undefined;
        }

        const updateSize = () => {
            const nextSize = Math.max(220, Math.min(320, Math.floor(element.offsetWidth || 320)));
            setContainerSize((current) => (current === nextSize ? current : nextSize));
        };

        updateSize();

        if (typeof window.ResizeObserver === 'function') {
            const observer = new window.ResizeObserver(() => updateSize());
            observer.observe(element);

            return () => observer.disconnect();
        }

        window.addEventListener('resize', updateSize);

        return () => window.removeEventListener('resize', updateSize);
    }, []);

    // On image load: fit square so image fills the viewport
    function handleImageLoad(e) {
        const { naturalWidth: w, naturalHeight: h } = e.target;
        setNaturalSize({ w, h });

        // Start with the image filling the square crop area (cover)
        const minSide = Math.min(w, h);
        const initialScale = containerSize / minSide;
        setScale(initialScale);

        // Center the image
        const displayW = w * initialScale;
        const displayH = h * initialScale;
        setOffset({
            x: (containerSize - displayW) / 2,
            y: (containerSize - displayH) / 2,
        });

        setLoading(false);
    }

    useEffect(() => {
        if (loading || naturalSize.w <= 1 || naturalSize.h <= 1) {
            return;
        }

        const minSide = Math.min(naturalSize.w, naturalSize.h);
        const initialScale = containerSize / minSide;
        const displayW = naturalSize.w * initialScale;
        const displayH = naturalSize.h * initialScale;

        setScale(initialScale);
        setOffset({
            x: (containerSize - displayW) / 2,
            y: (containerSize - displayH) / 2,
        });
    }, [containerSize, loading, naturalSize]);

    // Clamp offset so the crop area is always covered by the image
    const clampOffset = useCallback(
        (ox, oy, sc) => {
            const displayW = naturalSize.w * sc;
            const displayH = naturalSize.h * sc;

            return {
                x: clamp(ox, containerSize - displayW, 0),
                y: clamp(oy, containerSize - displayH, 0),
            };
        },
        [naturalSize, containerSize],
    );

    // ── Drag handlers
    function onMouseDown(e) {
        e.preventDefault();
        setDragging(true);
        setDragStart({ x: e.clientX, y: e.clientY, ox: offset.x, oy: offset.y });
    }

    function onMouseMove(e) {
        if (!dragging) return;
        const dx = e.clientX - dragStart.x;
        const dy = e.clientY - dragStart.y;
        setOffset(clampOffset(dragStart.ox + dx, dragStart.oy + dy, scale));
    }

    function onMouseUp() {
        setDragging(false);
    }

    // Touch equivalents
    function onTouchStart(e) {
        const t = e.touches[0];
        setDragging(true);
        setDragStart({ x: t.clientX, y: t.clientY, ox: offset.x, oy: offset.y });
    }

    function onTouchMove(e) {
        if (!dragging) return;
        const touch = e.touches[0];
        const dx = touch.clientX - dragStart.x;
        const dy = touch.clientY - dragStart.y;
        setOffset(clampOffset(dragStart.ox + dx, dragStart.oy + dy, scale));
    }

    function handleZoom(delta) {
        setScale((s) => {
            const next = clamp(s + delta, containerSize / Math.max(naturalSize.w, naturalSize.h), 4);
            // Re-clamp position at new scale
            setOffset((o) => clampOffset(o.x, o.y, next));
            return next;
        });
    }

    async function handleCrop() {
        if (!imgRef.current) return;

        // Convert display offset → natural image coords
        const cropX = -offset.x / scale;
        const cropY = -offset.y / scale;
        const cropSize = containerSize / scale;

        try {
            const blob = await cropToBlob(
                imgRef.current,
                { x: cropX, y: cropY, size: cropSize },
                outputSize,
            );
            onCrop(blob);
        } catch {
            message.error(t('image_uploader.crop_failed'));
        }
    }

    return (
        <div className="single-image-uploader__crop-wrapper">
            {loading && (
                <div className="single-image-uploader__crop-loading">
                    <Spin />
                </div>
            )}

            {/* Viewport – square clip area */}
            <div
                ref={viewportRef}
                className="single-image-uploader__crop-viewport"
                style={{ width: containerSize, height: containerSize }}
                onMouseMove={onMouseMove}
                onMouseUp={onMouseUp}
                onMouseLeave={onMouseUp}
                onTouchMove={onTouchMove}
                onTouchEnd={onMouseUp}
            >
                <img
                    ref={imgRef}
                    src={src}
                    alt="crop-preview"
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

                {/* Square crop guide overlay */}
                <div className="single-image-uploader__crop-guide" />
            </div>

            {/* Zoom controls */}
            <div className="single-image-uploader__crop-controls">
                <Button size="small" icon={<FontIcon name="zoom-out" />} onClick={() => handleZoom(-0.1)}>
                    {t('image_uploader.zoom_out')}
                </Button>
                <Button size="small" icon={<FontIcon name="zoom-in" />} onClick={() => handleZoom(0.1)}>
                    {t('image_uploader.zoom_in')}
                </Button>
                <Button size="small" onClick={onCancel}>
                    {t('common.cancel')}
                </Button>
                <Button type="primary" size="small" icon={<FontIcon name="crop" />} onClick={handleCrop}>
                    {t('image_uploader.crop')}
                </Button>
            </div>
        </div>
    );
}

// ─── Main export ─────────────────────────────────────────────────────────────

/**
 * SingleImageUploader
 *
 * Props:
 *   value        – current value (File object or null/undefined)
 *   onChange     – called with cropped File when crop is confirmed, or null when cleared
 *   existingUrl  – URL of already-stored image to show as preview on edit forms
 *   size         – output square resolution in px (default 800)
 *   accept       – accepted MIME types (default 'image/*')
 */
export default function SingleImageUploader({
    value,
    onChange,
    existingUrl,
    size = 800,
    accept = 'image/*',
}) {
    const { t } = useLocale();

    // rawSrc: object-URL for the file chosen by the user (for the crop overlay)
    const [rawSrc, setRawSrc] = useState(null);
    // previewSrc: object-URL for the cropped result, or existingUrl
    const [previewSrc, setPreviewSrc] = useState(null);
    const [cropping, setCropping] = useState(false);

    // Keep preview in sync when the field value or existingUrl changes.
    useEffect(() => {
        if (value instanceof File) {
            const objectUrl = window.URL.createObjectURL(value);
            setPreviewSrc((current) => {
                if (current?.startsWith('blob:')) {
                    window.URL.revokeObjectURL(current);
                }

                return objectUrl;
            });

            return undefined;
        }

        if (typeof value === 'string' && value.trim() !== '') {
            setPreviewSrc(value);

            return undefined;
        }

        if (value === null) {
            setPreviewSrc(null);

            return undefined;
        }

        setPreviewSrc(existingUrl ?? null);

        return undefined;
    }, [existingUrl, value]);

    // Clean up object URLs on unmount
    const rawSrcRef = useRef(null);
    const previewSrcRef = useRef(null);
    useEffect(() => {
        rawSrcRef.current = rawSrc;
        previewSrcRef.current = previewSrc;
    });
    useEffect(() => {
        return () => {
            if (rawSrcRef.current?.startsWith('blob:')) window.URL.revokeObjectURL(rawSrcRef.current);
            if (previewSrcRef.current?.startsWith('blob:')) window.URL.revokeObjectURL(previewSrcRef.current);
        };
    }, []);

    function handleBeforeUpload(file) {
        if (!file.type.startsWith('image/')) {
            message.error(t('image_uploader.invalid_type'));
            return Upload.LIST_IGNORE;
        }

        // Open the crop overlay
        const objectUrl = window.URL.createObjectURL(file);
        if (rawSrc?.startsWith('blob:')) window.URL.revokeObjectURL(rawSrc);
        setRawSrc(objectUrl);
        setCropping(true);

        return Upload.LIST_IGNORE; // prevent default upload
    }

    function handleCropConfirm(blob) {
        const croppedFile = new File([blob], 'image.jpg', { type: 'image/jpeg' });

        if (previewSrc?.startsWith('blob:')) window.URL.revokeObjectURL(previewSrc);
        const newPreview = window.URL.createObjectURL(blob);
        setPreviewSrc(newPreview);

        // Clean up raw src
        if (rawSrc?.startsWith('blob:')) window.URL.revokeObjectURL(rawSrc);
        setRawSrc(null);
        setCropping(false);

        onChange?.(croppedFile);
    }

    function handleCropCancel() {
        if (rawSrc?.startsWith('blob:')) window.URL.revokeObjectURL(rawSrc);
        setRawSrc(null);
        setCropping(false);
    }

    function handleClear() {
        if (previewSrc?.startsWith('blob:')) window.URL.revokeObjectURL(previewSrc);
        setPreviewSrc(null);
        onChange?.(null);
    }

    return (
        <div className="single-image-uploader">
            {/* Preview thumbnail */}
            {previewSrc && !cropping && (
                <div className="single-image-uploader__preview">
                    <img src={previewSrc} alt="preview" />
                    <Popconfirm {...deletePopconfirmProps(handleClear)}>
                        <Button
                            danger
                            size="small"
                            className="single-image-uploader__clear"
                            icon={<FontIcon name="delete" />}
                        >
                            {t('image_uploader.remove')}
                        </Button>
                    </Popconfirm>
                </div>
            )}

            {/* Crop overlay */}
            {cropping && rawSrc && (
                <CropOverlay
                    src={rawSrc}
                    outputSize={size}
                    onCrop={handleCropConfirm}
                    onCancel={handleCropCancel}
                />
            )}

            {/* Upload button – only shown when not cropping */}
            {!cropping && (
                <Upload
                    beforeUpload={handleBeforeUpload}
                    showUploadList={false}
                    accept={accept}
                    multiple={false}
                >
                    <Button icon={<FontIcon name="upload" />}>
                        {previewSrc
                            ? t('image_uploader.change')
                            : t('image_uploader.select')}
                    </Button>
                </Upload>
            )}

            <p className="single-image-uploader__hint">
                {t('image_uploader.hint', { size })}
            </p>
        </div>
    );
}
