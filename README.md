# Extendable Order & Payment Management API

A Laravel 13 REST API for managing orders and payments, with JWT authentication and a pluggable payment-gateway layer built on the **Strategy pattern**. New gateways can be added without modifying existing code.

- **Stack:** PHP 8.3+, Laravel 13, `php-open-source-saver/jwt-auth`, MySQL 8
- **Docs:** [`docs/Order-Payment-API.postman_collection.json`](docs/Order-Payment-API.postman_collection.json) has 16 requests and 38 success/error examples
- **Tests:** 55 unit and feature tests (`php artisan test`)
- **Code style:** PSR-12, enforced by Laravel Pint (`pint.json`)

---

## 1. Setup

```bash
git clone <repo-url> order-payment-api && cd order-payment-api
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

**Database (MySQL).** Create the database, adjust the `DB_*` values in `.env` (defaults: `127.0.0.1:3306`, user `root`, database `order_payment_api`), then migrate:

```sql
CREATE DATABASE order_payment_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate
```

**Run:**

```bash
php artisan serve          # http://localhost:8000/api
php artisan test           # run the test suite
vendor/bin/pint --test     # check PSR-12 compliance
```

**Postman:** import `docs/Order-Payment-API.postman_collection.json` and run **Authentication → Register** (or **Login**). The token is saved to the `{{token}}` variable automatically, and every other request uses it. `base_url` defaults to `http://localhost:8000/api`.

---

## 2. Endpoints

All endpoints except register and login require `Authorization: Bearer <token>`. Send `Accept: application/json` with every request.

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/auth/register` | Register and receive a JWT (rate limited) |
| POST | `/api/auth/login` | Log in and receive a JWT (rate limited) |
| GET | `/api/auth/me` | Current user |
| POST | `/api/auth/refresh` | Refresh the token |
| POST | `/api/auth/logout` | Invalidate the token |
| GET | `/api/orders?status=&per_page=` | List own orders (paginated, filter by status) |
| POST | `/api/orders` | Create an order (total calculated by the server) |
| GET | `/api/orders/{id}` | Show an order |
| PUT/PATCH | `/api/orders/{id}` | Update an order (partial) |
| DELETE | `/api/orders/{id}` | Delete an order (only if it has no payments) |
| GET | `/api/payment-methods` | Enabled gateways |
| POST | `/api/orders/{id}/payments` | Process a payment for an order |
| GET | `/api/orders/{id}/payments` | Payments for an order (paginated) |
| GET | `/api/payments?status=&method=` | All own payments (paginated, filterable) |
| GET | `/api/payments/{id}` | Show a payment |

### Status codes

| Code | When |
|---|---|
| 200 / 201 / 204 | Success, created, deleted |
| 401 | Missing, invalid, or expired token, or wrong login credentials |
| 403 | Resource belongs to another user |
| 404 | Resource not found |
| 409 | Conflicts with current state: order already paid, deleting an order that has payments, editing items after payment |
| 422 | Validation errors (`{ message, errors }`), or paying an order that isn't `confirmed` |

### Example: create an order, confirm it, and pay

```http
POST /api/orders
{ "customer_name": "Jane Doe", "customer_email": "jane@example.com",
  "items": [ { "product_name": "Keyboard", "quantity": 2, "price": 150.25 } ] }

PATCH /api/orders/1
{ "status": "confirmed" }

POST /api/orders/1/payments
{ "method": "credit_card",
  "payment_details": { "card_number": "4242424242424242", "card_holder": "Jane Doe",
                       "expiry_month": 12, "expiry_year": 2030, "cvv": "123" } }
```

---

## 3. Payment gateway extensibility

### Design

```
PaymentController ──► PaymentService ──► PaymentGatewayManager ──► PaymentGatewayInterface
   (HTTP only)        (business rules,     (resolves strategy       ├─ CreditCardGateway
                       persistence)         from config)            ├─ PaypalGateway
                                                                    └─ <YourNewGateway>
```

| Piece | Responsibility |
|---|---|
| `app/Payments/Contracts/PaymentGatewayInterface.php` | The strategy contract: `rules()` declares the gateway's own validation for `payment_details`; `charge(Order, array)` returns a `PaymentResult`. |
| `app/Payments/PaymentResult.php` | Immutable DTO (status, reference, failure reason, meta). Gateways never touch the database. |
| `app/Payments/Gateways/AbstractPaymentGateway.php` | Optional base class. It gives `$this->config('key')` access to the gateway's config block and generates references. |
| `app/Payments/PaymentGatewayManager.php` | Maps a `method` to its strategy using `config/payments.php`, skips disabled gateways, and caches instances. |
| `config/payments.php` | The gateway registry. Credentials come from `.env`. |
| `app/Services/PaymentService.php` | Enforces the business rules (order is confirmed, not already paid; row lock against double payment), calls the strategy, and stores the result. |

Because `ProcessPaymentRequest` merges `gateway->rules()` into its validation and gets the list of allowed `method` values from the manager, **adding a gateway requires no change to controllers, services, requests, or routes.**

### Adding a new gateway (example: Stripe)

**1. Create the strategy class:**

```php
// app/Payments/Gateways/StripeGateway.php
namespace App\Payments\Gateways;

use App\Models\Order;
use App\Payments\PaymentResult;

class StripeGateway extends AbstractPaymentGateway
{
    public function rules(): array
    {
        return ['payment_method_id' => ['required', 'string']];
    }

    public function charge(Order $order, array $details): PaymentResult
    {
        // $this->config('secret') holds STRIPE_SECRET from .env
        // Call the Stripe SDK here...
        return PaymentResult::successful('pi_123', ['stripe_pm' => $details['payment_method_id']]);
    }
}
```

**2. Register it in `config/payments.php`:**

```php
'stripe' => [
    'driver' => App\Payments\Gateways\StripeGateway::class,
    'enabled' => env('STRIPE_ENABLED', true),
    'secret' => env('STRIPE_SECRET'),
],
```

**3. Add the credentials to `.env`:** `STRIPE_SECRET=sk_test_...`

That's all. `"method": "stripe"` is now accepted, its `payment_details` are validated, and it appears in `GET /api/payment-methods`. To turn a gateway off without deleting code, set `<NAME>_ENABLED=false`. The `test_a_new_gateway_registered_in_config_works_end_to_end` feature test proves this flow with a throwaway gateway (`tests/Fakes/FakeWalletGateway.php`).

---

## 4. Testing

```bash
php artisan test
```

| Suite | Covers |
|---|---|
| `Unit/Payments/CreditCardGatewayTest` | Success, declined test card, expired card, only last 4 digits stored, validation rules |
| `Unit/Payments/PaypalGatewayTest` | Success, rejected account, config (`mode`) passed to the gateway |
| `Unit/Payments/PaymentGatewayManagerTest` | Strategy resolution, caching, disabled gateways, unknown method, invalid driver, a new gateway added with config only |
| `Unit/OrderTotalTest` | Total calculation including float rounding |
| `Feature/AuthTest` | Register, login, validation, wrong password, `me`, invalid token, logout invalidation |
| `Feature/OrderTest` | CRUD, server-side total, validation messages, pagination, status filter, ownership (403), 404, recalculation, item lock, delete rules |
| `Feature/PaymentTest` | Card and PayPal payments, declines recorded as failed, retry after failure, confirmed-only rule, no double payment, method and gateway-specific validation, ownership, listing and filters, a new gateway end to end |

By default tests run on in-memory SQLite (fast, no setup) with real JWTs. To run them against MySQL instead:

```bash
DB_CONNECTION=mysql DB_DATABASE=order_payment_api_test php artisan test
```

---

## 5. Assumptions & design decisions

1. **Confirming an order.** The task doesn't say how an order becomes `confirmed`. It is done through `PATCH /api/orders/{id}` with `{"status": "confirmed"}`.
2. **Ownership.** Users can see and change only their own orders and payments (`OrderPolicy`). Accessing another user's resource returns 403.
3. **Order total** is always calculated by the server from the items. Any client-supplied `total` is ignored.
4. **Failed charges are recorded.** A declined payment returns `201` with `status: "failed"` and a `failure_reason`, so there's an audit trail. A request rejected by validation or a business rule creates no payment record.
5. **No double payment.** A failed payment can be retried, but once an order has a successful payment, more attempts return `409`. Processing locks the order row (`lockForUpdate`) to prevent race conditions.
6. **Items are locked after any payment.** This keeps the paid amount consistent with the items. Other order fields can still be edited.
7. **Delete protection is enforced twice:** by a service-level check (clear 409 message) and by a `restrictOnDelete` foreign key.
8. **Payment amount** is taken from the order total at the time of processing. It is not accepted from the client.
9. **Simulation:** results are deterministic so tests are reliable. Card `4000000000000002` is declined, an expiry date in the past fails, and PayPal emails ending in `@decline.test` are rejected. Everything else succeeds. Only the last 4 card digits are stored, and CVV or full card numbers are never persisted.
10. **Order status values** are limited to `pending`, `confirmed`, and `cancelled`, as in the spec. A successful payment doesn't change the order status, because the spec defines no `paid` status. Paid state is derived from payments.
11. **Gateway configuration** uses `.env` via `config/payments.php`. A database-backed source could replace the config array passed to `PaymentGatewayManager` (registered in `AppServiceProvider`) without changing any gateway.
12. Auth endpoints are rate limited (10 requests per minute per IP).

## Regenerating the Postman collection

The collection is generated from `docs/build-postman.php` so that the examples stay consistent:

```bash
php docs/build-postman.php
```
