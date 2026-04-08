(function () {
    const body = document.body;

    function initDrawer() {
        const toggles = document.querySelectorAll('[data-cmbcore-drawer-toggle]');
        const closers = document.querySelectorAll('[data-cmbcore-drawer-close]');
        const sectionToggles = document.querySelectorAll('[data-cmbcore-drawer-section]');

        function openDrawer() {
            body.classList.add('cmbcore-drawer-open');
        }

        function closeDrawer() {
            body.classList.remove('cmbcore-drawer-open');
        }

        toggles.forEach((toggle) => {
            toggle.addEventListener('click', openDrawer);
        });

        closers.forEach((closer) => {
            closer.addEventListener('click', closeDrawer);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeDrawer();
            }
        });

        sectionToggles.forEach((toggle) => {
            toggle.addEventListener('click', () => {
                const item = toggle.closest('.cmbcore-drawer__item');

                if (!(item instanceof HTMLElement)) {
                    return;
                }

                const isOpen = item.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        });
    }

    function initSlider() {
        document.querySelectorAll('.js-cmbcore-slider').forEach((slider) => {
            const slides = Array.from(slider.querySelectorAll('.cmbcore-hero__slide'));
            const dots = Array.from(slider.querySelectorAll('[data-slider-dot]'));
            const prevButton = slider.querySelector('[data-slider-prev]');
            const nextButton = slider.querySelector('[data-slider-next]');

            if (slides.length < 2) {
                return;
            }

            const interval = Number.parseInt(slider.dataset.sliderInterval || '5600', 10);
            let activeIndex = Math.max(0, slides.findIndex((slide) => slide.classList.contains('is-active')));
            let timerId = null;

            const setActive = (index) => {
                activeIndex = (index + slides.length) % slides.length;
                slides.forEach((slide, slideIndex) => {
                    slide.classList.toggle('is-active', slideIndex === activeIndex);
                });
                dots.forEach((dot, dotIndex) => {
                    dot.classList.toggle('is-active', dotIndex === activeIndex);
                });
            };

            const start = () => {
                if (timerId !== null) {
                    window.clearInterval(timerId);
                }

                timerId = window.setInterval(() => {
                    setActive(activeIndex + 1);
                }, Number.isFinite(interval) ? interval : 5600);
            };

            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    setActive(index);
                    start();
                });
            });

            if (prevButton instanceof HTMLButtonElement) {
                prevButton.addEventListener('click', () => {
                    setActive(activeIndex - 1);
                    start();
                });
            }

            if (nextButton instanceof HTMLButtonElement) {
                nextButton.addEventListener('click', () => {
                    setActive(activeIndex + 1);
                    start();
                });
            }

            slider.addEventListener('mouseenter', () => {
                if (timerId !== null) {
                    window.clearInterval(timerId);
                }
            });

            slider.addEventListener('mouseleave', start);

            setActive(activeIndex);
            start();
        });
    }

    function initGallery() {
        document.querySelectorAll('[data-cmbcore-gallery]').forEach((gallery) => {
            const target = gallery.querySelector('[data-gallery-target]');
            const thumbs = Array.from(gallery.querySelectorAll('[data-gallery-thumb]'));

            if (!(target instanceof HTMLImageElement) || thumbs.length === 0) {
                return;
            }

            thumbs.forEach((thumb) => {
                thumb.addEventListener('click', () => {
                    const nextUrl = thumb.getAttribute('data-gallery-thumb');

                    if (!nextUrl || nextUrl === target.src) {
                        return;
                    }

                    // Fade out
                    target.classList.add('is-fading');

                    // Preload then swap
                    const preload = new Image();

                    preload.onload = () => {
                        target.src = nextUrl;
                        target.classList.remove('is-fading');
                    };

                    preload.onerror = () => {
                        target.src = nextUrl;
                        target.classList.remove('is-fading');
                    };

                    preload.src = nextUrl;

                    // Update active thumb
                    thumbs.forEach((item) => item.classList.remove('is-active'));
                    thumb.classList.add('is-active');
                });
            });
        });
    }

    function initQuantity() {
        document.querySelectorAll('.cmbcore-quantity').forEach((quantity) => {
            const input = quantity.querySelector('[data-quantity-input]');
            const controls = quantity.querySelectorAll('[data-quantity-step]');

            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            controls.forEach((control) => {
                control.addEventListener('click', () => {
                    const delta = Number.parseInt(control.getAttribute('data-quantity-step') || '0', 10);
                    const currentValue = Number.parseInt(input.value || '1', 10);
                    const minValue = Number.parseInt(input.min || '1', 10);
                    const nextValue = Math.max(minValue, currentValue + delta);

                    input.value = String(nextValue);
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });

            input.addEventListener('change', () => {
                const form = quantity.closest('[data-cmbcore-product]')?.querySelector('[data-product-purchase-form]');
                const targetInput = form?.querySelector('[data-product-quantity-input]');

                if (targetInput instanceof HTMLInputElement) {
                    targetInput.value = input.value || '1';
                }
            });
        });
    }

    function initProductSwatches() {
        document.querySelectorAll('[data-cmbcore-product]').forEach((productRoot) => {
            const payload = productRoot.getAttribute('data-product');
            const priceBox = productRoot.querySelector('[data-product-price]');
            const swatches = Array.from(productRoot.querySelectorAll('.cmbcore-swatch'));

            if (!payload || !(priceBox instanceof HTMLElement) || swatches.length === 0) {
                return;
            }

            let product;

            try {
                product = JSON.parse(payload);
            } catch (error) {
                return;
            }

            const skus = Array.isArray(product?.skus) ? product.skus : [];
            const purchaseForm = productRoot.querySelector('[data-product-purchase-form]');
            const skuInput = purchaseForm?.querySelector('[data-product-sku-input]');

            const formatPrice = (value) => {
                const numeric = Number(value || 0);

                return new Intl.NumberFormat(document.documentElement.lang === 'vi' ? 'vi-VN' : 'en-US', {
                    style: 'currency',
                    currency: 'VND',
                    maximumFractionDigits: 0,
                }).format(numeric);
            };

            const renderPrice = (sku) => {
                const price = Number(sku?.price ?? product?.min_price ?? 0);
                const comparePrice = Number(sku?.compare_price ?? product?.min_compare_price ?? 0);
                const discount = comparePrice > price && comparePrice > 0
                    ? Math.round(((comparePrice - price) / comparePrice) * 100)
                    : null;

                priceBox.innerHTML = `
                    ${discount ? `<span class="cmbcore-product-summary__discount">-${discount}%</span>` : ''}
                    ${comparePrice > price ? `<del>${formatPrice(comparePrice)}</del>` : ''}
                    <strong>${formatPrice(price)}</strong>
                `;

                if (skuInput instanceof HTMLInputElement && sku?.id) {
                    skuInput.value = String(sku.id);
                }
            };

            const activeSelections = {};

            const matchSku = () => skus.find((sku) => {
                const attributes = Array.isArray(sku?.attributes) ? sku.attributes : [];

                return Object.entries(activeSelections).every(([name, value]) => (
                    attributes.some((attribute) => attribute.attribute_name === name && attribute.attribute_value === value)
                ));
            }) || skus[0];

            swatches.forEach((swatch) => {
                const name = swatch.getAttribute('data-swatch-name');
                const value = swatch.getAttribute('data-swatch-value');

                if (!name || !value) {
                    return;
                }

                if (!(name in activeSelections)) {
                    activeSelections[name] = value;
                }

                swatch.addEventListener('click', () => {
                    activeSelections[name] = value;

                    swatches.forEach((candidate) => {
                        if (candidate.getAttribute('data-swatch-name') === name) {
                            candidate.classList.remove('is-active');
                        }
                    });

                    swatch.classList.add('is-active');
                    renderPrice(matchSku());
                });
            });

            renderPrice(matchSku());
        });
    }

    function initToc() {
        document.querySelectorAll('[data-cmbcore-toc]').forEach((toc) => {
            const toggles = toc.querySelectorAll('[data-cmbcore-toc-toggle]');
            const links = toc.querySelectorAll('.cmbcore-toc__list a[href^="#"]');

            toggles.forEach((toggle) => {
                toggle.addEventListener('click', () => {
                    toc.classList.toggle('is-minimized');
                });
            });

            links.forEach((link) => {
                link.addEventListener('click', (event) => {
                    const href = link.getAttribute('href');

                    if (!href) {
                        return;
                    }

                    const target = document.querySelector(href);

                    if (!(target instanceof HTMLElement)) {
                        return;
                    }

                    event.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });
        });
    }

    function initTocInline() {
        document.querySelectorAll('[data-toc-inline]').forEach((tocEl) => {
            const toggle = tocEl.querySelector('[data-toc-toggle]');
            const list   = tocEl.querySelector('[data-toc-list]');
            const links  = Array.from(tocEl.querySelectorAll('[data-toc-link]'));

            // Toggle collapse / expand
            if (toggle instanceof HTMLButtonElement) {
                toggle.addEventListener('click', () => {
                    const isCollapsed = tocEl.classList.toggle('is-collapsed');
                    toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                });
            }

            // Smooth scroll on link click
            links.forEach((link) => {
                link.addEventListener('click', (event) => {
                    const href = link.getAttribute('href');

                    if (!href) { return; }

                    const target = document.querySelector(href);

                    if (!(target instanceof HTMLElement)) { return; }

                    event.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            // Scroll-spy: highlight active heading
            if (links.length === 0) { return; }

            const headingMap = new Map();

            links.forEach((link) => {
                const href = link.getAttribute('href');

                if (!href) { return; }

                const heading = document.querySelector(href);

                if (heading instanceof HTMLElement) {
                    headingMap.set(heading, link);
                }
            });

            const headings = Array.from(headingMap.keys());

            const onScroll = () => {
                const scrollY = window.scrollY + 120; // offset for sticky header
                let activeHeading = null;

                for (const heading of headings) {
                    if (heading.offsetTop <= scrollY) {
                        activeHeading = heading;
                    } else {
                        break;
                    }
                }

                headingMap.forEach((linkEl, headingEl) => {
                    linkEl.classList.toggle('is-active', headingEl === activeHeading);
                });
            };

            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        });
    }

    function initFlashSaleCountdown() {
        document.querySelectorAll('[data-flash-sale-countdown]').forEach((container) => {
            const endsAt = container.getAttribute('data-flash-sale-ends-at');
            const label = container.querySelector('[data-flash-sale-countdown-label]');

            if (!endsAt || !(label instanceof HTMLElement)) {
                return;
            }

            const target = new Date(endsAt);

            if (Number.isNaN(target.getTime())) {
                return;
            }

            const render = () => {
                const diff = target.getTime() - Date.now();

                if (diff <= 0) {
                    label.textContent = 'Da ket thuc';

                    return;
                }

                const totalSeconds = Math.floor(diff / 1000);
                const days = Math.floor(totalSeconds / 86400);
                const hours = Math.floor((totalSeconds % 86400) / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;

                label.textContent = `${days} ngay ${hours} gio ${minutes} phut ${seconds} giay`;
            };

            render();
            window.setInterval(render, 1000);
        });
    }

    initDrawer();
    initSlider();
    initGallery();
    initQuantity();
    initProductSwatches();
    initToc();
    initTocInline();
    initFlashSaleCountdown();
})();

