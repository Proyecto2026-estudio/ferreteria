# Mercado Pago — cómo conectar las credenciales (Equipo 8)

## 1. Conseguir las credenciales de prueba
1. Entrá a https://www.mercadopago.com.ar/developers/es/docs/your-integrations/credentials con la cuenta del equipo.
2. En **Tus integraciones**, abrí (o creá) la aplicación de la ferretería.
3. En **Credenciales de prueba** copiá la **Public Key** y el **Access Token**.

## 2. Cargarlas en el proyecto
1. Copiá `env.txt` y renombrá la copia a `.env` (o `.env.example`, como indica el profe).
2. Completá:
   ```
   MP_ACCESS_TOKEN=TEST-...
   MP_PUBLIC_KEY=TEST-...
   BASE_URL=http://localhost/2026/Antigravity
   ```
   `BASE_URL` tiene que ser la dirección donde abren el proyecto en el navegador.
3. Si no existe `.env`, `env.php` usa las credenciales de prueba que ya están escritas ahí.

## 3. Probar un pago
1. Abrí la tienda, elegí **Minorista** o **Mayorista** y agregá materiales.
2. En la cotización elegí **Factura B**, o **Factura A** con un CUIT válido (ej. `20-12345678-6`) y razón social.
3. **Pagar con Mercado Pago** te lleva al checkout. Pagá con una tarjeta de prueba:
   https://www.mercadopago.com.ar/developers/es/docs/checkout-pro/additional-content/your-integrations/test/cards
4. En **Admin → Historial de Órdenes** vas a ver la orden con modalidad, comprobante, CUIT e IVA.

> En `localhost` Mercado Pago no puede avisar el pago por webhook, así que la orden queda en
> `pending` hasta que el proyecto esté publicado en un dominio con HTTPS.
