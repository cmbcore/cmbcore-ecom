import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Menu } from 'antd';
import { useLocation } from 'react-router-dom';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';
import { useModules } from '../hooks/useModules';

/**
 * Build an AntD Menu item from a module menu entry.
 * Key = route so selectedKeys={[location.pathname]} works.
 */
function mapMenuItem(item) {
    const children =
        Array.isArray(item.children) && item.children.length > 0
            ? item.children.map(mapMenuItem)
            : undefined;

    return {
        key: item.route,
        icon: item.icon ? <FontIcon name={item.icon} /> : null,
        label: item.label,
        children,
    };
}

/**
 * Collect keys of parent items whose subtree contains `pathname`.
 */
function findOpenKeys(items, pathname) {
    const result = [];

    function walk(nodes) {
        for (const node of nodes) {
            if (Array.isArray(node.children) && node.children.length > 0) {
                const dominated = walk(node.children);
                if (
                    dominated ||
                    node.children.some(
                        (c) =>
                            pathname === c.route ||
                            pathname.startsWith(c.route + '/'),
                    )
                ) {
                    result.push(node.route);
                    return true;
                }
            }
        }
        return false;
    }

    walk(items);
    return result;
}

/**
 * Find the deepest leaf route that matches `pathname`.
 */
function findSelectedKey(items, pathname) {
    for (const node of items) {
        if (Array.isArray(node.children) && node.children.length > 0) {
            const found = findSelectedKey(node.children, pathname);
            if (found) return found;
        } else if (
            pathname === node.route ||
            pathname.startsWith(node.route + '/')
        ) {
            return node.route;
        }
    }
    return null;
}

export default function Sidebar({ onNavigate }) {
    const location = useLocation();
    const { menuItems } = useModules();
    const { t } = useLocale();

    // ── Open-keys state ──────────────────────────────────────────────────────
    // Seeded once when menuItems first load, and again whenever the route
    // changes to a path whose parent is NOT already open.
    // After that, user can open/close any submenu freely (no override).
    const [openKeys, setOpenKeys] = useState([]);
    const prevPathname = useRef('');

    useEffect(() => {
        if (menuItems.length === 0) return;

        const required = findOpenKeys(menuItems, location.pathname);

        if (location.pathname !== prevPathname.current) {
            // Route changed: merge required open-keys with current ones so
            // already-open sections stay open and the active parent expands.
            setOpenKeys((prev) => {
                const merged = Array.from(new Set([...prev, ...required]));
                return merged;
            });
            prevPathname.current = location.pathname;
        } else {
            // First load: seed open-keys from current path.
            setOpenKeys(required);
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [menuItems, location.pathname]);

    const selectedKey = useMemo(
        () => findSelectedKey(menuItems, location.pathname) ?? location.pathname,
        [menuItems, location.pathname],
    );

    return (
        <div className="admin-sidebar">
            <div className="admin-sidebar__brand">
                <span className="admin-sidebar__eyebrow">CMBCORE</span>
                <strong className="admin-sidebar__title">
                    {t('layout.control_room')}
                </strong>
            </div>
            <Menu
                mode="inline"
                selectedKeys={[selectedKey]}
                openKeys={openKeys}
                onOpenChange={setOpenKeys}
                items={menuItems.map(mapMenuItem)}
                onClick={({ key }) => onNavigate(key)}
                inlineIndent={20}
            />
        </div>
    );
}
