/**
 * CMBCORE Electro Theme JS
 * Vanilla JS — no jQuery dependency
 */
(function () {
  'use strict';

  /* ----- Mobile menu toggle ----- */
  document.addEventListener('click', function (e) {
    var toggle = e.target.closest('[data-electro-menu-toggle]');
    if (toggle) {
      e.preventDefault();
      var nav = document.getElementById('electro-responsive-nav');
      if (nav) {
        nav.classList.toggle('active');
      }
    }
  });

  /* Close mobile nav on click outside */
  document.addEventListener('click', function (e) {
    var nav = document.getElementById('electro-responsive-nav');
    if (nav && nav.classList.contains('active')) {
      if (!nav.contains(e.target) && !e.target.closest('[data-electro-menu-toggle]')) {
        nav.classList.remove('active');
      }
    }
  });

  /* ----- Quantity input +/- ----- */
  document.addEventListener('click', function (e) {
    var up = e.target.closest('.electro-qty-up');
    var down = e.target.closest('.electro-qty-down');

    if (up) {
      var input = up.closest('.electro-input-number').querySelector('input[type="number"]');
      if (input) {
        var max = input.getAttribute('max');
        var val = parseInt(input.value || '1', 10);
        if (!max || val < parseInt(max, 10)) {
          input.value = val + 1;
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    }

    if (down) {
      var input2 = down.closest('.electro-input-number').querySelector('input[type="number"]');
      if (input2) {
        var min = input2.getAttribute('min') || '1';
        var val2 = parseInt(input2.value || '1', 10);
        if (val2 > parseInt(min, 10)) {
          input2.value = val2 - 1;
          input2.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    }
  });

  /* ----- Product tabs ----- */
  document.addEventListener('click', function (e) {
    var tabLink = e.target.closest('.electro-tab-nav a');
    if (tabLink) {
      e.preventDefault();
      var tabContainer = tabLink.closest('.electro-product-tab');
      if (!tabContainer) return;

      // Deactivate all
      tabContainer.querySelectorAll('.electro-tab-nav li').forEach(function (li) {
        li.classList.remove('active');
      });
      tabContainer.querySelectorAll('.electro-tab-pane').forEach(function (pane) {
        pane.classList.remove('active');
      });

      // Activate clicked
      tabLink.closest('li').classList.add('active');
      var targetId = tabLink.getAttribute('data-tab');
      if (targetId) {
        var targetPane = tabContainer.querySelector('#' + targetId);
        if (targetPane) {
          targetPane.classList.add('active');
        }
      }
    }
  });

  /* ----- Product gallery thumbnail click ----- */
  document.addEventListener('click', function (e) {
    var thumb = e.target.closest('.electro-product-gallery__thumbs img');
    if (thumb) {
      var gallery = thumb.closest('.electro-product-gallery');
      if (!gallery) return;

      // Update main image
      var mainImg = gallery.querySelector('.electro-product-gallery__main img');
      if (mainImg) {
        mainImg.src = thumb.src;
      }

      // Update active state
      gallery.querySelectorAll('.electro-product-gallery__thumbs img').forEach(function (t) {
        t.classList.remove('active');
      });
      thumb.classList.add('active');
    }
  });

  /* ----- Smooth scroll for anchor links ----- */
  document.addEventListener('click', function (e) {
    var anchor = e.target.closest('a[href^="#"]');
    if (anchor && anchor.getAttribute('href').length > 1) {
      var target = document.querySelector(anchor.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }
  });

})();
