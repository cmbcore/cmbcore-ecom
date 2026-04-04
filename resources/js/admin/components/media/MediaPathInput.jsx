import React, { useCallback, useMemo, useState } from 'react';
import { Button, Input, Space, Typography } from 'antd';
import FontIcon from '@admin/components/ui/FontIcon';
import { isImageAsset, resolveMediaUrl } from '@admin/utils/media';
import MediaPickerModal from './MediaPickerModal';

const { Link, Text } = Typography;

export default function MediaPathInput({
    value,
    onChange,
    placeholder = 'Nhập đường dẫn hoặc chọn từ media',
    modalTitle = 'Chọn file từ thư viện media',
}) {
    const [open, setOpen] = useState(false);

    const normalizedValue = String(value ?? '');
    const previewUrl = useMemo(() => resolveMediaUrl(normalizedValue), [normalizedValue]);

    const handleInputChange = useCallback((event) => {
        onChange?.(event.target.value);
    }, [onChange]);

    const handlePreview = useCallback(() => {
        if (!previewUrl) {
            return;
        }

        window.open(previewUrl, '_blank', 'noopener,noreferrer');
    }, [previewUrl]);

    const handleSelect = useCallback((file) => {
        onChange?.(file.path);
        setOpen(false);
    }, [onChange]);

    return (
        <div className="media-path-input">
            <Space.Compact style={{ width: '100%' }}>
                <Input
                    value={normalizedValue}
                    onChange={handleInputChange}
                    allowClear
                    placeholder={placeholder}
                />
                <Button icon={<FontIcon name="media" />} onClick={() => setOpen(true)}>
                    Thư viện
                </Button>
                {previewUrl ? (
                    <Button icon={<FontIcon name="preview" />} onClick={handlePreview} />
                ) : null}
            </Space.Compact>

            {previewUrl ? (
                isImageAsset(previewUrl) ? (
                    <div className="media-path-input__preview">
                        <img src={previewUrl} alt="" />
                    </div>
                ) : (
                    <Text type="secondary">
                        <Link href={previewUrl} target="_blank" rel="noreferrer">
                            {previewUrl}
                        </Link>
                    </Text>
                )
            ) : null}

            <MediaPickerModal
                open={open}
                title={modalTitle}
                onCancel={() => setOpen(false)}
                onSelect={handleSelect}
            />
        </div>
    );
}
