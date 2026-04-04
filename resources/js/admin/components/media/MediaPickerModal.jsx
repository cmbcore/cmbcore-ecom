import React, { useCallback, useEffect, useState } from 'react';
import { Button, Modal } from 'antd';
import MediaBrowser from './MediaBrowser';

export default function MediaPickerModal({
    open,
    title = 'Chọn file từ thư viện media',
    onCancel,
    onSelect,
}) {
    const [selectedFile, setSelectedFile] = useState(null);

    useEffect(() => {
        if (!open) {
            setSelectedFile(null);
        }
    }, [open]);

    const handleClose = useCallback(() => {
        setSelectedFile(null);
        onCancel?.();
    }, [onCancel]);

    const handleSelect = useCallback((file) => {
        if (!file) {
            return;
        }

        setSelectedFile(file);
        onSelect?.(file);
    }, [onSelect]);

    return (
        <Modal
            open={open}
            title={title}
            onCancel={handleClose}
            destroyOnHidden
            width={1240}
            footer={[
                <Button key="cancel" onClick={handleClose}>Hủy</Button>,
                (
                    <Button
                        key="select"
                        type="primary"
                        disabled={!selectedFile}
                        onClick={() => handleSelect(selectedFile)}
                    >
                        Chọn file
                    </Button>
                ),
            ]}
        >
            <MediaBrowser
                picker
                height={620}
                onSelect={handleSelect}
                onSelectionChange={setSelectedFile}
            />
        </Modal>
    );
}
