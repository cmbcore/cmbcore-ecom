import React from 'react';
import { Button, Card, Upload, message } from 'antd';
import PageHeader from '@admin/components/ui/PageHeader';
import api from '@admin/services/api';

export default function ImportExportPage() {
    return (
        <div>
            <PageHeader
                title="Import / Export dữ liệu"
                description="Trích xuất sản phẩm ra CSV và nhập nhanh dữ liệu cơ bản."
            />
            <Card bordered={false}>
                <Button href="/api/admin/import-export/products/export" target="_blank" style={{ marginBottom: 16 }}>
                    Xuất CSV
                </Button>

                <Upload
                    showUploadList={false}
                    customRequest={async ({ file, onSuccess, onError }) => {
                        try {
                            const formData = new FormData();
                            formData.append('file', file);
                            const response = await api.post('/import-export/products/import', formData, {
                                headers: { 'Content-Type': 'multipart/form-data' },
                            });
                            onSuccess?.({});
                            message.success(response.data.message ?? 'Đã import dữ liệu CSV.');
                        } catch (error) {
                            onError?.(error);
                            message.error(error.response?.data?.message ?? 'Không import được CSV.');
                        }
                    }}
                >
                    <Button type="primary">Nhập CSV</Button>
                </Upload>
            </Card>
        </div>
    );
}
