import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
    Alert,
    Badge,
    Button,
    Card,
    Col,
    DatePicker,
    Divider,
    Form,
    InputNumber,
    Modal,
    Popconfirm,
    Row,
    Space,
    Switch,
    Table,
    Tag,
    Tooltip,
    Typography,
    message,
} from 'antd';
import dayjs from 'dayjs';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import api from '@admin/services/api';
import { deletePopconfirmProps } from '@admin/utils/confirm';

const { Text } = Typography;

// ─── SKU Picker ──────────────────────────────────────────────────────────────

function SkuPicker({ skuOptions, selectedSkuIds, onToggle }) {
    const [search, setSearch] = useState('');

    const grouped = useMemo(() => {
        const map = {};
        skuOptions.forEach((sku) => {
            if (!map[sku.product_id]) {
                map[sku.product_id] = { product_name: sku.product_name, skus: [] };
            }
            map[sku.product_id].skus.push(sku);
        });
        return Object.values(map);
    }, [skuOptions]);

    const filtered = useMemo(() => {
        const q = search.toLowerCase();
        if (!q) return grouped;
        return grouped
            .map((g) => ({
                ...g,
                skus: g.skus.filter(
                    (s) =>
                        g.product_name?.toLowerCase().includes(q) ||
                        s.sku_name?.toLowerCase().includes(q) ||
                        s.sku_code?.toLowerCase().includes(q),
                ),
            }))
            .filter((g) => g.skus.length > 0);
    }, [grouped, search]);

    return (
        <div>
            <input
                type="text"
                placeholder="Tìm sản phẩm / SKU..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                style={{
                    width: '100%',
                    padding: '6px 10px',
                    marginBottom: 12,
                    border: '1px solid #d9d9d9',
                    borderRadius: 6,
                    fontSize: 13,
                    outline: 'none',
                }}
            />
            <div style={{ maxHeight: 360, overflowY: 'auto', paddingRight: 4 }}>
                {filtered.map((group) => (
                    <div key={group.product_name} style={{ marginBottom: 8 }}>
                        <Text strong style={{ fontSize: 12, color: '#8c8c8c', textTransform: 'uppercase' }}>
                            {group.product_name}
                        </Text>
                        <div style={{ marginTop: 4, display: 'flex', flexWrap: 'wrap', gap: 6 }}>
                            {group.skus.map((sku) => {
                                const isSelected = selectedSkuIds.includes(sku.sku_id);
                                const isBusy = sku.is_busy && !isSelected;

                                return (
                                    <Tooltip
                                        key={sku.sku_id}
                                        title={
                                            isBusy
                                                ? 'SKU này đang có trong một flash sale khác đang chạy'
                                                : `${sku.sku_code} · Tồn: ${sku.stock} · Giá: ${sku.price.toLocaleString('vi-VN')}₫`
                                        }
                                    >
                                        <Tag
                                            onClick={isBusy ? undefined : () => onToggle(sku)}
                                            color={isSelected ? 'blue' : isBusy ? 'default' : undefined}
                                            style={{
                                                cursor: isBusy ? 'not-allowed' : 'pointer',
                                                opacity: isBusy ? 0.45 : 1,
                                                userSelect: 'none',
                                                border: isSelected ? '1.5px solid #1677ff' : undefined,
                                                padding: '3px 10px',
                                                fontSize: 13,
                                            }}
                                        >
                                            {isBusy && <FontIcon name="lock" style={{ marginRight: 4, fontSize: 11 }} />}
                                            {isSelected && <FontIcon name="check" style={{ marginRight: 4, fontSize: 11 }} />}
                                            {sku.sku_name || sku.sku_code}
                                        </Tag>
                                    </Tooltip>
                                );
                            })}
                        </div>
                        <Divider style={{ margin: '8px 0' }} />
                    </div>
                ))}
                {filtered.length === 0 && (
                    <Alert message="Không tìm thấy SKU phù hợp." type="info" showIcon />
                )}
            </div>
        </div>
    );
}

// ─── Flash Sale Item Row ──────────────────────────────────────────────────────

function FlashSaleItemRow({ item, skuOptions, onRemove, onSalePriceChange, onQuantityLimitChange }) {
    const sku = skuOptions.find((s) => s.sku_id === item.product_sku_id);
    if (!sku) return null;

    return (
        <Card
            size="small"
            style={{ marginBottom: 8 }}
            styles={{ body: { padding: '10px 14px' } }}
        >
            <Row gutter={[12, 8]} align="middle">
                <Col xs={24} sm={10}>
                    <Text strong style={{ fontSize: 13 }}>{sku.product_name}</Text>
                    <br />
                    <Text type="secondary" style={{ fontSize: 12 }}>{sku.sku_name} · {sku.sku_code}</Text>
                </Col>
                <Col xs={12} sm={5}>
                    <div style={{ fontSize: 11, color: '#8c8c8c', marginBottom: 2 }}>Giá gốc</div>
                    <Text delete type="secondary">{sku.price.toLocaleString('vi-VN')}₫</Text>
                </Col>
                <Col xs={12} sm={4}>
                    <div style={{ fontSize: 11, color: '#8c8c8c', marginBottom: 2 }}>Giá sale</div>
                    <InputNumber
                        size="small"
                        min={0}
                        max={sku.price}
                        value={item.sale_price}
                        onChange={onSalePriceChange}
                        style={{ width: '100%' }}
                        formatter={(v) => `${v}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                        parser={(v) => v.replace(/,/g, '')}
                    />
                </Col>
                <Col xs={12} sm={4}>
                    <div style={{ fontSize: 11, color: '#8c8c8c', marginBottom: 2 }}>Giới hạn SL</div>
                    <InputNumber
                        size="small"
                        min={1}
                        value={item.quantity_limit ?? null}
                        onChange={onQuantityLimitChange}
                        placeholder="∞"
                        style={{ width: '100%' }}
                    />
                </Col>
                <Col xs={12} sm={1} style={{ textAlign: 'right' }}>
                    <Button
                        danger
                        type="text"
                        size="small"
                        icon={<FontIcon name="close" />}
                        onClick={onRemove}
                    />
                </Col>
            </Row>
        </Card>
    );
}

// ─── Flash Sale Modal ─────────────────────────────────────────────────────────

function FlashSaleModal({ open, initialValues, onCancel, onSubmit }) {
    const [form] = Form.useForm();
    const [skuOptions, setSkuOptions] = useState([]);
    const [loadingSkus, setLoadingSkus] = useState(false);
    const [items, setItems] = useState([]);

    useEffect(() => {
        if (!open) {
            form.resetFields();
            setItems([]);
            return;
        }

        const saleId = initialValues?.id ?? null;

        // Load SKU options
        setLoadingSkus(true);
        api.get('/flash-sales/sku-options', { params: saleId ? { exclude_sale_id: saleId } : {} })
            .then((res) => setSkuOptions(res.data.data ?? []))
            .catch(() => message.error('Không tải được danh sách SKU.'))
            .finally(() => setLoadingSkus(false));

        // Set form fields
        if (initialValues) {
            form.setFieldsValue({
                id: initialValues.id,
                title: initialValues.title,
                starts_at: initialValues.starts_at ? dayjs(initialValues.starts_at) : null,
                ends_at: initialValues.ends_at ? dayjs(initialValues.ends_at) : null,
                is_active: initialValues.is_active ?? true,
            });
            setItems(
                (initialValues.items ?? []).map((it) => ({
                    id: it.id,
                    product_sku_id: it.product_sku_id,
                    sale_price: it.sale_price ?? 0,
                    quantity_limit: it.quantity_limit ?? null,
                    sold_quantity: it.sold_quantity ?? 0,
                })),
            );
        } else {
            form.setFieldsValue({ is_active: true });
            setItems([]);
        }
    }, [form, initialValues, open]);

    const selectedSkuIds = items.map((i) => i.product_sku_id);

    function handleToggleSku(sku) {
        setItems((prev) => {
            const exists = prev.find((i) => i.product_sku_id === sku.sku_id);
            if (exists) {
                return prev.filter((i) => i.product_sku_id !== sku.sku_id);
            }
            return [
                ...prev,
                {
                    product_sku_id: sku.sku_id,
                    sale_price: Math.floor(sku.price * 0.8), // default 20% off
                    quantity_limit: null,
                    sold_quantity: 0,
                },
            ];
        });
    }

    function handleSubmit() {
        form
            .validateFields()
            .then((values) => {
                if (items.length === 0) {
                    message.warning('Vui lòng chọn ít nhất một sản phẩm.');
                    return;
                }
                onSubmit({
                    id: values.id,
                    title: values.title,
                    starts_at: values.starts_at?.toISOString(),
                    ends_at: values.ends_at?.toISOString(),
                    is_active: values.is_active ?? true,
                    items,
                });
            })
            .catch(() => {});
    }

    return (
        <Modal
            open={open}
            title={initialValues?.id ? 'Sửa Flash Sale' : 'Tạo Flash Sale'}
            onCancel={onCancel}
            onOk={handleSubmit}
            okText={initialValues?.id ? 'Lưu thay đổi' : 'Tạo Flash Sale'}
            cancelText="Hủy"
            destroyOnHidden
            width={920}
            styles={{ body: { padding: '16px 24px' } }}
        >
            <Form form={form} layout="vertical">
                <Form.Item name="id" hidden><input /></Form.Item>

                <Row gutter={16}>
                    <Col span={24}>
                        <Form.Item name="title" label="Tiêu đề chương trình" rules={[{ required: true, message: 'Vui lòng nhập tiêu đề.' }]}>
                            <input
                                className="ant-input"
                                placeholder="VD: Flash Deal 0h - 6h"
                                style={{ width: '100%', padding: '6px 11px', border: '1px solid #d9d9d9', borderRadius: 6 }}
                            />
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item name="starts_at" label="Bắt đầu" rules={[{ required: true, message: 'Chọn thời gian bắt đầu.' }]}>
                            <DatePicker showTime style={{ width: '100%' }} placeholder="Chọn ngày bắt đầu" />
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item name="ends_at" label="Kết thúc" rules={[{ required: true, message: 'Chọn thời gian kết thúc.' }]}>
                            <DatePicker showTime style={{ width: '100%' }} placeholder="Chọn ngày kết thúc" />
                        </Form.Item>
                    </Col>
                    <Col span={24}>
                        <Form.Item name="is_active" label="Đang hoạt động" valuePropName="checked">
                            <Switch />
                        </Form.Item>
                    </Col>
                </Row>
            </Form>

            <Divider style={{ margin: '8px 0 16px' }} />

            <Row gutter={24}>
                {/* Left: SKU picker */}
                <Col xs={24} md={12}>
                    <Text strong>Chọn sản phẩm tham gia</Text>
                    <div style={{ color: '#8c8c8c', fontSize: 12, marginBottom: 10 }}>
                        Mờ = đang có flash sale khác · Click để thêm/bỏ
                    </div>
                    {loadingSkus ? (
                        <div style={{ textAlign: 'center', padding: 32 }}>Đang tải...</div>
                    ) : (
                        <SkuPicker
                            skuOptions={skuOptions}
                            selectedSkuIds={selectedSkuIds}
                            onToggle={handleToggleSku}
                        />
                    )}
                </Col>

                {/* Right: Selected items config */}
                <Col xs={24} md={12}>
                    <Space style={{ marginBottom: 10 }} align="center">
                        <Text strong>Sản phẩm đã chọn</Text>
                        <Badge count={items.length} color="#1677ff" />
                    </Space>
                    {items.length === 0 ? (
                        <Alert
                            message="Chưa chọn sản phẩm nào"
                            description="Chọn SKU từ danh sách bên trái để thêm vào flash sale."
                            type="info"
                            showIcon
                        />
                    ) : (
                        <div style={{ maxHeight: 400, overflowY: 'auto' }}>
                            {items.map((item) => (
                                <FlashSaleItemRow
                                    key={item.product_sku_id}
                                    item={item}
                                    skuOptions={skuOptions}
                                    onRemove={() => setItems((prev) => prev.filter((i) => i.product_sku_id !== item.product_sku_id))}
                                    onSalePriceChange={(val) =>
                                        setItems((prev) =>
                                            prev.map((i) =>
                                                i.product_sku_id === item.product_sku_id ? { ...i, sale_price: val ?? 0 } : i,
                                            ),
                                        )
                                    }
                                    onQuantityLimitChange={(val) =>
                                        setItems((prev) =>
                                            prev.map((i) =>
                                                i.product_sku_id === item.product_sku_id ? { ...i, quantity_limit: val ?? null } : i,
                                            ),
                                        )
                                    }
                                />
                            ))}
                        </div>
                    )}
                </Col>
            </Row>
        </Modal>
    );
}

// ─── Flash Sale List Page ─────────────────────────────────────────────────────

export default function FlashSaleList() {
    const [loading, setLoading] = useState(true);
    const [flashSales, setFlashSales] = useState([]);
    const [modalState, setModalState] = useState({ open: false, initialValues: null });

    const fetchItems = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/flash-sales');
            setFlashSales(response.data.data ?? []);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được danh sách flash sale.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchItems();
    }, [fetchItems]);

    async function handleSubmit(values) {
        try {
            await api.post('/flash-sales', values);
            setModalState({ open: false, initialValues: null });
            message.success('Đã lưu flash sale.');
            fetchItems();
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không lưu được flash sale.');
        }
    }

    async function handleDelete(id) {
        try {
            await api.delete(`/flash-sales/${id}`);
            message.success('Đã xóa flash sale.');
            fetchItems();
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không xóa được flash sale.');
        }
    }

    function statusTag(sale) {
        const now = Date.now();
        const start = new Date(sale.starts_at).getTime();
        const end = new Date(sale.ends_at).getTime();

        if (!sale.is_active) return <Tag color="default">Tắt</Tag>;
        if (now < start) return <Tag color="blue">Sắp diễn ra</Tag>;
        if (now > end) return <Tag color="red">Đã kết thúc</Tag>;
        return <Tag color="green">Đang chạy</Tag>;
    }

    return (
        <div>
            <PageHeader
                title="Flash Sale"
                description="Chương trình giảm giá theo thời gian và giới hạn số lượng."
                extra={[
                    { label: 'Tải lại', icon: <FontIcon name="refresh" />, onClick: fetchItems },
                    {
                        label: 'Tạo Flash Sale',
                        icon: <FontIcon name="add" />,
                        type: 'primary',
                        onClick: () => setModalState({ open: true, initialValues: null }),
                    },
                ]}
            />

            <Table
                rowKey="id"
                loading={loading}
                dataSource={flashSales}
                pagination={false}
                columns={[
                    {
                        title: 'Tiêu đề',
                        dataIndex: 'title',
                        render: (text, sale) => (
                            <Space direction="vertical" size={2}>
                                <Text strong>{text}</Text>
                                <Text type="secondary" style={{ fontSize: 12 }}>
                                    {sale.items?.length ?? 0} sản phẩm ·{' '}
                                    {(sale.items ?? []).reduce((t, i) => t + (i.sold_quantity ?? 0), 0)} đã bán
                                </Text>
                            </Space>
                        ),
                    },
                    {
                        title: 'Bắt đầu',
                        dataIndex: 'starts_at',
                        render: (v) => v ? dayjs(v).format('DD/MM/YYYY HH:mm') : '-',
                        width: 160,
                    },
                    {
                        title: 'Kết thúc',
                        dataIndex: 'ends_at',
                        render: (v) => v ? dayjs(v).format('DD/MM/YYYY HH:mm') : '-',
                        width: 160,
                    },
                    {
                        title: 'Trạng thái',
                        key: 'status',
                        width: 130,
                        render: (_, sale) => statusTag(sale),
                    },
                    {
                        title: 'Thao tác',
                        key: 'actions',
                        width: 130,
                        render: (_, sale) => (
                            <Space size={4}>
                                <Button
                                    size="small"
                                    icon={<FontIcon name="edit" />}
                                    onClick={() => setModalState({ open: true, initialValues: sale })}
                                >
                                    Sửa
                                </Button>
                                <Popconfirm {...deletePopconfirmProps(() => handleDelete(sale.id))}>
                                    <Button size="small" danger icon={<FontIcon name="delete" />}>
                                        Xóa
                                    </Button>
                                </Popconfirm>
                            </Space>
                        ),
                    },
                ]}
            />

            <FlashSaleModal
                open={modalState.open}
                initialValues={modalState.initialValues}
                onCancel={() => setModalState({ open: false, initialValues: null })}
                onSubmit={handleSubmit}
            />
        </div>
    );
}
