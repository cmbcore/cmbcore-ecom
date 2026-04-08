import React from 'react';
import { Button, Card, Form, Input, Select, Space } from 'antd';
import FontIcon from '@admin/components/ui/FontIcon';
import IconPickerField from '@admin/components/ui/IconPickerField';
import { useLocale } from '@admin/hooks/useLocale';
import { showDeleteConfirm } from '@admin/utils/confirm';

const TARGET_OPTIONS = [
    { value: '_self', label: 'Cùng tab' },
    { value: '_blank', label: 'Tab mới' },
];

/**
 * Normalize menu item label:
 * - If already a plain string, return as-is.
 * - If a multilingual object {vi, en, ...}, return the Vietnamese or first available value.
 */
function resolveMenuLabel(label) {
    if (typeof label === 'string') return label;
    if (label && typeof label === 'object') {
        return label['vi'] ?? label['en'] ?? Object.values(label)[0] ?? '';
    }
    return '';
}

/**
 * getValueProps for Ant Design Form.Item:
 * Converts object labels to plain string before rendering the Input,
 * preventing [object Object] being displayed on initial load.
 */
function labelValueProps(value) {
    return { value: resolveMenuLabel(value) };
}

export default function ThemeMenuEditor({ menu, menuIndex }) {
    const { t } = useLocale();

    return (
        <Card
            title={menu.label}
            type="inner"
            extra={menu.description ? <span>{menu.description}</span> : null}
        >
            <Form.Item name={['menus', menuIndex, 'alias']} hidden>
                <Input />
            </Form.Item>

            <Form.List name={['menus', menuIndex, 'items']}>
                {(fields, { add, remove }) => (
                    <Space direction="vertical" size={16} style={{ display: 'flex' }}>
                        {fields.map((field, itemIndex) => (
                            <Card
                                key={field.key}
                                size="small"
                                title={`Liên kết ${itemIndex + 1}`}
                                extra={
                                    <Button
                                        danger
                                        size="small"
                                        icon={<FontIcon name="delete" />}
                                        onClick={() =>
                                            showDeleteConfirm({
                                                title: 'Xóa liên kết menu?',
                                                content: 'Mục menu này sẽ bị gỡ khỏi theme settings.',
                                                onConfirm: () => remove(field.name),
                                            })
                                        }
                                    >
                                        {t('common.delete')}
                                    </Button>
                                }
                            >
                                <Space direction="vertical" size={12} style={{ display: 'flex' }}>
                                    {/* Single label field - normalize multilingual objects to plain string */}
                                    <Form.Item
                                        label="Nhãn hiển thị"
                                        name={[field.name, 'label']}
                                        normalize={resolveMenuLabel}
                                        getValueProps={labelValueProps}
                                        rules={[{ required: true, message: 'Vui lòng nhập nhãn menu.' }]}
                                    >
                                        <Input placeholder="VD: Trang chủ" />
                                    </Form.Item>

                                    <Form.Item
                                        label="Đường dẫn (URL)"
                                        name={[field.name, 'url']}
                                        rules={[{ required: true, message: 'Vui lòng nhập URL.' }]}
                                    >
                                        <Input placeholder="/products hoặc https://..." prefix={<FontIcon name="link" />} />
                                    </Form.Item>

                                    <Form.Item
                                        label="Icon (tùy chọn)"
                                        name={[field.name, 'icon']}
                                    >
                                        <IconPickerField />
                                    </Form.Item>

                                    <Form.Item
                                        label="Mở link"
                                        name={[field.name, 'target']}
                                        style={{ width: 200 }}
                                        initialValue="_self"
                                    >
                                        <Select options={TARGET_OPTIONS} />
                                    </Form.Item>
                                </Space>
                            </Card>
                        ))}

                        <Button
                            icon={<FontIcon name="create" />}
                            onClick={() => add({ target: '_self', label: '' })}
                        >
                            Thêm liên kết
                        </Button>
                    </Space>
                )}
            </Form.List>
        </Card>
    );
}
