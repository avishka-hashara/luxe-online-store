// ============================================
// LUXE STORE — Main JavaScript
// ============================================

document.addEventListener('DOMContentLoaded', () => {

  // ---- Flash message auto-dismiss ----
  const flashes = document.querySelectorAll('.flash');
  flashes.forEach(el => {
    setTimeout(() => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(-6px)';
      el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      setTimeout(() => el.remove(), 400);
    }, 4000);
  });

  // ---- Category filter (homepage) ----
  const catBtns = document.querySelectorAll('.cat-btn');
  const cards   = document.querySelectorAll('.product-card[data-category]');

  catBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      catBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const selected = btn.dataset.cat;
      cards.forEach(card => {
        if (selected === 'all' || card.dataset.category === selected) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });

  // ---- Confirm dialogs ----
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

  // ---- Password strength indicator ----
  const pwInput = document.getElementById('password');
  const pwStrength = document.getElementById('pw-strength');
  if (pwInput && pwStrength) {
    pwInput.addEventListener('input', () => {
      const val = pwInput.value;
      let strength = 0;
      if (val.length >= 8) strength++;
      if (/[A-Z]/.test(val)) strength++;
      if (/[0-9]/.test(val)) strength++;
      if (/[^A-Za-z0-9]/.test(val)) strength++;
      const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
      const colors = ['', '#e87a6f', '#f9ca5f', '#7abfe8', '#6fcf97'];
      pwStrength.textContent = labels[strength] || '';
      pwStrength.style.color = colors[strength] || '';
    });
  }

  // ---- Toggle password visibility ----
  document.querySelectorAll('.toggle-pw').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.previousElementSibling;
      input.type = input.type === 'password' ? 'text' : 'password';
      btn.textContent = input.type === 'password' ? '👁' : '🙈';
    });
  });

  // ---- Smooth scroll reveal ----
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.style.opacity = '1';
          e.target.style.transform = 'translateY(0)';
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.product-card').forEach((el, i) => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(20px)';
      el.style.transition = `opacity 0.5s ease ${i * 0.06}s, transform 0.5s ease ${i * 0.06}s`;
      io.observe(el);
    });
  }

  // ---- Admin: confirm delete actions ----
  document.querySelectorAll('form[data-confirm-form]').forEach(form => {
    form.addEventListener('submit', e => {
      if (!confirm(form.dataset.confirmForm)) e.preventDefault();
    });
  });

  // ============================================
  // CART FUNCTIONALITY
  // ============================================

  const csrfToken   = () => document.getElementById('csrf_token')?.value || '';
  const cartBadge   = document.getElementById('cart-badge');
  const cartApiUrl  = window.SITE_URL ? window.SITE_URL + '/api/cart.php' : '/api/cart.php';

  // Resolve the cart API URL dynamically from the current hostname
  function getCartUrl() {
    // Remove the filename, then strip /pages or /admin dir to reach site root
    const base = window.location.pathname
      .replace(/\/[^\/]+$/, '')         // /store/index.php  → /store
      .replace(/\/(pages|admin)$/, ''); // /store/pages      → /store
    return (base || '') + '/api/cart.php';
  }

  function updateBadge(count) {
    if (!cartBadge) return;
    if (count > 0) {
      cartBadge.textContent = count;
      cartBadge.style.display = '';
    } else {
      cartBadge.style.display = 'none';
    }
  }

  // ---- Add to Cart (homepage) ----
  document.querySelectorAll('.btn-add-cart').forEach(btn => {
    btn.addEventListener('click', async () => {
      const productId = btn.dataset.id;
      const name      = btn.dataset.name;
      const token     = csrfToken();
      if (!token) return;

      btn.classList.add('adding');
      btn.textContent = 'Adding…';

      const body = new FormData();
      body.append('action',     'add');
      body.append('product_id', productId);
      body.append('quantity',   '1');
      body.append('_csrf_token', token);

      try {
        const res  = await fetch(getCartUrl(), { method: 'POST', body });
        const data = await res.json();

        if (data.success) {
          btn.textContent = 'Added ✓';
          updateBadge(data.cart_count);
          setTimeout(() => {
            btn.classList.remove('adding');
            btn.textContent = 'Add to Cart';
          }, 1800);
        } else {
          btn.classList.remove('adding');
          btn.textContent = 'Add to Cart';
          alert(data.message || 'Could not add item.');
        }
      } catch {
        btn.classList.remove('adding');
        btn.textContent = 'Add to Cart';
        alert('Network error. Please try again.');
      }
    });
  });

  // ============================================
  // CART PAGE — quantity controls & remove
  // ============================================

  async function cartUpdate(productId, qty) {
    const token = csrfToken();
    const body  = new FormData();
    body.append('action',      'update');
    body.append('product_id',  productId);
    body.append('quantity',    qty);
    body.append('_csrf_token', token);

    const res  = await fetch(getCartUrl(), { method: 'POST', body });
    const data = await res.json();
    if (data.success) {
      updateBadge(data.cart_count);
      // Update item total
      const totalEl = document.getElementById('item-total-' + productId);
      if (totalEl) {
        if (qty <= 0) {
          document.getElementById('cart-row-' + productId)?.remove();
        } else {
          totalEl.textContent = 'Rs. ' + data.item_total;
        }
      }
      // Update summary
      const subEl  = document.getElementById('summary-subtotal');
      const totEl  = document.getElementById('summary-total');
      const shipEl = document.getElementById('summary-shipping');
      if (subEl && data.subtotal !== undefined) {
        const sub      = parseFloat(data.subtotal.replace(/,/g, ''));
        const shipping = sub > 0 ? 350 : 0;
        subEl.textContent  = 'Rs. ' + data.subtotal;
        if (shipEl) shipEl.textContent = 'Rs. ' + (shipping === 0 ? '0.00' : '350.00');
        if (totEl)  totEl.textContent  = 'Rs. ' + formatRs(sub + shipping);
      }
      // Show empty state if last item removed
      if (data.cart_count === 0) location.reload();
    }
  }

  function formatRs(n) {
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // Qty increment / decrement buttons
  document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id    = btn.dataset.id;
      const input = document.getElementById('qty-' + id);
      if (!input) return;
      let val = parseInt(input.value, 10);
      const max = parseInt(input.max, 10) || 999;

      if (btn.dataset.action === 'inc') val = Math.min(val + 1, max);
      else                              val = Math.max(val - 1, 0);

      input.value = val;
      cartUpdate(id, val);
    });
  });

  // Manual input change
  document.querySelectorAll('.qty-input').forEach(input => {
    input.addEventListener('change', () => {
      const id  = input.dataset.id;
      let   val = parseInt(input.value, 10);
      const max = parseInt(input.max, 10) || 999;
      if (isNaN(val) || val < 0) val = 0;
      if (val > max) val = max;
      input.value = val;
      cartUpdate(id, val);
    });
  });

  // Remove button
  document.querySelectorAll('.cart-remove').forEach(btn => {
    btn.addEventListener('click', () => cartUpdate(btn.dataset.id, 0));
  });

  // ============================================
  // CHECKOUT PAGE — payment method toggle
  // ============================================
  const paymentHidden = document.getElementById('payment_method_hidden');
  const cardForm      = document.getElementById('card-form');

  document.querySelectorAll('input[name="payment_choice"]').forEach(radio => {
    radio.addEventListener('change', () => {
      const val = radio.value;
      if (paymentHidden) paymentHidden.value = val;
      if (cardForm)      cardForm.style.display = val === 'card' ? 'block' : 'none';
    });
    // Handle initial state on load
    if (radio.checked) {
      if (paymentHidden) paymentHidden.value = radio.value;
      if (cardForm)      cardForm.style.display = radio.value === 'card' ? 'block' : 'none';
    }
  });

  // Card number: auto-space every 4 digits
  const cardNumberInput = document.getElementById('card_number');
  if (cardNumberInput) {
    cardNumberInput.addEventListener('input', () => {
      let val = cardNumberInput.value.replace(/\D/g, '').substring(0, 16);
      cardNumberInput.value = val.replace(/(.{4})/g, '$1 ').trim();
    });
  }

  // Card expiry: auto-add slash
  const cardExpiryInput = document.getElementById('card_expiry');
  if (cardExpiryInput) {
    cardExpiryInput.addEventListener('input', (e) => {
      let val = cardExpiryInput.value.replace(/\D/g, '').substring(0, 4);
      if (val.length > 2) val = val.substring(0, 2) + '/' + val.substring(2);
      cardExpiryInput.value = val;
    });
  }

  // Place order button loading state
  const checkoutForm   = document.getElementById('checkout-form');
  const placeOrderBtn  = document.getElementById('place-order-btn');
  if (checkoutForm && placeOrderBtn) {
    checkoutForm.addEventListener('submit', () => {
      placeOrderBtn.textContent = 'Processing…';
      placeOrderBtn.disabled    = true;
    });
  }
});
