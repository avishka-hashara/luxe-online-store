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
});
