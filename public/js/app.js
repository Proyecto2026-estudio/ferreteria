import { API } from './api.js';
import { Cart, isValidCuit } from './cart.js';

const money = n => `$ ${Number(n).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const IMG_FALLBACK = 'public/img/cemento.svg';

document.addEventListener('DOMContentLoaded', () => {
  let currentCategory = 'todos';
  let searchQuery = '';
  let products = [];

  // Elementos DOM
  const productsGrid = document.getElementById('products-grid');
  const searchInput = document.getElementById('search-input');
  const categoryBtns = document.querySelectorAll('.category-btn');
  const modeBtns = document.querySelectorAll('.mode-btn');
  const cartTrigger = document.getElementById('cart-trigger');
  const cartBadge = document.getElementById('cart-badge');
  const cartOverlay = document.getElementById('cart-overlay');
  const cartDrawer = document.getElementById('cart-drawer');
  const cartCloseBtn = document.getElementById('cart-close-btn');
  const cartBody = document.getElementById('cart-body');
  const cartModeLabel = document.getElementById('cart-mode-label');
  const cartBreakdown = document.getElementById('cart-breakdown');
  const cartTotalPrice = document.getElementById('cart-total-price');
  const invoiceRadios = document.querySelectorAll('input[name="invoice_type"]');
  const invoiceAFields = document.getElementById('invoice-a-fields');
  const cuitInput = document.getElementById('cuit-input');
  const cuitFeedback = document.getElementById('cuit-feedback');
  const razonSocialInput = document.getElementById('razon-social-input');
  const btnCheckout = document.getElementById('btn-checkout-mp');
  const btnDemo = document.getElementById('btn-checkout-demo'); // solo existe con credenciales de prueba
  const btnClearCart = document.getElementById('btn-clear-cart');
  const toastContainer = document.getElementById('toast-container');

  function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<span>${esc(message)}</span>`;
    toastContainer.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(-100%)';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  const getInvoiceType = () => document.querySelector('input[name="invoice_type"]:checked')?.value || 'B';

  // ================= Modalidad Minorista / Mayorista =================
  function renderModeButtons() {
    const mode = Cart.getMode();
    modeBtns.forEach(btn => {
      const active = btn.dataset.mode === mode;
      btn.classList.toggle('active', active);
      btn.setAttribute('aria-checked', active ? 'true' : 'false');
    });
    cartModeLabel.textContent = mode === 'wholesale' ? 'Mayorista' : 'Minorista';
    cartModeLabel.classList.toggle('wholesale', mode === 'wholesale');
  }

  modeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      if (btn.dataset.mode === Cart.getMode()) return;
      Cart.setMode(btn.dataset.mode);
      renderModeButtons();
      renderProducts();
      showToast(btn.dataset.mode === 'wholesale'
        ? 'Lista de precios MAYORISTA activada'
        : 'Lista de precios MINORISTA activada', 'success');
    });
  });

  // ================= Catálogo =================
  async function loadProducts() {
    productsGrid.innerHTML = `
      <div style="grid-column: 1/-1; text-align: center; padding: 4rem 1rem;">
        <div class="spinner" style="margin: 0 auto 1rem auto; width: 32px; height: 32px;"></div>
        <p style="color: var(--text-muted);">Cargando catálogo de materiales...</p>
      </div>
    `;
    try {
      products = await API.getProducts(currentCategory, searchQuery);
      renderProducts();
    } catch (error) {
      productsGrid.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 4rem 1rem; color: var(--danger);">
          <p style="font-size: 1.2rem; font-weight: bold; margin-bottom: 0.5rem;">⚠️ Error al cargar catálogo</p>
          <p style="color: var(--text-muted);">${esc(error.message)}</p>
        </div>
      `;
    }
  }

  function renderProducts() {
    if (products.length === 0) {
      productsGrid.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 4rem 1rem; color: var(--text-muted);">
          <p style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔍</p>
          <p style="font-size: 1.1rem; font-weight: 600;">No se encontraron materiales</p>
          <p style="font-size: 0.9rem;">Probá con otra categoría o búsqueda.</p>
        </div>
      `;
      return;
    }

    const wholesale = Cart.getMode() === 'wholesale';

    productsGrid.innerHTML = products.map(prod => {
      const precioActivo = Cart.unitPrice(prod);
      const precioOtro = wholesale ? prod.precio : prod.precio_mayorista;
      const ahorro = prod.precio > 0 && prod.precio_mayorista > 0
        ? Math.round((1 - prod.precio_mayorista / prod.precio) * 100) : 0;
      const sinStock = prod.stock <= 0;

      return `
      <article class="product-card" data-id="${prod.id}">
        ${prod.destacado ? `<span class="card-badge-featured">⭐ Destacado</span>` : ''}
        <div class="product-image-container">
          <img src="${esc(prod.imagen_url)}" alt="${esc(prod.nombre)}" class="product-image" loading="lazy" onerror="this.onerror=null;this.src='${IMG_FALLBACK}'">
        </div>
        <div class="product-info">
          <span class="product-category">${esc(prod.categoria)}</span>
          <h3 class="product-title">${esc(prod.nombre)}</h3>
          <p class="product-description">${esc(prod.descripcion)}</p>

          <div class="price-block">
            <div class="price-main">
              <span class="product-price">${money(precioActivo)}</span>
              <span class="price-unit">/ ${esc(prod.unidad)}</span>
            </div>
            <div class="price-alt">
              ${wholesale
                ? `Minorista: <s>${money(precioOtro)}</s> · <span class="price-save">-${ahorro}%</span>`
                : (prod.precio_mayorista > 0 ? `Mayorista: ${money(precioOtro)} <span class="price-save">(-${ahorro}%)</span>` : '')}
            </div>
            <div class="price-stock">${sinStock ? 'Sin stock' : `Stock: ${prod.stock}`}</div>
          </div>

          <div class="product-footer">
            <input type="number" class="qty-input" min="1" max="${prod.stock}" value="1" aria-label="Cantidad de ${esc(prod.nombre)}" ${sinStock ? 'disabled' : ''}>
            <button class="add-to-cart-btn" data-id="${prod.id}" ${sinStock ? 'disabled' : ''}>
              ${sinStock ? 'Agotado' : '➕ Cotizar'}
            </button>
          </div>
        </div>
      </article>`;
    }).join('');

    productsGrid.querySelectorAll('.add-to-cart-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const card = e.currentTarget.closest('.product-card');
        const prodId = parseInt(e.currentTarget.dataset.id, 10);
        const qty = parseInt(card.querySelector('.qty-input').value, 10) || 1;
        const target = products.find(p => p.id === prodId);
        if (!target) return;
        try {
          Cart.addItem(target, qty);
          showToast(`Agregado: ${qty} × ${target.nombre}`, 'success');
        } catch (err) {
          showToast(err.message, 'warning');
        }
      });
    });
  }

  // ================= Drawer de la cotización =================
  function toggleCart(open = true) {
    cartOverlay.classList.toggle('open', open);
    cartDrawer.classList.toggle('open', open);
  }
  cartTrigger.addEventListener('click', () => toggleCart(true));
  cartCloseBtn.addEventListener('click', () => toggleCart(false));
  cartOverlay.addEventListener('click', () => toggleCart(false));

  function renderCart() {
    const items = Cart.getItems();
    cartBadge.textContent = Cart.getItemCount();
    renderModeButtons();

    if (items.length === 0) {
      cartBody.innerHTML = `
        <div class="cart-empty">
          <div class="cart-empty-icon">🧾</div>
          <p style="font-weight: 600; font-size: 1.1rem; color: var(--text-main); margin-bottom: 0.25rem;">Tu cotización está vacía</p>
          <p style="font-size: 0.9rem;">Agregá materiales del catálogo para cotizar.</p>
        </div>
      `;
      cartBreakdown.innerHTML = '';
      cartTotalPrice.textContent = money(0);
      btnCheckout.disabled = true;
      if (btnDemo) btnDemo.disabled = true;
      btnClearCart.style.display = 'none';
      return;
    }

    btnClearCart.style.display = 'block';

    cartBody.innerHTML = items.map(item => {
      const unit = Cart.unitPrice(item);
      return `
      <div class="cart-item" data-id="${item.id}">
        <img src="${esc(item.imagen_url)}" alt="${esc(item.nombre)}" class="cart-item-img" onerror="this.onerror=null;this.src='${IMG_FALLBACK}'">
        <div class="cart-item-details">
          <h4 class="cart-item-title">${esc(item.nombre)}</h4>
          <span class="cart-item-price">${money(unit)} <small>/ ${esc(item.unidad)}</small></span>
          <div class="cart-item-controls">
            <button class="qty-btn btn-minus" data-id="${item.id}" aria-label="Restar">-</button>
            <input class="qty-value qty-edit" type="number" min="1" max="${item.stock}" value="${item.quantity}" data-id="${item.id}" aria-label="Cantidad">
            <button class="qty-btn btn-plus" data-id="${item.id}" aria-label="Sumar">+</button>
          </div>
          <span class="cart-item-subtotal">Subtotal: ${money(unit * item.quantity)}</span>
        </div>
        <button class="remove-item-btn" data-id="${item.id}" title="Quitar producto">🗑️</button>
      </div>`;
    }).join('');

    const handle = fn => (e) => {
      const id = parseInt(e.currentTarget.dataset.id, 10);
      try { fn(id, e); } catch (err) { showToast(err.message, 'warning'); renderCart(); }
    };
    cartBody.querySelectorAll('.btn-minus').forEach(b => b.addEventListener('click', handle(id => Cart.updateQuantity(id, -1))));
    cartBody.querySelectorAll('.btn-plus').forEach(b => b.addEventListener('click', handle(id => Cart.updateQuantity(id, 1))));
    cartBody.querySelectorAll('.qty-edit').forEach(i => i.addEventListener('change', handle((id, e) => Cart.setQuantity(id, e.currentTarget.value))));
    cartBody.querySelectorAll('.remove-item-btn').forEach(b => b.addEventListener('click', handle(id => Cart.removeItem(id))));

    renderTotals();
  }

  function renderTotals() {
    if (Cart.getItems().length === 0) return;
    const { total, neto, iva } = Cart.getBreakdown();
    const invoice = getInvoiceType();
    const savings = Cart.getWholesaleSavings();
    const wholesale = Cart.getMode() === 'wholesale';

    let html = '';
    if (invoice === 'A') {
      html += `
        <div class="breakdown-row"><span>Neto gravado</span><span>${money(neto)}</span></div>
        <div class="breakdown-row"><span>IVA 21%</span><span>${money(iva)}</span></div>`;
    } else {
      html += `<div class="breakdown-row muted"><span>Factura B · IVA incluido en el precio</span></div>`;
    }
    if (wholesale && savings > 0) {
      html += `<div class="breakdown-row save"><span>Ahorro mayorista</span><span>- ${money(savings)}</span></div>`;
    } else if (!wholesale && savings > 0) {
      html += `<div class="breakdown-row hint"><span>En mayorista pagarías ${money(Cart.getTotal('wholesale'))}</span></div>`;
    }
    cartBreakdown.innerHTML = html;
    cartTotalPrice.textContent = money(total);
    updateCheckoutState();
  }

  // ================= Factura A / B =================
  function validateInvoice(showFeedback = false) {
    if (getInvoiceType() !== 'A') return true;
    const cuitOk = isValidCuit(cuitInput.value);
    const razonOk = razonSocialInput.value.trim().length >= 3;
    if (showFeedback || cuitInput.value.replace(/\D/g, '').length === 11) {
      cuitFeedback.textContent = cuitInput.value.trim() === '' ? '' : (cuitOk ? '✔ CUIT válido' : '✖ CUIT inválido (revisá el dígito verificador)');
      cuitFeedback.className = `cuit-feedback ${cuitOk ? 'ok' : 'error'}`;
    } else {
      cuitFeedback.textContent = '';
    }
    cuitInput.classList.toggle('invalid', cuitInput.value !== '' && !cuitOk && cuitInput.value.replace(/\D/g, '').length === 11);
    return cuitOk && razonOk;
  }

  function updateCheckoutState() {
    const hasItems = Cart.getItems().length > 0;
    btnCheckout.disabled = !hasItems || !validateInvoice();
    if (btnDemo) btnDemo.disabled = btnCheckout.disabled;
  }

  invoiceRadios.forEach(r => r.addEventListener('change', () => {
    const isA = getInvoiceType() === 'A';
    invoiceAFields.hidden = !isA;
    if (isA) cuitInput.focus();
    renderTotals();
    updateCheckoutState();
  }));

  // Formatea el CUIT mientras se escribe: 20-12345678-9
  cuitInput.addEventListener('input', () => {
    const d = cuitInput.value.replace(/\D/g, '').slice(0, 11);
    let f = d;
    if (d.length > 2) f = d.slice(0, 2) + '-' + d.slice(2);
    if (d.length > 10) f = f.slice(0, 11) + '-' + d.slice(10);
    cuitInput.value = f;
    updateCheckoutState();
  });
  cuitInput.addEventListener('blur', () => validateInvoice(true));
  razonSocialInput.addEventListener('input', updateCheckoutState);

  Cart.subscribe(renderCart);
  renderCart();

  btnClearCart.addEventListener('click', () => {
    if (confirm('¿Vaciar todos los materiales de la cotización?')) Cart.clearCart();
  });

  // ================= Checkout (Mercado Pago o demo) =================
  async function checkout(demo, button) {
    const items = Cart.getItems();
    if (items.length === 0) return;

    const invoiceType = getInvoiceType();
    if (!validateInvoice(true)) {
      showToast('Para Factura A completá un CUIT válido y la razón social.', 'warning');
      return;
    }

    btnCheckout.disabled = true;
    if (btnDemo) btnDemo.disabled = true;
    const originalText = button.innerHTML;
    button.innerHTML = `<div class="spinner"></div><span>${demo ? 'Procesando pago demo...' : 'Generando Pago...'}</span>`;

    try {
      const response = await API.createCheckoutPreference(items, {
        mode: Cart.getMode(),
        invoice_type: invoiceType,
        cuit: invoiceType === 'A' ? cuitInput.value : '',
        razon_social: invoiceType === 'A' ? razonSocialInput.value.trim() : '',
        demo
      });
      if (response.demo) {
        Cart.clearCart();
        showToast('¡Pago de demostración aprobado!', 'success');
      } else {
        showToast('¡Redirigiendo a Mercado Pago!', 'success');
      }
      const redirectUrl = response.init_point || response.sandbox_init_point;
      setTimeout(() => { window.location.href = redirectUrl; }, 800);
    } catch (error) {
      showToast(error.message || 'Error al conectar con la pasarela de pago.', 'danger');
      button.innerHTML = originalText;
      updateCheckoutState();
    }
  }

  btnCheckout.addEventListener('click', () => checkout(false, btnCheckout));
  if (btnDemo) btnDemo.addEventListener('click', () => checkout(true, btnDemo));

  // ================= Filtros =================
  categoryBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      categoryBtns.forEach(b => b.classList.remove('active'));
      e.currentTarget.classList.add('active');
      currentCategory = e.currentTarget.dataset.category;
      loadProducts();
    });
  });

  let searchTimeout = null;
  searchInput.addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      searchQuery = e.target.value.trim();
      loadProducts();
    }, 350);
  });

  renderModeButtons();
  loadProducts();
});
