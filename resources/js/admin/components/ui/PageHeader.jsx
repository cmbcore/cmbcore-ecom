import React from 'react';
import { Button, Space, Typography } from 'antd';

const { Paragraph, Title } = Typography;

export default function PageHeader({ title, description, extra = [] }) {
    return (
        <div className="page-header">
            <div>
                <Title level={2} className="page-header__title">
                    {title}
                </Title>
                {description ? <Paragraph className="page-header__description">{description}</Paragraph> : null}
            </div>
            {extra.length > 0 ? (
                <Space wrap>
                    {extra.map((action, index) => (
                        <Button
                            key={action.key ?? index}
                            type={action.type ?? 'default'}
                            icon={action.icon}
                            onClick={action.onClick}
                            href={action.href}
                            loading={action.loading}
                            danger={action.danger}
                        >
                            {action.label}
                        </Button>
                    ))}
                </Space>
            ) : null}
        </div>
    );
}
