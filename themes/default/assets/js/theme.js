(() => {
    const body = document.body;

    const setDrawerState = (open) => {
        body.classList.toggle('sf-drawer-open', open);
    };

    document.querySelectorAll('[data-sf-drawer-toggle]').forEach((button) => {
        button.addEventListener('click', () => setDrawerState(true));
    });

    document.querySelectorAll('[data-sf-drawer-close]').forEach((button) => {
        button.addEventListener('click', () => setDrawerState(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setDrawerState(false);
        }
    });

    document.querySelectorAll('[data-sf-slider]').forEach((slider) => {
        const slides = Array.from(slider.querySelectorAll('[data-sf-slide]'));
        const dots = Array.from(slider.querySelectorAll('[data-sf-slide-dot]'));
        let index = slides.findIndex((slide) => slide.classList.contains('is-active'));
        index = index >= 0 ? index : 0;

        const render = () => {
            slides.forEach((slide, slideIndex) => {
                slide.classList.toggle('is-active', slideIndex === index);
            });

            dots.forEach((dot, dotIndex) => {
                dot.classList.toggle('is-active', dotIndex === index);
            });
        };

        slider.querySelector('[data-sf-slider-prev]')?.addEventListener('click', () => {
            index = (index - 1 + slides.length) % slides.length;
            render();
        });

        slider.querySelector('[data-sf-slider-next]')?.addEventListener('click', () => {
            index = (index + 1) % slides.length;
            render();
        });

        dots.forEach((dot, dotIndex) => {
            dot.addEventListener('click', () => {
                index = dotIndex;
                render();
            });
        });

        render();
    });

    document.querySelectorAll('[data-sf-gallery]').forEach((gallery) => {
        const main = gallery.querySelector('[data-sf-gallery-main]');
        const thumbs = Array.from(gallery.querySelectorAll('[data-sf-gallery-thumb]'));

        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                thumbs.forEach((item) => item.classList.remove('is-active'));
                thumb.classList.add('is-active');

                if (main) {
                    main.innerHTML = thumb.dataset.html || '';
                }
            });
        });
    });

    document.querySelectorAll('[data-sf-qty]').forEach((wrapper) => {
        const input = wrapper.querySelector('input');

        wrapper.querySelectorAll('[data-sf-qty-step]').forEach((button) => {
            button.addEventListener('click', () => {
                if (!(input instanceof HTMLInputElement)) {
                    return;
                }

                const currentValue = Number.parseInt(input.value || '1', 10) || 1;
                const delta = Number.parseInt(button.dataset.sfQtyStep || '0', 10) || 0;
                const min = Number.parseInt(input.min || '1', 10) || 1;
                const max = Number.parseInt(input.max || '999999', 10) || 999999;
                const nextValue = Math.min(max, Math.max(min, currentValue + delta));

                input.value = String(nextValue);
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });

    document.querySelectorAll('[data-sf-product-purchase]').forEach((root) => {
        const price = root.querySelector('[data-sf-price]');
        const comparePrice = root.querySelector('[data-sf-compare-price]');
        const stock = root.querySelector('[data-sf-stock]');
        const skuName = root.querySelector('[data-sf-sku-name]');
        const skuCode = root.querySelector('[data-sf-sku-code]');
        const countdown = root.querySelector('[data-sf-countdown]');
        const hiddenInputs = Array.from(root.querySelectorAll('[data-sf-sku-input]'));
        const buttons = Array.from(root.querySelectorAll('[data-sf-variant-option]'));

        const applyState = (button) => {
            buttons.forEach((item) => {
                item.classList.toggle('is-active', item === button);
                item.setAttribute('aria-pressed', item === button ? 'true' : 'false');
            });

            if (price) {
                price.textContent = button.dataset.price || '';
            }

            if (comparePrice instanceof HTMLElement) {
                comparePrice.textContent = button.dataset.comparePrice || '';
                comparePrice.hidden = comparePrice.textContent === '';
            }

            if (stock) {
                stock.textContent = button.dataset.stock || '';
            }

            if (skuName) {
                skuName.textContent = button.dataset.name || '';
            }

            if (skuCode) {
                skuCode.textContent = button.dataset.code || '';
            }

            hiddenInputs.forEach((input) => {
                if (input instanceof HTMLInputElement) {
                    input.value = button.dataset.skuId || '';
                }
            });

            if (countdown instanceof HTMLElement) {
                countdown.dataset.endAt = button.dataset.endsAt || '';
                countdown.dataset.title = button.dataset.saleTitle || '';
                countdown.dispatchEvent(new CustomEvent('sf:countdown:refresh'));
            }
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => applyState(button));
        });

        if (buttons[0]) {
            applyState(buttons.find((button) => button.classList.contains('is-active')) || buttons[0]);
        }
    });

    const formatCountdown = (distance) => {
        const totalSeconds = Math.max(0, Math.floor(distance / 1000));
        const days = Math.floor(totalSeconds / 86400);
        const hours = Math.floor((totalSeconds % 86400) / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        return [days, hours, minutes, seconds]
            .map((value) => String(value).padStart(2, '0'))
            .join(' : ');
    };

    document.querySelectorAll('[data-sf-countdown]').forEach((node) => {
        const tick = () => {
            const endAt = node.dataset.endAt || '';

            if (!endAt) {
                node.hidden = true;
                return;
            }

            const distance = new Date(endAt).getTime() - Date.now();

            if (Number.isNaN(distance) || distance <= 0) {
                node.hidden = true;
                return;
            }

            node.hidden = false;
            node.textContent = `${node.dataset.title ? `${node.dataset.title}: ` : ''}${formatCountdown(distance)}`;
        };

        node.addEventListener('sf:countdown:refresh', tick);
        tick();
        window.setInterval(tick, 1000);
    });
})();
