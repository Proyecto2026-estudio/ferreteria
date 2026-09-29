<?php
require_once __DIR__ . '/env.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ferretería y Corralón Equipo 8 - Cotizador Online</title>
  <meta name="description" content="Ferretería y materiales de construcción. Cotizá con precios minoristas o mayoristas, elegí Factura A o B y pagá con Mercado Pago.">
  
  <!-- CSS Principal -->
  <link rel="stylesheet" href="public/css/styles.css">
  
  <!-- Favicon e Iconos -->
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🛠️</text></svg>">
</head>
<body>

  <!-- Encabezado / Navegación Principal -->
  <header class="navbar">
    <div class="nav-container">
      <a href="index.php" class="logo">
        <div class="logo-icon">🛠️</div>
        <div>
          <span class="logo-text">Ferretería Equipo 8</span>
          <span class="logo-badge">MATERIALES DE CONSTRUCCIÓN</span>
        </div>
      </a>

      <!-- Buscador de Productos -->
      <div class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" id="search-input" class="search-input" placeholder="Buscar cemento, hierro, pintura...">
      </div>

      <!-- Botón de Carrito con Contador Dinámico -->
      <button id="cart-trigger" class="cart-trigger" aria-label="Abrir Carrito">
        <span>🧾 Mi Cotización</span>
        <span id="cart-badge" class="cart-badge">0</span>
      </button>
    </div>
  </header>

  <!-- Contenido Principal -->
  <main class="main-content">
    <!-- Cotizador: selector de modalidad de precio -->
    <section class="price-mode-bar" aria-label="Modalidad de precio">
      <div>
        <h1 class="price-mode-title">Cotizador de materiales</h1>
        <p class="price-mode-help">Elegí cómo comprás. Los precios incluyen IVA.</p>
      </div>
      <div class="price-mode-toggle" role="radiogroup" aria-label="Lista de precios">
        <button type="button" class="mode-btn" data-mode="retail" role="radio">
          <strong>Minorista</strong><span>Particulares y obras chicas</span>
        </button>
        <button type="button" class="mode-btn" data-mode="wholesale" role="radio">
          <strong>Mayorista</strong><span>Constructoras y revendedores</span>
        </button>
      </div>
    </section>

    <!-- Barra de Filtro de Categorías -->
    <nav class="categories-bar" aria-label="Categorías de productos">
      <button class="category-btn active" data-category="todos">✨ Todos los productos</button>
      <button class="category-btn" data-category="Cemento y Áridos">🧱 Cemento y Áridos</button>
      <button class="category-btn" data-category="Hierros y Perfiles">🔩 Hierros y Perfiles</button>
      <button class="category-btn" data-category="Ladrillos y Bloques">🏗️ Ladrillos y Bloques</button>
      <button class="category-btn" data-category="Pinturas">🎨 Pinturas</button>
      <button class="category-btn" data-category="Tornillería y Fijaciones">🪛 Tornillería y Fijaciones</button>
      <button class="category-btn" data-category="Caños y Membranas">🚿 Caños y Membranas</button>
    </nav>

    <!-- Grilla Adaptativa de Productos -->
    <section id="products-grid" class="products-grid">
      <!-- Los productos se cargan dinámicamente vía JS -->
    </section>
  </main>

  <!-- Overlay y Drawer Deslizable del Carrito -->
  <div id="cart-overlay" class="cart-overlay"></div>
  
  <aside id="cart-drawer" class="cart-drawer" aria-label="Carrito de Compras">
    <div class="cart-header">
      <h2 class="cart-title">🧾 Tu Cotización <span id="cart-mode-label" class="cart-mode-label">Minorista</span></h2>
      <button id="cart-close-btn" class="cart-close-btn" aria-label="Cerrar Carrito">&times;</button>
    </div>

    <div id="cart-body" class="cart-body">
      <!-- Los ítems del carrito se inyectan dinámicamente -->
    </div>

    <div class="cart-footer">
      <!-- Comprobante: Factura A o B -->
      <fieldset id="invoice-box" class="invoice-box">
        <legend>Comprobante</legend>
        <div class="invoice-options">
          <label class="invoice-option">
            <input type="radio" name="invoice_type" value="B" checked>
            <span><strong>Factura B</strong><small>Consumidor final / Monotributo</small></span>
          </label>
          <label class="invoice-option">
            <input type="radio" name="invoice_type" value="A">
            <span><strong>Factura A</strong><small>Responsable Inscripto (con CUIT)</small></span>
          </label>
        </div>
        <div id="invoice-a-fields" class="invoice-a-fields" hidden>
          <label for="cuit-input">CUIT</label>
          <input type="text" id="cuit-input" inputmode="numeric" maxlength="13" placeholder="20-12345678-9" autocomplete="off">
          <small id="cuit-feedback" class="cuit-feedback"></small>
          <label for="razon-social-input">Razón social</label>
          <input type="text" id="razon-social-input" maxlength="150" placeholder="Ej. Construcciones del Sur S.R.L.">
        </div>
      </fieldset>

      <div id="cart-breakdown" class="cart-breakdown"></div>

      <div class="cart-summary-row">
        <span style="color: var(--text-muted); font-weight: 500;">Total a abonar:</span>
        <span id="cart-total-price" class="cart-total-price">$ 0,00</span>
      </div>

      <!-- Botón Oficial de Checkout Mercado Pago -->
      <button id="btn-checkout-mp" class="btn-checkout-mp" disabled>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM16.5 15.5H14.5L13.2 12.8C13.1 12.6 12.9 12.5 12.7 12.5H10.5V15.5H8.5V8.5H12.8C13.8 8.5 14.7 8.9 15.3 9.6C15.9 10.3 16.2 11.2 16.2 12.2C16.2 13.5 15.5 14.7 14.4 15.2L16.5 15.5Z" fill="white"/>
        </svg>
        <span>Pagar con Mercado Pago</span>
      </button>

      <button id="btn-clear-cart" class="btn-clear-cart">
        Vaciar Cotización
      </button>
    </div>
  </aside>

  <!-- Contenedor de Notificaciones Toast -->
  <div id="toast-container" class="toast-container"></div>

  <!-- Scripts JavaScript ES Modules -->
  <script type="module" src="public/js/app.js"></script>
</body>
</html>
