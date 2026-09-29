/**
 * Carrito / Cotizador de la Ferretería (localStorage)
 * - Guarda ambos precios (minorista y mayorista) de cada producto
 * - La modalidad activa (retail / wholesale) decide qué precio se usa
 * - Los precios de lista incluyen IVA 21%
 */
const STORAGE_KEY = 'ferreteria_eq8_cart_v1';
const MODE_KEY = 'ferreteria_eq8_mode_v1';
export const IVA_RATE = 0.21;

class CartStore {
  constructor() {
    this.items = this.load(STORAGE_KEY, []);
    this.mode = this.load(MODE_KEY, 'retail') === 'wholesale' ? 'wholesale' : 'retail';
    this.listeners = [];
  }

  load(key, fallback) {
    try {
      const stored = localStorage.getItem(key);
      return stored ? JSON.parse(stored) : fallback;
    } catch (e) {
      return fallback;
    }
  }

  save() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(this.items));
      localStorage.setItem(MODE_KEY, JSON.stringify(this.mode));
    } catch (e) {
      console.error('No se pudo guardar el carrito:', e);
    }
  }

  subscribe(listener) {
    this.listeners.push(listener);
  }

  notify() {
    this.save();
    this.listeners.forEach(fn => fn(this));
  }

  // ---------- Modalidad Minorista / Mayorista ----------
  getMode() {
    return this.mode;
  }

  setMode(mode) {
    this.mode = mode === 'wholesale' ? 'wholesale' : 'retail';
    this.notify();
  }

  /** Precio unitario de un producto según la modalidad actual */
  unitPrice(item, mode = this.mode) {
    const mayorista = parseFloat(item.precio_mayorista) || 0;
    return mode === 'wholesale' && mayorista > 0 ? mayorista : parseFloat(item.precio);
  }

  // ---------- Ítems ----------
  getItems() {
    return [...this.items];
  }

  addItem(product, qty = 1) {
    qty = Math.max(1, parseInt(qty, 10) || 1);
    const existing = this.items.find(item => item.id === product.id);
    const current = existing ? existing.quantity : 0;

    if (product.stock < 1) {
      throw new Error('Producto sin stock disponible.');
    }
    if (current + qty > product.stock) {
      throw new Error(`Solo hay ${product.stock} ${product.unidad || 'u.'} disponibles de "${product.nombre}".`);
    }

    if (existing) {
      existing.quantity += qty;
    } else {
      this.items.push({
        id: product.id,
        nombre: product.nombre,
        unidad: product.unidad || 'unidad',
        precio: parseFloat(product.precio),
        precio_mayorista: parseFloat(product.precio_mayorista) || 0,
        imagen_url: product.imagen_url,
        stock: product.stock,
        quantity: qty
      });
    }
    this.notify();
  }

  setQuantity(productId, qty) {
    const item = this.items.find(i => i.id === productId);
    if (!item) return;
    qty = parseInt(qty, 10) || 0;
    if (qty <= 0) {
      this.removeItem(productId);
      return;
    }
    if (qty > item.stock) {
      throw new Error(`Solo hay ${item.stock} unidades disponibles.`);
    }
    item.quantity = qty;
    this.notify();
  }

  updateQuantity(productId, delta) {
    const item = this.items.find(i => i.id === productId);
    if (item) this.setQuantity(productId, item.quantity + delta);
  }

  removeItem(productId) {
    this.items = this.items.filter(item => item.id !== productId);
    this.notify();
  }

  clearCart() {
    this.items = [];
    this.notify();
  }

  // ---------- Totales ----------
  getTotal(mode = this.mode) {
    return this.items.reduce((t, item) => t + this.unitPrice(item, mode) * item.quantity, 0);
  }

  /** Cuánto se ahorra comprando en modalidad mayorista */
  getWholesaleSavings() {
    return this.getTotal('retail') - this.getTotal('wholesale');
  }

  /** Desglose fiscal: el total ya incluye IVA; se calcula el neto gravado */
  getBreakdown() {
    const total = Math.round(this.getTotal() * 100) / 100;
    const neto = Math.round((total / (1 + IVA_RATE)) * 100) / 100;
    return { total, neto, iva: Math.round((total - neto) * 100) / 100 };
  }

  getItemCount() {
    return this.items.reduce((count, item) => count + item.quantity, 0);
  }
}

export const Cart = new CartStore();

/** Validación de CUIT (formato + dígito verificador), igual a la del backend */
export function isValidCuit(raw) {
  const d = String(raw || '').replace(/\D/g, '');
  if (d.length !== 11) return false;
  const mult = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
  let sum = 0;
  for (let i = 0; i < 10; i++) sum += parseInt(d[i], 10) * mult[i];
  let check = 11 - (sum % 11);
  if (check === 11) check = 0;
  if (check === 10) return false;
  return check === parseInt(d[10], 10);
}
