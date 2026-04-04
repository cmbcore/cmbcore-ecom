import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Button, Card, InputNumber, Popconfirm, Select, Space, Table, Tag, message } from 'antd';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import api from '@admin/services/api';
import { deletePopconfirmProps } from '@admin/utils/confirm';

const EMPTY_PAYLOAD = {
    low_stock: [],
    movements: [],
    sku_options: [],
};

function normalizeManagedItem(item) {
    return {
        id: Number(item.id ?? item.value),
        product_name: item.product_name ?? '',
        sku_name: item.sku_name ?? '',
        sku_code: item.sku_code ?? '',
        stock_quantity: Number(item.stock_quantity ?? 0),
        low_stock_threshold: Number(item.low_stock_threshold ?? 0),
    };
}

const MOVEMENT_TYPE_LABELS = {
    in: 'Nhập kho',
    out: 'Xuất kho',
    adjustment: 'Điều chỉnh',
    return: 'Hoàn trả',
};

export default function InventoryDashboard() {
    const [loading, setLoading] = useState(true);
    const [payload, setPayload] = useState(EMPTY_PAYLOAD);
    const [drafts, setDrafts] = useState({});
    const [managedItems, setManagedItems] = useState([]);
    const [selectedSkuId, setSelectedSkuId] = useState();

    const skuOptionMap = useMemo(() => new Map(
        (payload.sku_options ?? []).map((item) => [Number(item.value), item]),
    ), [payload.sku_options]);

    const fetchPayload = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/inventory');
            const nextPayload = response.data.data ?? EMPTY_PAYLOAD;
            setPayload(nextPayload);
            setManagedItems((nextPayload.low_stock ?? []).map((item) => normalizeManagedItem(item)));
            setDrafts({});
            setSelectedSkuId(undefined);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được dữ liệu tồn kho.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchPayload();
    }, [fetchPayload]);

    const tableData = useMemo(() => managedItems.map((item) => {
        const draft = drafts[item.id] ?? {};
        const stockQuantity = draft.stock_quantity ?? item.stock_quantity;
        const lowStockThreshold = draft.low_stock_threshold ?? item.low_stock_threshold;

        return {
            ...item,
            stock_quantity: stockQuantity,
            low_stock_threshold: lowStockThreshold,
            is_low_stock: Number(stockQuantity) <= Number(lowStockThreshold),
        };
    }), [drafts, managedItems]);

    function updateDraft(skuId, key, value) {
        setDrafts((current) => ({
            ...current,
            [skuId]: {
                ...(current[skuId] ?? {}),
                [key]: value,
            },
        }));
    }

    function handleAddSku() {
        const skuId = Number(selectedSkuId);

        if (!skuId) {
            return;
        }

        if (managedItems.some((item) => item.id === skuId)) {
            message.info('SKU này đã có trong danh sách cập nhật.');
            setSelectedSkuId(undefined);
            return;
        }

        const selectedSku = skuOptionMap.get(skuId);

        if (!selectedSku) {
            message.error('Không tìm thấy SKU để thêm vào tồn kho.');
            return;
        }

        setManagedItems((current) => [...current, normalizeManagedItem(selectedSku)]);
        setSelectedSkuId(undefined);
    }

    function handleRemoveSku(skuId) {
        setManagedItems((current) => current.filter((item) => item.id !== skuId));
        setDrafts((current) => {
            const next = { ...current };
            delete next[skuId];
            return next;
        });
    }

    async function saveBulk() {
        if (managedItems.length === 0) {
            message.warning('Chưa có SKU nào để cập nhật tồn kho.');
            return;
        }

        try {
            const items = managedItems.map((item) => ({
                sku_id: item.id,
                stock_quantity: drafts[item.id]?.stock_quantity ?? item.stock_quantity,
                low_stock_threshold: drafts[item.id]?.low_stock_threshold ?? item.low_stock_threshold,
            }));

            await api.post('/inventory/bulk-update', { items });
            message.success('Đã cập nhật tồn kho.');
            fetchPayload();
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không lưu được tồn kho.');
        }
    }

    return (
        <div>
            <PageHeader
                title="Tồn kho"
                description="Thêm SKU bất kỳ để cập nhật tồn kho, ngưỡng cảnh báo và theo dõi lịch sử xuất nhập."
                extra={[
                    { label: 'Tải lại', icon: <FontIcon name="refresh" />, onClick: fetchPayload },
                    { label: 'Lưu cập nhật', icon: <FontIcon name="save" />, type: 'primary', onClick: saveBulk },
                ]}
            />

            <Space direction="vertical" size={24} style={{ width: '100%' }}>
                <Card bordered={false} title="Cập nhật tồn kho">
                    <Space wrap style={{ marginBottom: 16 }}>
                        <Select
                            showSearch
                            allowClear
                            style={{ minWidth: 360 }}
                            placeholder="Chọn SKU để thêm vào bảng cập nhật..."
                            optionFilterProp="label"
                            value={selectedSkuId}
                            options={payload.sku_options ?? []}
                            onChange={setSelectedSkuId}
                        />
                        <Button type="primary" icon={<FontIcon name="create" />} onClick={handleAddSku}>
                            Thêm SKU
                        </Button>
                    </Space>

                    <Table
                        rowKey="id"
                        loading={loading}
                        dataSource={tableData}
                        pagination={false}
                        locale={{ emptyText: 'Chưa có SKU nào được chọn để cập nhật tồn kho.' }}
                        columns={[
                            { title: 'Sản phẩm', dataIndex: 'product_name' },
                            { title: 'Tên SKU', dataIndex: 'sku_name' },
                            { title: 'Mã SKU', dataIndex: 'sku_code' },
                            {
                                title: 'Tồn kho',
                                render: (_, item) => (
                                    <InputNumber
                                        min={0}
                                        value={item.stock_quantity}
                                        onChange={(value) => updateDraft(item.id, 'stock_quantity', value ?? 0)}
                                    />
                                ),
                            },
                            {
                                title: 'Ngưỡng cảnh báo',
                                render: (_, item) => (
                                    <InputNumber
                                        min={0}
                                        value={item.low_stock_threshold}
                                        onChange={(value) => updateDraft(item.id, 'low_stock_threshold', value ?? 0)}
                                    />
                                ),
                            },
                            {
                                title: 'Tình trạng',
                                render: (_, item) => (
                                    <Tag color={item.is_low_stock ? 'error' : 'success'}>
                                        {item.is_low_stock ? 'Sắp hết' : 'Ổn định'}
                                    </Tag>
                                ),
                            },
                            {
                                title: 'Thao tác',
                                render: (_, item) => (
                                    <Popconfirm
                                        {...deletePopconfirmProps(
                                            () => handleRemoveSku(item.id),
                                            {
                                                title: 'Bỏ SKU khỏi bảng cập nhật?',
                                                description: 'Dòng này chỉ bị gỡ khỏi màn hình, không xóa dữ liệu SKU.',
                                            },
                                        )}
                                    >
                                        <Button size="small" danger icon={<FontIcon name="delete" />}>
                                            Xóa dòng
                                        </Button>
                                    </Popconfirm>
                                ),
                            },
                        ]}
                    />
                </Card>

                <Card bordered={false} title="Lịch sử xuất nhập kho">
                    <Table
                        rowKey="id"
                        loading={loading}
                        dataSource={payload.movements}
                        pagination={false}
                        locale={{ emptyText: 'Chưa có lịch sử xuất nhập.' }}
                        columns={[
                            { title: 'Sản phẩm', dataIndex: 'product_name' },
                            { title: 'SKU', dataIndex: 'sku_name' },
                            { title: 'Loại', dataIndex: 'type', render: (v) => MOVEMENT_TYPE_LABELS[v] ?? v },
                            { title: 'Số lượng', dataIndex: 'quantity' },
                            { title: 'Mã tham chiếu', dataIndex: 'reference' },
                            { title: 'Ghi chú', dataIndex: 'note' },
                        ]}
                    />
                </Card>
            </Space>
        </div>
    );
}
