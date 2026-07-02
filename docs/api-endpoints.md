# E-Commerce API Endpoints (Fast Food Angular App)

Base URL:
- Local: `http://127.0.0.1:8000`
- Versioned prefix: `/api/v1`

Common headers:
- `Content-Type: application/json`
- `Accept: application/json`
- Protected endpoints require: `Authorization: Bearer <token>`

## Auth

### `POST /api/v1/auth/register`
Payload:
```json
{
  "name": "Imran",
  "email": "imran@example.com",
  "password": "Password@123",
  "password_confirmation": "Password@123"
}
```

### `POST /api/v1/auth/login`
Payload:
```json
{
  "email": "imran@example.com",
  "password": "Password@123"
}
```

### `POST /api/v1/auth/forgot-password`
Payload:
```json
{
  "email": "imran@example.com"
}
```

### `POST /api/v1/auth/reset-password`
Payload:
```json
{
  "token": "reset_token_from_email",
  "email": "imran@example.com",
  "password": "NewPassword@123",
  "password_confirmation": "NewPassword@123"
}
```

### `GET /api/v1/auth/me` (protected)
No payload.

### `POST /api/v1/auth/logout` (protected)
No payload.

## Food Catalog

### `GET /api/v1/food-items`
No payload. Returns available food items.

### `GET /api/v1/food-items/{foodItem}`
No payload. Returns one item if available.

## Cart (protected)

### `GET /api/v1/cart`
No payload. Returns cart items and subtotal.

### `POST /api/v1/cart/items`
Payload:
```json
{
  "food_item_id": 1,
  "quantity": 2
}
```

### `PATCH /api/v1/cart/items/{item}`
Payload:
```json
{
  "quantity": 3
}
```

### `DELETE /api/v1/cart/items/{item}`
No payload.

### `DELETE /api/v1/cart/clear`
No payload.

## Shipping Addresses (protected)

### `GET /api/v1/shipping-addresses`
No payload.

### `POST /api/v1/shipping-addresses`
Payload:
```json
{
  "label": "Home",
  "recipient_name": "Imran",
  "phone": "+91-9876543210",
  "address_line_1": "123 Street",
  "address_line_2": "Near Park",
  "city": "Mumbai",
  "state": "Maharashtra",
  "postal_code": "400001",
  "country": "India",
  "is_default": true
}
```

### `PUT /api/v1/shipping-addresses/{shippingAddress}`
Payload: same as create, partial fields allowed.

### `DELETE /api/v1/shipping-addresses/{shippingAddress}`
No payload.

## Checkout & Orders (protected)

### `POST /api/v1/checkout`
Payload:
```json
{
  "shipping_address_id": 1,
  "payment_method": "cod",
  "notes": "Deliver fast please"
}
```

`payment_method` allowed values: `cod`, `card`, `wallet`

### `GET /api/v1/orders`
No payload. Returns user order history.

### `GET /api/v1/orders/{order}`
No payload. Returns order details with items.

### `GET /api/v1/orders/{order}/confirmation`
No payload. Returns confirmation summary for placed order.

## Health

### `GET /api/v1/health`
No payload.
