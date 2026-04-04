import React from 'react';
import { Button, Card, Col, Form, Input, Row, Select, Space } from 'antd';
import RichTextEditor from '@admin/components/RichTextEditor';
import SingleImageUploader from '@admin/components/SingleImageUploader';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';
import { showDeleteConfirm } from '@admin/utils/confirm';

function BlockField({ field, name }) {
    switch (field.type) {
        case 'textarea':
            return (
                <Form.Item label={field.label} name={name}>
                    <RichTextEditor minHeight={220} />
                </Form.Item>
            );
        case 'select':
            return (
                <Form.Item label={field.label} name={name}>
                    <Select options={(field.options ?? []).map((option) => ({ value: option.value, label: option.label }))} />
                </Form.Item>
            );
        case 'image':
            return (
                <Form.Item label={field.label} name={name}>
                    <SingleImageUploader size={1200} />
                </Form.Item>
            );
        default:
            return (
                <Form.Item label={field.label} name={name}>
                    <Input />
                </Form.Item>
            );
    }
}

export default function PageBlockBuilder({ blocks = [] }) {
    const { t } = useLocale();
    const definitionsByType = Object.fromEntries(blocks.map((block) => [block.type, block]));

    return (
        <Form.List name="content_blocks">
            {(fields, { add, remove, move }) => (
                <Space direction="vertical" size={16} style={{ display: 'flex' }}>
                    {fields.map((field, index) => (
                        <Card
                            key={field.key}
                            size="small"
                            title={t('pages.blocks.block_number', { number: index + 1 })}
                            extra={(
                                <Space size={8}>
                                    <Button size="small" icon={<FontIcon name="move_up" />} onClick={() => move(index, index - 1)} disabled={index === 0} />
                                    <Button size="small" icon={<FontIcon name="move_down" />} onClick={() => move(index, index + 1)} disabled={index === fields.length - 1} />
                                    <Button
                                        danger
                                        size="small"
                                        icon={<FontIcon name="delete" />}
                                        onClick={() => showDeleteConfirm({
                                            title: 'Xóa block nội dung?',
                                            content: 'Block nội dung này sẽ bị gỡ khỏi trang hiện tại.',
                                            onConfirm: () => remove(field.name),
                                        })}
                                    >
                                        {t('common.delete')}
                                    </Button>
                                </Space>
                            )}
                        >
                            <Row gutter={16}>
                                <Col xs={24}>
                                    <Form.Item
                                        label={t('pages.blocks.type')}
                                        name={[field.name, 'type']}
                                        rules={[{ required: true, message: t('pages.blocks.validation.type_required') }]}
                                    >
                                        <Select
                                            options={blocks.map((block) => ({ value: block.type, label: block.label }))}
                                        />
                                    </Form.Item>
                                </Col>

                                <Form.Item noStyle shouldUpdate={(prev, next) => prev.content_blocks?.[field.name]?.type !== next.content_blocks?.[field.name]?.type}>
                                    {({ getFieldValue }) => {
                                        const type = getFieldValue(['content_blocks', field.name, 'type']);
                                        const definition = definitionsByType[type];

                                        if (!definition) {
                                            return null;
                                        }

                                        return definition.fields.map((blockField) => (
                                            <Col xs={24} lg={blockField.type === 'textarea' ? 24 : 12} key={blockField.key}>
                                                <BlockField field={blockField} name={[field.name, 'props', blockField.key]} />
                                            </Col>
                                        ));
                                    }}
                                </Form.Item>
                            </Row>
                        </Card>
                    ))}

                    <Button icon={<FontIcon name="create" />} onClick={() => add({ type: blocks[0]?.type ?? 'hero', props: {} })}>
                        {t('pages.blocks.add')}
                    </Button>
                </Space>
            )}
        </Form.List>
    );
}
