@extends(theme_layout('app'))

@section('title', theme_context('page.meta_title', theme_text('account.addresses_title')))

@push('head')
<style>
    /* ── Address card ─────────────────────────────────── */
    .addr-card {
        position: relative;
        padding: 20px;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        transition: border-color 0.18s, box-shadow 0.18s;
    }

    .addr-card:hover {
        border-color: #d1d5db;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
    }

    .addr-card.is-default {
        border-color: #111;
    }

    .addr-card__badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 9px;
        border-radius: 999px;
        background: #111;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    .addr-card__badge-secondary {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 9px;
        border-radius: 999px;
        background: #f3f4f6;
        color: #374151;
        font-size: 10px;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .addr-card__label {
        font-size: 15px;
        font-weight: 700;
        color: #111;
        margin: 0 0 8px;
    }

    .addr-card__row {
        display: flex;
        align-items: flex-start;
        gap: 7px;
        font-size: 13.5px;
        color: #374151;
        margin-bottom: 5px;
        line-height: 1.45;
    }

    .addr-card__row i {
        flex-shrink: 0;
        margin-top: 2px;
        color: #9ca3af;
        font-size: 12px;
        width: 14px;
        text-align: center;
    }

    .addr-card__actions {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid #f3f4f6;
        flex-wrap: wrap;
    }

    .addr-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        background: #fff;
        color: #374151;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.15s;
    }

    .addr-btn:hover {
        border-color: #111;
        color: #111;
        background: #f9fafb;
    }

    .addr-btn.is-danger:hover {
        border-color: #dc2626;
        color: #dc2626;
        background: #fef2f2;
    }

    .addr-btn.is-primary {
        border-color: #111;
        background: #111;
        color: #fff;
    }

    .addr-btn.is-primary:hover {
        border-color: #333;
        background: #333;
    }

    .addr-add-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 20px;
        border: 2px dashed #e5e7eb;
        border-radius: 10px;
        background: none;
        color: #6b7280;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        text-align: center;
        transition: all 0.18s;
        text-decoration: none;
    }

    .addr-add-btn:hover {
        border-color: #111;
        color: #111;
        background: #f9fafb;
    }

    /* ── Modal overlay ─────────────────────────────────── */
    .addr-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 1000;
        background: rgba(0, 0, 0, 0.48);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.22s;
    }

    .addr-modal-overlay.is-open {
        opacity: 1;
        pointer-events: all;
    }

    .addr-modal {
        position: relative;
        width: 100%;
        max-width: 640px;
        max-height: 90vh;
        overflow-y: auto;
        background: #fff;
        border-radius: 12px;
        padding: 28px 28px 24px;
        transform: translateY(16px);
        transition: transform 0.22s;
    }

    .addr-modal-overlay.is-open .addr-modal {
        transform: translateY(0);
    }

    .addr-modal__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 20px;
    }

    .addr-modal__title {
        font-size: 17px;
        font-weight: 700;
        color: #0f172a;
    }

    .addr-modal__close {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border: 0;
        background: #f3f4f6;
        border-radius: 50%;
        cursor: pointer;
        color: #6b7280;
        font-size: 14px;
        flex-shrink: 0;
        transition: background 0.15s;
    }

    .addr-modal__close:hover {
        background: #e5e7eb;
        color: #111;
    }

    /* ── Form inside modal ─────────────────────────────── */
    .addr-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .addr-form-grid .is-full {
        grid-column: 1 / -1;
    }

    .addr-form-grid label {
        display: grid;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
    }

    .addr-form-grid label span em {
        color: #e02424;
        margin-left: 2px;
        font-style: normal;
    }

    .addr-form-grid input,
    .addr-form-grid textarea {
        width: 100%;
        padding: 11px 13px;
        border: 1px solid rgba(15, 23, 42, 0.14);
        border-radius: 6px;
        background: #fff;
        color: #0f172a;
        font: inherit;
        font-size: 14px;
        transition: border-color 0.16s;
    }

    .addr-form-grid input:focus,
    .addr-form-grid textarea:focus {
        outline: none;
        border-color: #111;
    }

    .addr-form-grid textarea {
        resize: vertical;
    }

    .addr-form-grid .addr-checkbox {
        display: flex !important;
        flex-direction: row;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }

    .addr-form-grid .addr-checkbox input {
        width: auto;
        margin: 0;
        width: 16px;
        height: 16px;
        flex-shrink: 0;
    }

    .addr-form-grid .addr-checkbox span {
        font-weight: 500;
    }

    /* ── Searchable dropdown ── (reuse checkout style) ── */
    .ck-field__required {
        color: #e02424;
        margin-left: 2px;
        font-size: 13px;
    }

    .ck-field-error {
        display: none;
        margin-top: 4px;
        color: #e02424;
        font-size: 12px;
        font-weight: 500;
    }

    .ck-field-error.is-visible {
        display: block;
    }

    .ck-input-error {
        border-color: #e02424 !important;
    }

    .ck-select-wrap {
        position: relative;
    }

    .ck-select-trigger {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 11px 13px;
        border: 1px solid rgba(15, 23, 42, 0.14);
        border-radius: 6px;
        background: #fff;
        color: #0f172a;
        font: inherit;
        font-size: 14px;
        cursor: pointer;
        text-align: left;
        user-select: none;
        transition: border-color 0.16s;
    }

    .ck-select-trigger:hover {
        border-color: rgba(15, 23, 42, 0.28);
    }

    .ck-select-trigger.is-placeholder {
        color: #9ca3af;
    }

    .ck-select-trigger .ck-select-caret {
        flex-shrink: 0;
        font-size: 10px;
        color: #6b7280;
        transition: transform 0.2s ease;
    }

    .ck-select-trigger.is-open .ck-select-caret {
        transform: rotate(180deg);
    }

    .ck-select-dropdown {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        z-index: 200;
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 6px;
        background: #fff;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.14);
        display: none;
        flex-direction: column;
        max-height: 260px;
    }

    .ck-select-dropdown.is-open {
        display: flex;
    }

    .ck-select-search {
        flex-shrink: 0;
        padding: 8px 10px;
        border-bottom: 1px solid #f0f0f0;
    }

    .ck-select-search input {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid rgba(15, 23, 42, 0.16);
        border-radius: 4px;
        background: #f9fafb;
        font: inherit;
        font-size: 13px;
        outline: none;
    }

    .ck-select-search input:focus {
        border-color: #111;
    }

    .ck-select-list {
        flex: 1;
        overflow-y: auto;
        margin: 0;
        padding: 4px 0;
        list-style: none;
    }

    .ck-select-list li {
        padding: 10px 14px;
        font-size: 13px;
        cursor: pointer;
    }

    .ck-select-list li:hover,
    .ck-select-list li.is-focused {
        background: #f3f4f6;
    }

    .ck-select-list li.is-selected {
        background: #111;
        color: #fff;
    }

    .ck-select-list li.is-hidden {
        display: none;
    }

    .ck-select-empty {
        padding: 14px;
        color: #9ca3af;
        font-size: 13px;
        text-align: center;
    }

    /* ── Address grid ─────────────────────────────────── */
    .addr-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    @media (max-width: 640px) {

        .addr-grid,
        .addr-form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<section class="cmbcore-section cmbcore-section--compact">
    <div class="cmbcore-container">
        <div class="cmbcore-account-dashboard">
            {{-- Header --}}
            <div class="cmbcore-account-dashboard__header">
                <div>
                    <p class="cmbcore-account-dashboard__eyebrow">
                        <i class="fa-solid fa-location-dot" style="margin-right:5px;color:#ed2524;"></i>
                        {{ theme_text('account.addresses_title') }}
                    </p>
                    <h1 style="margin:0;font-size:26px;font-weight:700;">Sổ địa chỉ</h1>
                    <p style="margin:6px 0 0;color:#64748b;font-size:14px;">
                        Quản lý các địa chỉ giao hàng của bạn.
                    </p>
                </div>
                <a class="cmbcore-button is-secondary" href="{{ route('storefront.account.dashboard') }}">
                    <i class="fa-solid fa-arrow-left" style="font-size:12px;"></i>
                    {{ theme_text('account.actions.back_dashboard') }}
                </a>
            </div>

            @if (session('status'))
            <div class="cmbcore-alert is-success">
                <i class="fa-solid fa-circle-check" style="margin-right:6px;"></i>
                {{ session('status') }}
            </div>
            @endif

            @if ($errors->any())
            <div class="cmbcore-alert is-danger">
                <i class="fa-solid fa-circle-exclamation" style="margin-right:6px;"></i>
                {{ $errors->first() }}
            </div>
            @endif

            {{-- Address grid --}}
            <div class="addr-grid">
                @foreach ($addresses as $address)
                <div class="addr-card {{ $address->is_default ? 'is-default' : '' }}">
                    {{-- Badges --}}
                    @if ($address->is_default)
                    <span class="addr-card__badge">
                        <i class="fa-solid fa-star" style="font-size:9px;"></i>
                        Mặc định
                    </span>
                    @elseif ($address->label)
                    <span class="addr-card__badge-secondary">
                        <i class="fa-regular fa-bookmark" style="font-size:9px;"></i>
                        {{ $address->label }}
                    </span>
                    @endif

                    {{-- Label / Name --}}
                    <h3 class="addr-card__label">
                        {{ $address->label ?: ($address->is_default ? 'Địa chỉ mặc định' : theme_text('account.default_address')) }}
                    </h3>

                    {{-- Person & phone --}}
                    <div class="addr-card__row">
                        <i class="fa-solid fa-user"></i>
                        <span><strong>{{ $address->recipient_name }}</strong></span>
                    </div>
                    <div class="addr-card__row">
                        <i class="fa-solid fa-phone"></i>
                        <span>{{ $address->phone }}</span>
                    </div>
                    <div class="addr-card__row">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>{{ $address->formattedAddress() }}</span>
                    </div>

                    {{-- Actions --}}
                    <div class="addr-card__actions">
                        @if (! $address->is_default)
                        <form method="post" action="{{ route('storefront.account.addresses.default', $address->id) }}">
                            @csrf
                            <button type="submit" class="addr-btn">
                                <i class="fa-regular fa-star"></i>
                                Đặt làm mặc định
                            </button>
                        </form>
                        @endif
                        <form method="post" action="{{ route('storefront.account.addresses.destroy', $address->id) }}"
                            onsubmit="return confirm('Xác nhận xóa địa chỉ này?')">
                            @csrf
                            <button type="submit" class="addr-btn is-danger">
                                <i class="fa-regular fa-trash-can"></i>
                                Xóa
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach

                {{-- Add new button --}}
                <button type="button" class="addr-add-btn" id="addr-open-modal">
                    <i class="fa-solid fa-plus" style="font-size:18px;"></i>
                    Thêm địa chỉ mới
                </button>
            </div>
        </div>
    </div>
</section>

{{-- ── Add Address Modal ──────────────────────────────────── --}}
<div class="addr-modal-overlay" id="addr-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="addr-modal-title">
    <div class="addr-modal">
        <div class="addr-modal__header">
            <h2 class="addr-modal__title" id="addr-modal-title">
                <i class="fa-solid fa-location-dot" style="color:#ed2524;margin-right:8px;"></i>
                Thêm địa chỉ mới
            </h2>
            <button type="button" class="addr-modal__close" id="addr-close-modal" aria-label="Đóng">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="post" action="{{ route('storefront.account.addresses.store') }}" class="addr-form-grid" id="addr-form" novalidate>
            @csrf

            {{-- Label --}}
            <label class="is-full">
                <span>Nhãn địa chỉ <small style="font-weight:400;color:#6b7280;">(VD: Nhà riêng, Văn phòng)</small></span>
                <input type="text" name="label" placeholder="VD: Nhà riêng" value="{{ old('label') }}">
            </label>

            {{-- Recipient name --}}
            <label>
                <span>Họ và tên người nhận <em class="ck-field__required">*</em></span>
                <input type="text" name="recipient_name" id="addr-recipient-name"
                    placeholder="Tên người nhận hàng"
                    value="{{ old('recipient_name') }}"
                    data-required="true">
                <span class="ck-field-error" data-for="addr-recipient-name">Vui lòng nhập tên người nhận.</span>
            </label>

            {{-- Phone --}}
            <label>
                <span>Số điện thoại <em class="ck-field__required">*</em></span>
                <input type="tel" name="phone" id="addr-phone"
                    placeholder="VD: 0912345678"
                    value="{{ old('phone') }}"
                    data-required="true"
                    data-pattern="^(0|\+84)[0-9]{9}$">
                <span class="ck-field-error" data-for="addr-phone">Số điện thoại không hợp lệ.</span>
            </label>

            {{-- Province --}}
            <label>
                <span>Tỉnh / Thành phố <em class="ck-field__required">*</em></span>
                <input type="hidden" name="province_code" id="addr-province-code" value="{{ old('province_code') }}">
                <input type="hidden" name="province" id="addr-province-name" value="{{ old('province') }}">
                <div class="ck-select-wrap" id="addr-province-wrap" data-select="province">
                    <button type="button" class="ck-select-trigger is-placeholder" id="addr-province-trigger"
                        aria-haspopup="listbox" aria-expanded="false">
                        <span id="addr-province-label">Chọn tỉnh / thành phố</span>
                        <i class="fa-solid fa-chevron-down ck-select-caret"></i>
                    </button>
                    <div class="ck-select-dropdown" id="addr-province-dropdown" role="listbox">
                        <div class="ck-select-search">
                            <input type="text" placeholder="Tìm tỉnh / thành phố…"
                                id="addr-province-search" autocomplete="off" spellcheck="false">
                        </div>
                        <ul class="ck-select-list" id="addr-province-list"></ul>
                        <div class="ck-select-empty" id="addr-province-empty" style="display:none;">Không tìm thấy</div>
                    </div>
                </div>
                <span class="ck-field-error" id="addr-province-error">Vui lòng chọn tỉnh / thành phố.</span>
            </label>

            {{-- Ward --}}
            <label>
                <span>Phường / Xã <em class="ck-field__required">*</em></span>
                <input type="hidden" name="ward_code" id="addr-ward-code" value="{{ old('ward_code') }}">
                <input type="hidden" name="ward" id="addr-ward-name" value="{{ old('ward') }}">
                <div class="ck-select-wrap" id="addr-ward-wrap">
                    <button type="button" class="ck-select-trigger is-placeholder" id="addr-ward-trigger"
                        aria-haspopup="listbox" aria-expanded="false" disabled>
                        <span id="addr-ward-label">Chọn tỉnh trước</span>
                        <i class="fa-solid fa-chevron-down ck-select-caret"></i>
                    </button>
                    <div class="ck-select-dropdown" id="addr-ward-dropdown" role="listbox">
                        <div class="ck-select-search">
                            <input type="text" placeholder="Tìm phường / xã…"
                                id="addr-ward-search" autocomplete="off" spellcheck="false">
                        </div>
                        <ul class="ck-select-list" id="addr-ward-list"></ul>
                        <div class="ck-select-empty" id="addr-ward-empty" style="display:none;">Không tìm thấy</div>
                    </div>
                </div>
                <span class="ck-field-error" id="addr-ward-error">Vui lòng chọn phường / xã.</span>
            </label>

            {{-- Address line --}}
            <label class="is-full">
                <span>Địa chỉ cụ thể (số nhà, tên đường) <em class="ck-field__required">*</em></span>
                <input type="text" name="address_line" id="addr-address-line"
                    placeholder="VD: 123 Đường Trần Hưng Đạo"
                    value="{{ old('address_line') }}"
                    data-required="true">
                <span class="ck-field-error" data-for="addr-address-line">Vui lòng nhập địa chỉ cụ thể.</span>
            </label>

            {{-- Note --}}
            <label class="is-full">
                <span>Ghi chú giao hàng <small style="font-weight:400;color:#6b7280;">(không bắt buộc)</small></span>
                <textarea name="address_note" rows="2"
                    placeholder="Hướng dẫn giao hàng, landmark…">{{ old('address_note') }}</textarea>
            </label>

            {{-- Default checkbox --}}
            <label class="is-full addr-checkbox">
                <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}>
                <span>Đặt làm địa chỉ mặc định</span>
            </label>

            <div class="is-full" style="display:flex;gap:10px;justify-content:flex-end;padding-top:4px;">
                <button type="button" class="addr-btn" id="addr-cancel-modal">Hủy</button>
                <button type="submit" class="addr-btn is-primary">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Lưu địa chỉ
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function() {
        'use strict';

        const API = '/api/address';

        /* ── Modal ──────────────────────────────────────────────── */
        const overlay = document.getElementById('addr-modal-overlay');
        const openBtn = document.getElementById('addr-open-modal');
        const closeBtn = document.getElementById('addr-close-modal');
        const cancelBtn = document.getElementById('addr-cancel-modal');

        function openModal() {
            overlay.classList.add('is-open');
            document.body.style.overflow = 'hidden';
            // Load provinces if not loaded yet
            if (provinceSelect) provinceSelect.loadIfEmpty();
        }

        function closeModal() {
            overlay.classList.remove('is-open');
            document.body.style.overflow = '';
        }

        openBtn?.addEventListener('click', openModal);
        closeBtn?.addEventListener('click', closeModal);
        cancelBtn?.addEventListener('click', closeModal);
        overlay?.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });

        // Auto-open modal if there are validation errors (page reload after POST)
        @if($errors->any())
        openModal();
        @endif

        /* ── Searchable select factory ─────────────────────────── */
        function SearchSelect({
            triggerId,
            labelId,
            searchId,
            listId,
            emptyId,
            codeInputId,
            nameInputId,
            onSelect
        }) {
            const trigger = document.getElementById(triggerId);
            const label = document.getElementById(labelId);
            const search = document.getElementById(searchId);
            const list = document.getElementById(listId);
            const empty = document.getElementById(emptyId);
            const codeInput = document.getElementById(codeInputId);
            const nameInput = document.getElementById(nameInputId);
            const dropdown = trigger?.nextElementSibling;

            if (!trigger || !dropdown) return null;

            let items = [];
            let focusedIndex = -1;

            function open() {
                dropdown.classList.add('is-open');
                trigger.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
                search.value = '';
                renderList(items);
                search.focus();
                focusedIndex = -1;
            }

            function close() {
                dropdown.classList.remove('is-open');
                trigger.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
            }

            function toggle() {
                dropdown.classList.contains('is-open') ? close() : open();
            }

            function renderList(data) {
                list.innerHTML = '';
                if (data.length === 0) {
                    empty.style.display = 'block';
                    return;
                }
                empty.style.display = 'none';
                data.forEach((item) => {
                    const li = document.createElement('li');
                    li.textContent = item.name;
                    li.dataset.code = item.code;
                    if (codeInput.value && item.code == codeInput.value) li.classList.add('is-selected');
                    li.addEventListener('click', () => select(item));
                    list.appendChild(li);
                });
            }

            function select(item) {
                codeInput.value = item.code;
                if (nameInput) nameInput.value = item.name;
                label.textContent = item.name;
                trigger.classList.remove('is-placeholder');
                close();
                onSelect?.(item);
            }

            function filter(q) {
                const lower = q.toLowerCase().trim();
                const visible = items.filter(i => i.name.toLowerCase().includes(lower));
                renderList(visible);
            }

            function reset(placeholder) {
                label.textContent = placeholder;
                trigger.classList.add('is-placeholder');
                codeInput.value = '';
                if (nameInput) nameInput.value = '';
                list.innerHTML = '';
                items = [];
            }

            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                toggle();
            });
            search.addEventListener('input', () => filter(search.value));
            search.addEventListener('keydown', (e) => {
                const lis = [...list.querySelectorAll('li:not(.is-hidden)')];
                if (e.key === 'ArrowDown') {
                    focusedIndex = Math.min(focusedIndex + 1, lis.length - 1);
                } else if (e.key === 'ArrowUp') {
                    focusedIndex = Math.max(focusedIndex - 1, 0);
                } else if (e.key === 'Enter' && focusedIndex >= 0) {
                    e.preventDefault();
                    const item = items.find(i => i.name === lis[focusedIndex]?.textContent);
                    if (item) select(item);
                } else if (e.key === 'Escape') {
                    close();
                    trigger.focus();
                }
                lis.forEach((li, i) => li.classList.toggle('is-focused', i === focusedIndex));
                lis[focusedIndex]?.scrollIntoView({
                    block: 'nearest'
                });
            });
            document.addEventListener('click', (e) => {
                if (!trigger.contains(e.target) && !dropdown.contains(e.target)) close();
            });

            function loadItems(data) {
                items = data;
                // Restore previous value if any
                if (codeInput.value) {
                    const found = items.find(i => i.code == codeInput.value);
                    if (found) {
                        label.textContent = found.name;
                        trigger.classList.remove('is-placeholder');
                    }
                }
            }

            function loadIfEmpty() {
                if (items.length === 0 && !trigger.disabled) {
                    // trigger province load externally
                }
            }

            return {
                reset,
                loadItems,
                loadIfEmpty,
                select
            };
        }

        /* ── Province ──────────────────────────────────────────── */
        const provinceSelect = SearchSelect({
            triggerId: 'addr-province-trigger',
            labelId: 'addr-province-label',
            searchId: 'addr-province-search',
            listId: 'addr-province-list',
            emptyId: 'addr-province-empty',
            codeInputId: 'addr-province-code',
            nameInputId: 'addr-province-name',
            onSelect(item) {
                wardSelect?.reset('Chọn phường / xã');
                document.getElementById('addr-ward-trigger').disabled = false;
                loadWards(item.code);
            },
        });

        /* ── Ward ──────────────────────────────────────────────── */
        const wardSelect = SearchSelect({
            triggerId: 'addr-ward-trigger',
            labelId: 'addr-ward-label',
            searchId: 'addr-ward-search',
            listId: 'addr-ward-list',
            emptyId: 'addr-ward-empty',
            codeInputId: 'addr-ward-code',
            nameInputId: 'addr-ward-name',
        });

        /* ── Load provinces ─────────────────────────────────────── */
        async function loadProvinces() {
            try {
                const res = await fetch(`${API}/provinces`);
                const json = await res.json();
                provinceSelect?.loadItems(json.data ?? []);
            } catch {}
        }

        async function loadWards(provinceCode) {
            const wardTrigger = document.getElementById('addr-ward-trigger');
            const wardLabel = document.getElementById('addr-ward-label');
            wardLabel.textContent = 'Đang tải…';
            wardTrigger.disabled = true;
            try {
                const res = await fetch(`${API}/provinces/${provinceCode}/communes`);
                const json = await res.json();
                wardSelect?.loadItems(json.data ?? []);
                wardLabel.textContent = 'Chọn phường / xã';
            } catch {
                wardLabel.textContent = 'Lỗi tải dữ liệu';
            } finally {
                wardTrigger.disabled = false;
            }
        }

        loadProvinces();

        /* ── Client-side form validation ──────────────────────── */
        const form = document.getElementById('addr-form');
        form?.addEventListener('submit', (e) => {
            let valid = true;

            // Required text fields
            form.querySelectorAll('[data-required]').forEach((input) => {
                const err = form.querySelector(`[data-for="${input.id}"]`);
                const isEmpty = !input.value.trim();
                input.classList.toggle('ck-input-error', isEmpty);
                err?.classList.toggle('is-visible', isEmpty);
                if (isEmpty) valid = false;
            });

            // Pattern validation
            form.querySelectorAll('[data-pattern]').forEach((input) => {
                if (!input.value.trim()) return;
                const pattern = new RegExp(input.dataset.pattern);
                const invalid = !pattern.test(input.value.trim());
                input.classList.toggle('ck-input-error', invalid);
                const err = form.querySelector(`[data-for="${input.id}"]`);
                err?.classList.toggle('is-visible', invalid);
                if (invalid) valid = false;
            });

            // Province
            const pCode = document.getElementById('addr-province-code');
            const pErr = document.getElementById('addr-province-error');
            const pMissing = !pCode?.value;
            pErr?.classList.toggle('is-visible', pMissing);
            if (pMissing) valid = false;

            // Ward
            const wCode = document.getElementById('addr-ward-code');
            const wErr = document.getElementById('addr-ward-error');
            const wMissing = !wCode?.value;
            wErr?.classList.toggle('is-visible', wMissing);
            if (wMissing) valid = false;

            if (!valid) e.preventDefault();
        });
    })();
</script>
@endpush