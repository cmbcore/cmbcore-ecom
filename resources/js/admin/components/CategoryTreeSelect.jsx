import React from 'react';
import { TreeSelect } from 'antd';

export function mapCategoryTreeOptions(categories = []) {
    return categories.map((category) => ({
        title: category.name,
        value: category.id,
        key: category.id,
        children: mapCategoryTreeOptions(category.children ?? []),
    }));
}

export default function CategoryTreeSelect({
    categories = [],
    placeholder,
    allowClear = true,
    disabled = false,
    value,
    onChange,
    ...rest
}) {
    return (
        <TreeSelect
            allowClear={allowClear}
            disabled={disabled}
            value={value}
            onChange={onChange}
            treeDefaultExpandAll
            treeData={mapCategoryTreeOptions(categories)}
            placeholder={placeholder}
            {...rest}
        />
    );
}
