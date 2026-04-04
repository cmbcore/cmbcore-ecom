import React, { useCallback, useEffect, useState } from 'react';
import {
    Button, Checkbox, Divider, Form, Input, Modal, Select,
    Space, Switch, Tabs, Tooltip, Typography, message,
} from 'antd';
import FontIcon from '@admin/components/ui/FontIcon';
import api from '@admin/services/api';

const { Text } = Typography;
const { TabPane } = Tabs;

// ─── Field type options ───────────────────────────────────────────────────────
const FIELD_TYPES = [
    { value: 'text',     label: 'Văn bản ngắn' },
    { value: 'email',    label: 'Email' },
    { value: 'phone',    label: 'Số điện thoại' },
    { value: 'number',   label: 'Số' },
    { value: 'textarea', label: 'Văn bản dài' },
    { value: 'select',   label: 'Dropdown' },
    { value: 'radio',    label: 'Radio buttons' },
    { value: 'checkbox', label: 'Checkbox' },
];

// ─── Unique ID generator ──────────────────────────────────────────────────────
let _uid = 0;
function uid() { return `field_${++_uid}_${Date.now()}`; }

function defaultField() {
    return { id: uid(), type: 'text', label: '', name: '', placeholder: '', required: false, width: 'full', options: [] };
}

// ─── Single Field Editor ─────────────────────────────────────────────────────
function FieldEditor({ field, index, onChange, onRemove, onMoveUp, onMoveDown, isFirst, isLast }) {
    const update = (key, value) => onChange({ ...field, [key]: value });

    const hasOptions = ['select', 'radio'].includes(field.type);

    return (
        <div style={{
            border: '1px solid #e5e7eb', borderRadius: 8, padding: '16px 16px 12px',
            marginBottom: 12, background: '#fafafa', position: 'relative',
        }}>
            {/* Header */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 }}>
                <Text strong style={{ fontSize: 13 }}>
                    <span style={{ background: '#1677ff', color: '#fff', borderRadius: 4, padding: '1px 7px', fontSize: 12, marginRight: 8 }}>
                        {index + 1}
                    </span>
                    {field.label || 'Trường chưa đặt tên'}
                </Text>
                <Space size={4}>
                    <Tooltip title="Di chuyển lên">
                        <Button size="small" icon={<FontIcon name="move_up" />} disabled={isFirst} onClick={onMoveUp} />
                    </Tooltip>
                    <Tooltip title="Di chuyển xuống">
                        <Button size="small" icon={<FontIcon name="move_down" />} disabled={isLast} onClick={onMoveDown} />
                    </Tooltip>
                    <Button size="small" danger icon={<FontIcon name="delete" />} onClick={onRemove} />
                </Space>
            </div>

            {/* Row 1: type + label + name */}
            <div style={{ display: 'grid', gridTemplateColumns: '140px 1fr 160px', gap: 10, marginBottom: 10 }}>
                <div>
                    <label style={{ fontSize: 12, color: '#6b7280', display: 'block', marginBottom: 4 }}>Loại trường</label>
                    <Select
                        size="small"
                        value={field.type}
                        onChange={(v) => update('type', v)}
                        options={FIELD_TYPES}
                        style={{ width: '100%' }}
                    />
                </div>
                <div>
                    <label style={{ fontSize: 12, color: '#6b7280', display: 'block', marginBottom: 4 }}>Nhãn hiển thị *</label>
                    <Input
                        size="small"
                        value={field.label}
                        onChange={(e) => {
                            const newLabel = e.target.value;
                            const autoName = field.name === '' || field.name === slugify(field.label)
                                ? slugify(newLabel)
                                : field.name;
                            onChange({ ...field, label: newLabel, name: autoName });
                        }}
                        placeholder="VD: Họ tên"
                    />
                </div>
                <div>
                    <label style={{ fontSize: 12, color: '#6b7280', display: 'block', marginBottom: 4 }}>Name (key) *</label>
                    <Input
                        size="small"
                        value={field.name}
                        onChange={(e) => update('name', e.target.value.toLowerCase().replace(/\s+/g, '_'))}
                        placeholder="VD: full_name"
                    />
                </div>
            </div>

            {/* Row 2: placeholder + width + required */}
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 120px 100px', gap: 10 }}>
                <div>
                    <label style={{ fontSize: 12, color: '#6b7280', display: 'block', marginBottom: 4 }}>Placeholder</label>
                    <Input size="small" value={field.placeholder} onChange={(e) => update('placeholder', e.target.value)} placeholder="Văn bản gợi ý..." />
                </div>
                <div>
                    <label style={{ fontSize: 12, color: '#6b7280', display: 'block', marginBottom: 4 }}>Độ rộng</label>
                    <Select
                        size="small"
                        value={field.width}
                        onChange={(v) => update('width', v)}
                        options={[{ value: 'full', label: 'Toàn bộ' }, { value: 'half', label: '1/2' }]}
                        style={{ width: '100%' }}
                    />
                </div>
                <div style={{ paddingTop: 22 }}>
                    <Checkbox
                        checked={field.required}
                        onChange={(e) => update('required', e.target.checked)}
                    >
                        <span style={{ fontSize: 13 }}>Bắt buộc</span>
                    </Checkbox>
                </div>
            </div>

            {/* Options for select/radio */}
            {hasOptions && (
                <div style={{ marginTop: 10 }}>
                    <label style={{ fontSize: 12, color: '#6b7280', display: 'block', marginBottom: 6 }}>
                        Các lựa chọn (mỗi dòng: value|Nhãn)
                    </label>
                    <Input.TextArea
                        size="small"
                        rows={3}
                        value={(field.options ?? []).map((o) => `${o.value}|${o.label}`).join('\n')}
                        onChange={(e) => {
                            const options = e.target.value
                                .split('\n')
                                .filter((l) => l.trim() !== '')
                                .map((line) => {
                                    const [value, ...rest] = line.split('|');
                                    return { value: value.trim(), label: (rest.join('|') || value).trim() };
                                });
                            update('options', options);
                        }}
                        placeholder={`north|Miền Bắc\nsouth|Miền Nam\ncentral|Miền Trung`}
                    />
                </div>
            )}
        </div>
    );
}

function slugify(str) {
    return str
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd').replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
}

// ─── Main Modal ───────────────────────────────────────────────────────────────
export default function FormBuilderModal({ open, initialData, onClose, onSaved }) {
    const [form] = Form.useForm();
    const [fields, setFields] = useState([]);
    const [saving, setSaving] = useState(false);
    const isEdit = !!initialData?.id;

    // Load data when modal opens
    useEffect(() => {
        if (open && initialData?.id) {
            // Fetch full data
            api.get(`/contact-forms/${initialData.id}`).then((res) => {
                const d = res.data.data;
                form.setFieldsValue({
                    name: d.name,
                    description: d.description,
                    success_message: d.success_message,
                    notify_email: d.settings?.notify_email ?? '',
                    button_label: d.settings?.button_label ?? 'Gửi',
                    is_active: d.is_active,
                });
                setFields(d.fields ?? []);
            });
        } else if (open && !initialData) {
            form.resetFields();
            form.setFieldsValue({ is_active: true, success_message: 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất.' });
            setFields([
                { id: uid(), type: 'text',     label: 'Họ tên',    name: 'name',    required: true,  width: 'half', placeholder: 'Nhập họ tên', options: [] },
                { id: uid(), type: 'email',    label: 'Email',     name: 'email',   required: true,  width: 'half', placeholder: 'Nhập email', options: [] },
                { id: uid(), type: 'textarea', label: 'Nội dung',  name: 'message', required: true,  width: 'full', placeholder: 'Nội dung liên hệ...', options: [] },
            ]);
        }
    }, [open, initialData, form]);

    const addField = useCallback(() => {
        setFields((prev) => [...prev, defaultField()]);
    }, []);

    const updateField = useCallback((index, updated) => {
        setFields((prev) => prev.map((f, i) => (i === index ? updated : f)));
    }, []);

    const removeField = useCallback((index) => {
        setFields((prev) => prev.filter((_, i) => i !== index));
    }, []);

    const moveField = useCallback((from, to) => {
        setFields((prev) => {
            const copy = [...prev];
            const [item] = copy.splice(from, 1);
            copy.splice(to, 0, item);
            return copy;
        });
    }, []);

    const handleSubmit = useCallback(async () => {
        try {
            const values = await form.validateFields();

            // Validate fields
            for (const f of fields) {
                if (!f.label.trim() || !f.name.trim()) {
                    message.warning('Tất cả trường cần có nhãn và name key.');
                    return;
                }
            }

            // Check duplicate names
            const names = fields.map((f) => f.name);
            if (new Set(names).size !== names.length) {
                message.warning('Có name key bị trùng, vui lòng kiểm tra lại.');
                return;
            }

            setSaving(true);

            const payload = {
                name: values.name,
                description: values.description,
                success_message: values.success_message,
                is_active: values.is_active ?? true,
                fields,
                settings: {
                    notify_email: values.notify_email ?? '',
                    button_label: values.button_label ?? 'Gửi',
                },
            };

            if (isEdit) {
                await api.put(`/contact-forms/${initialData.id}`, payload);
                message.success('Cập nhật form thành công.');
            } else {
                await api.post('/contact-forms', payload);
                message.success('Tạo form thành công.');
            }

            onSaved();
        } catch (err) {
            if (err?.errorFields) return; // antd validation
            message.error('Không thể lưu form.');
        } finally {
            setSaving(false);
        }
    }, [form, fields, isEdit, initialData, onSaved]);

    return (
        <Modal
            open={open}
            onCancel={onClose}
            title={isEdit ? `Sửa form: ${initialData?.name}` : 'Tạo form liên hệ mới'}
            width={820}
            footer={[
                <Button key="cancel" onClick={onClose}>Hủy</Button>,
                <Button key="save" type="primary" loading={saving} onClick={handleSubmit} icon={<FontIcon name="save" />}>
                    {isEdit ? 'Lưu thay đổi' : 'Tạo form'}
                </Button>,
            ]}
            destroyOnClose
        >
            <Tabs defaultActiveKey="fields">
                <TabPane tab="Các trường nhập liệu" key="fields">
                    <div style={{ maxHeight: '55vh', overflowY: 'auto', paddingRight: 4 }}>
                        {fields.length === 0 && (
                            <div style={{ textAlign: 'center', padding: '24px 0', color: '#9ca3af' }}>
                                Chưa có trường nào. Thêm trường đầu tiên bên dưới.
                            </div>
                        )}
                        {fields.map((field, index) => (
                            <FieldEditor
                                key={field.id}
                                field={field}
                                index={index}
                                isFirst={index === 0}
                                isLast={index === fields.length - 1}
                                onChange={(updated) => updateField(index, updated)}
                                onRemove={() => removeField(index)}
                                onMoveUp={() => moveField(index, index - 1)}
                                onMoveDown={() => moveField(index, index + 1)}
                            />
                        ))}
                    </div>

                    <Button
                        type="dashed"
                        block
                        icon={<FontIcon name="create" />}
                        onClick={addField}
                        style={{ marginTop: 8 }}
                    >
                        Thêm trường mới
                    </Button>
                </TabPane>

                <TabPane tab="Cài đặt form" key="settings">
                    <Form form={form} layout="vertical" style={{ marginTop: 8 }}>
                        <Form.Item name="name" label="Tên form" rules={[{ required: true, message: 'Tên form là bắt buộc.' }]}>
                            <Input placeholder="VD: Form liên hệ trang chủ" />
                        </Form.Item>
                        <Form.Item name="description" label="Mô tả (hiển thị dưới tiêu đề form)">
                            <Input.TextArea rows={2} placeholder="Mô tả ngắn về form..." />
                        </Form.Item>
                        <Form.Item name="button_label" label="Nhãn nút gửi">
                            <Input placeholder="VD: Gửi, Liên hệ ngay, Đăng ký..." />
                        </Form.Item>
                        <Form.Item name="success_message" label="Thông báo sau khi gửi thành công">
                            <Input.TextArea rows={2} placeholder="Cảm ơn bạn đã liên hệ..." />
                        </Form.Item>
                        <Divider />
                        <Form.Item name="notify_email" label="Email nhận thông báo submission (để trống = không gửi)">
                            <Input type="email" placeholder="admin@example.com" />
                        </Form.Item>
                        <Form.Item name="is_active" label="Trạng thái" valuePropName="checked">
                            <Switch checkedChildren="Hoạt động" unCheckedChildren="Tắt" />
                        </Form.Item>
                    </Form>
                </TabPane>
            </Tabs>
        </Modal>
    );
}
