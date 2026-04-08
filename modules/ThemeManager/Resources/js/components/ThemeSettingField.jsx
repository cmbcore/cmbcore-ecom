import React, { useEffect, useMemo, useState } from 'react';
import { Button, Card, Col, Form, Input, InputNumber, Row, Select, Space, Switch } from 'antd';
import ImageResizer from '@admin/components/ImageResizer';
import FontIcon from '@admin/components/ui/FontIcon';
import IconPickerField from '@admin/components/ui/IconPickerField';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';
import { showDeleteConfirm } from '@admin/utils/confirm';

const { TextArea } = Input;

function flattenCategoryOptions(categories = [], depth = 0) {
    return categories.flatMap((category) => {
        const prefix = depth > 0 ? `${'— '.repeat(depth)} ` : '';

        return [
            {
                label: `${prefix}${category.name}`,
                value: String(category.slug),
            },
            ...flattenCategoryOptions(category.children ?? [], depth + 1),
        ];
    });
}

function CategorySelectField({ mode }) {
    const [options, setOptions] = useState([]);

    useEffect(() => {
        let mounted = true;

        async function fetchCategories() {
            try {
                const response = await api.get('/categories/tree');

                if (! mounted) {
                    return;
                }

                setOptions(flattenCategoryOptions(response.data.data ?? []));
            } catch {
                if (! mounted) {
                    return;
                }

                setOptions([]);
            }
        }

        fetchCategories();

        return () => {
            mounted = false;
        };
    }, []);

    const normalizedOptions = useMemo(() => options, [options]);

    return (
        <Select
            allowClear
            showSearch
            optionFilterProp="label"
            mode={mode}
            options={normalizedOptions}
        />
    );
}

function fieldSpan(field) {
    return field.span ?? (['textarea', 'object', 'repeater'].includes(field.type) ? 24 : 12);
}

function childName(name, key) {
    return Array.isArray(name) ? [...name, key] : [name, key];
}

function renderPrimitiveField(field) {
    switch (field.type) {
        case 'color':
            return <Input type="color" />;
        case 'number':
            return (
                <InputNumber
                    min={field.min}
                    max={field.max}
                    step={field.step ?? 1}
                    style={{ width: '100%' }}
                />
            );
        case 'boolean':
            return <Switch />;
        case 'select':
            return <Select options={(field.options ?? []).map((option) => ({ value: option.value, label: option.label }))} />;
        case 'textarea':
            return <TextArea rows={field.rows ?? 4} />;
        case 'tags':
            return <Select mode="tags" tokenSeparators={[',']} open={false} />;
        case 'image':
            return <ImageResizer defaultPreset={field.preset ?? '1:1'} outputWidth={field.output_width ?? 800} />;
        case 'icon':
            return <IconPickerField />;
        case 'category-select':
            return <CategorySelectField />;
        case 'category-multi-select':
            return <CategorySelectField mode="multiple" />;
        default:
            return <Input />;
    }
}

function ThemeSettingFieldInner({ field, name, level = 0 }) {
    const { t } = useLocale();

    if (field.type === 'object') {
        return (
            <Card
                className={`theme-settings-block theme-settings-block--object theme-settings-block--level-${level}`}
                title={field.label}
                size="small"
            >
                <Row gutter={[24, 24]}>
                    {(field.fields ?? []).map((childField) => (
                        <Col key={childField.key} xs={24} lg={fieldSpan(childField)}>
                            <ThemeSettingFieldInner
                                field={childField}
                                name={childName(name, childField.key)}
                                level={level + 1}
                            />
                        </Col>
                    ))}
                </Row>
            </Card>
        );
    }

    if (field.type === 'repeater') {
        return (
            <Form.List name={name}>
                {(fields, { add, remove, move }) => (
                    <Card
                        className={`theme-settings-block theme-settings-block--repeater theme-settings-block--level-${level}`}
                        title={field.label}
                        size="small"
                        extra={(
                            <Button icon={<FontIcon name="create" />} onClick={() => add({})}>
                                {t('themes.editor.add_row')}
                            </Button>
                        )}
                    >
                        <Space direction="vertical" size={16} style={{ display: 'flex' }}>
                            {fields.map((item, index) => (
                                <Card
                                    key={item.key}
                                    size="small"
                                    className="theme-settings-repeater__item"
                                    title={`${field.label} ${index + 1}`}
                                    extra={(
                                        <Space size={8}>
                                            <Button
                                                size="small"
                                                icon={<FontIcon name="move_up" />}
                                                onClick={() => move(index, index - 1)}
                                                disabled={index === 0}
                                            />
                                            <Button
                                                size="small"
                                                icon={<FontIcon name="move_down" />}
                                                onClick={() => move(index, index + 1)}
                                                disabled={index === fields.length - 1}
                                            />
                                            <Button
                                                danger
                                                size="small"
                                                icon={<FontIcon name="delete" />}
                                                onClick={() => showDeleteConfirm({
                                                    title: 'Xóa dòng cấu hình?',
                                                    content: 'Dòng này sẽ bị gỡ khỏi repeater trong theme setting.',
                                                    onConfirm: () => remove(item.name),
                                                })}
                                            >
                                                {t('common.delete')}
                                            </Button>
                                        </Space>
                                    )}
                                >
                                    <Row gutter={[24, 24]}>
                                        {(field.fields ?? []).map((childField) => (
                                            <Col key={childField.key} xs={24} lg={fieldSpan(childField)}>
                                                <ThemeSettingFieldInner
                                                    field={childField}
                                                    name={[item.name, childField.key]}
                                                    level={level + 1}
                                                />
                                            </Col>
                                        ))}
                                    </Row>
                                </Card>
                            ))}
                        </Space>
                    </Card>
                )}
            </Form.List>
        );
    }

    return (
        <Form.Item
            label={field.label}
            name={name}
            extra={field.description}
            valuePropName={field.type === 'boolean' ? 'checked' : 'value'}
        >
            {renderPrimitiveField(field)}
        </Form.Item>
    );
}

export default function ThemeSettingField(props) {
    return <ThemeSettingFieldInner {...props} />;
}
