# Exim India – API Reference (for frontend integration)

Base URL (dev): `http://localhost:8000/api`
Production: `https://<your-domain>/api`

All responses are JSON. The examples below are real responses from the seeded database (long lists are trimmed).

## 0. Ground rules

| Rule | Detail |
|---|---|
| **Always send** | `Accept: application/json` (without it, Laravel may redirect validation errors instead of returning JSON) |
| **POST/PUT bodies** | `Content-Type: application/json` (except image upload, which is `multipart/form-data`) |
| **Public endpoints** | No auth needed |
| **Admin endpoints** | `Authorization: Bearer <token>` from `POST /admin/login` |
| **CORS** | Allowed origin is `FRONTEND_URL` in the backend `.env` (default `http://localhost:5173`). Add your production origin there, comma-separated for several |
| **Images** | Returned as absolute URLs (`http://.../storage/products/...`). Use them directly in `<img src>` |
| **Keys** | Public API is **camelCase**. Admin API returns raw model fields in **snake_case** |
| **Missing values** | Always `null`, never omitted (every product has exactly the same keys, whatever its category) |

### Error formats

| Status | When | Body |
|---|---|---|
| 422 | Validation failed | `{"message": "The name field is required. (and 2 more errors)", "errors": {"name": ["The name field is required."], "email": [...]}}` |
| 401 | Missing/invalid admin token | `{"message": "Unauthenticated."}` |
| 403 | Logged in but not an admin | `{"message": "Admin access required."}` |
| 404 | Unknown category/product | `{"message": "No query results for model [App\\Models\\Category]."}` (in production; with `APP_DEBUG=true` the body also carries a stack trace) |
| 429 | Rate limit hit (`/contact`, `/orders`: 5 per minute per IP) | `{"message": "Too Many Requests."}` |

A small fetch helper that covers all of this:

```js
const API = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api'

export async function api(path, { method = 'GET', body, token } = {}) {
  const res = await fetch(`${API}${path}`, {
    method,
    headers: {
      Accept: 'application/json',
      ...(body && { 'Content-Type': 'application/json' }),
      ...(token && { Authorization: `Bearer ${token}` }),
    },
    body: body ? JSON.stringify(body) : undefined,
  })
  if (res.status === 204) return null
  const data = await res.json()
  if (!res.ok) throw Object.assign(new Error(data.message), { status: res.status, errors: data.errors })
  return data
}
```

---

## 1. Public API

### 1.1 List categories (navbar dropdown / Products page)

`GET /categories`

Returns active categories in navbar order. Does **not** include products, specs or sections (use 1.2 for those).

```bash
curl -H "Accept: application/json" http://localhost:8000/api/categories
```

```json
{
  "data": [
    {
      "id": 1,
      "name": "Synthetic food colours",
      "slug": "synthetic-food-colours",
      "path": "/products/synthetic-food-colours",
      "description": null,
      "image": null,
      "productCount": 15
    }
  ]
}
```

`path` matches your React route, so `<Link to={category.path}>` works directly. `productCount` is 0 for Chemicals and Botanical Extracts.

```js
const { data: categories } = await api('/categories')
```

### 1.2 Category detail (page header + spec table + extra sections)

`GET /categories/{slug}`

```bash
curl -H "Accept: application/json" http://localhost:8000/api/categories/edible-lustre
```

```json
{
  "data": {
    "id": 18,
    "name": "Edible Lustre",
    "slug": "edible-lustre",
    "path": "/products/edible-lustre",
    "description": null,
    "image": null,
    "analysis": [
      { "characteristic": "Heavy Metals (as Pb), Max.", "requirement": "10.00 ppm" }
    ],
    "sections": [
      {
        "key": "application-techniques",
        "title": "Application Techniques",
        "items": [
          {
            "title": "Dry Dusting",
            "subtitle": null,
            "colorHex": null,
            "attributes": [
              { "label": "Method", "value": "Apply directly with a soft dry confectionery brush onto dry surfaces." },
              { "label": "Best For", "value": "Fondant accents, gum paste flower petals, royal icing." },
              { "label": "Tip", "value": "Gives a soft, pearlescent, translucent shimmer." }
            ]
          }
        ]
      }
    ]
  }
}
```

| Field | Meaning |
|---|---|
| `analysis` | Default "Specification & Quality Analysis" rows for the category (may be `[]`) |
| `sections` | Extra content blocks (application guides, comparison tables, name lists). May be `[]`. Every item has the same 4 keys; render `attributes` with one generic `label: value` loop. A plain-name list (e.g. flavour names) has only `title` and `attributes: []` |

### 1.3 List products in a category

`GET /categories/{slug}/products`

| Query param | Description |
|---|---|
| `group_key` | Optional filter, e.g. `?group_key=yellow` (use `groupKey` values from the products to build filter chips) |

```bash
curl -H "Accept: application/json" \
  "http://localhost:8000/api/categories/synthetic-food-colours/products?group_key=yellow"
```

Response: `{ "data": [ <product>, <product>, ... ] }` where each `<product>` has the shape in 1.4 (no pagination; the largest category has 20 products).

### 1.4 Product detail

`GET /categories/{slug}/products/{productId}`

`{productId}` is the product's `id` field (its slug, e.g. `tartrazine`), which matches your current URLs like `/products/blended-colours/egg-yellow`.

```bash
curl -H "Accept: application/json" \
  http://localhost:8000/api/categories/synthetic-food-colours/products/tartrazine
```

```json
{
  "data": {
    "id": "tartrazine",
    "productId": 1,
    "name": "Tartrazine",
    "category": { "name": "Synthetic food colours", "slug": "synthetic-food-colours" },
    "groupKey": "yellow",
    "groupName": "Yellow",
    "colorHex": "#facc15",
    "image": "http://localhost:8000/storage/products/synthesis-food-color/high-quality-Tartrazine.jpg",
    "description": "Bright lemon-yellow azo dye known for outstanding water solubility...",
    "moq": "250 Kgs",
    "supplyAbility": "20 Metric Tons / Month",
    "port": "JNPT, (Nhava Sheva) Mumbai",
    "casNo": "1934-21-0",
    "otherNames": null,
    "mf": "C16H9N4Na3O9S2",
    "einecsNo": null,
    "femaNo": "Tartrazine Yellow",
    "placeOfOrigin": "Maharashtra, India",
    "types": "Colorants, Emulsifiers, Flavoring Agents, food color, pharma",
    "brandName": "Exim India Corporation",
    "modelNumber": "19140",
    "grade": "Food Grade, Pharma, Cosmetic",
    "colorDesc": "Yellow Powder",
    "applicationSummary": "Bakery, Food additive , Confectionery",
    "purity": "99% Min",
    "shelfLife": "3 Years",
    "packagingDetails": "10/25/50 HDPE DRUMS, BAGS We can also ship by Air and Ship",
    "deliveryDetail": "Shipped in 15 days after payment",
    "storage": "Please keep in closed containers at ambient Temp and Humidity",
    "extra": null,
    "analysis": [
      { "characteristic": "Total color/Assay, percent by Mass, min.", "requirement": "85.00" }
    ],
    "attributes": [
      { "label": "Color No.", "value": "Yellow 4" },
      { "label": "C.I. No.", "value": "19140" },
      { "label": "E Number", "value": "E102" }
    ]
  }
}
```

| Field | Notes |
|---|---|
| `id` | Slug. Use for URLs and React `key` |
| `productId` | Database id. **Send this as `product_id` when placing an order** (1.6) |
| `groupKey` / `groupName` | Replaces the old `colorFamily` / `category` / `formType` / `variety` / `gradeFamily`. `groupKey` is for filtering, `groupName` is the display label |
| `extra` | Free-form JSON object for anything without its own column (any keys, nested values allowed). `null` when empty. Set from the admin; example: `{"certificates": ["ISO", "FSSAI"], "minOrder": 100}` |
| `analysis` | The product's own specs, otherwise its category's default specs. Always an array |
| `attributes` | Category-specific extras as `{label, value}` pairs (C.I. No., E Number, Packaging, Pharmacopoeia, ...). Different products have different labels, so render them with a generic loop and don't hard-code labels. May be `[]` |

### 1.5 Contact Us form

`POST /contact` (rate limit: 5 requests/minute per IP)

| Field | Required | Rules |
|---|---|---|
| `name` | yes | string, max 255 |
| `email` | yes | valid email |
| `message` | yes | string, max 5000 |
| `phone` | no | string, max 50 |
| `company` | no | string, max 255 |
| `product_interest` | no | string, max 255 (the "Product of Interest" dropdown value) |
| `website` | no | **Honeypot. Render a hidden input and send it empty (or don't send it).** A non-empty value is rejected as spam |

```bash
curl -X POST http://localhost:8000/api/contact \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{
    "name": "Asha Rao",
    "email": "asha@example.com",
    "phone": "+91 98765 43210",
    "company": "Acme Foods",
    "product_interest": "Lake Colours",
    "message": "Please send a quote for 500 kg."
  }'
```

`201 Created`
```json
{ "message": "Thank you! Your message has been received." }
```

`422` (missing fields)
```json
{
  "message": "The name field is required. (and 2 more errors)",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email field is required."],
    "message": ["The message field is required."]
  }
}
```

```js
try {
  const { message } = await api('/contact', { method: 'POST', body: formData })
  setSubmitted(true)
} catch (e) {
  if (e.status === 422) setFieldErrors(e.errors)   // { email: ['...'] }
  else if (e.status === 429) setError('Too many attempts, please try again in a minute.')
}
```

> The current `ContactForm` has no `message` validation. The API requires it, so mark the textarea `required`.

### 1.6 Product order inquiry (the "Order" modal)

`POST /orders` (same rate limit as `/contact`)

| Field | Required | Rules |
|---|---|---|
| `product_id` | yes | the product's `productId` (integer) |
| `name` | yes | string |
| `email` | yes | valid email |
| `quantity` | yes | string, max 100 (e.g. `"250 Kgs"`) |
| `phone` | no | string |
| `address` | no | string, max 2000 |
| `message` | no | string, max 5000 |
| `website` | no | honeypot, leave empty |

```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "name": "Asha Rao",
    "email": "asha@example.com",
    "phone": "+91 98765 43210",
    "quantity": "250 Kgs",
    "address": "Plot 4, MIDC, Pune"
  }'
```

`201 Created`
```json
{ "message": "Thank you! Your order inquiry has been received." }
```

`422` example (bad input)
```json
{
  "message": "The name field is required. (and 2 more errors)",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email field must be a valid email address."],
    "quantity": ["The quantity field is required."]
  }
}
```

### 1.7 Testimonials (homepage "What Our Customers Say")

`GET /testimonials`

Returns active testimonials, ordered.

```bash
curl -H "Accept: application/json" http://localhost:8000/api/testimonials
```

```json
{
  "data": [
    {
      "id": 1,
      "name": "Marcus Vance",
      "role": "Director of Global Procurement",
      "company": "BevTech Innovations Europe (Frankfurt, Germany)",
      "avatar": "http://localhost:8000/storage/testimonials/avatar-1.jpg",
      "quote": "We have sourced synthetic food colours and aluminium lake pigments from Exim India for over six years..."
    }
  ]
}
```

---

## 2. Admin API (for the admin dashboard)

All admin routes are under `/api/admin`. Only `login` is public.

### 2.1 Login / logout / me

`POST /admin/login` (rate limit: 10/minute)

```bash
curl -X POST http://localhost:8000/api/admin/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"admin@eximindiacorporation.com","password":"<ADMIN_PASSWORD from .env>"}'
```

`200`
```json
{
  "token": "1|Zk3...plainTextToken",
  "user": { "id": 1, "name": "Admin", "email": "admin@eximindiacorporation.com" }
}
```

`422` on wrong credentials
```json
{ "message": "Invalid credentials.", "errors": { "email": ["Invalid credentials."] } }
```

Store the token (e.g. in memory or `localStorage`) and send `Authorization: Bearer <token>` on every admin call.

| Method | Route | Response |
|---|---|---|
| `POST` | `/admin/logout` | `204` (token revoked) |
| `GET` | `/admin/me` | `{ "id": 1, "name": "Admin", "email": "..." }` |

### 2.2 Categories

| Method | Route | Notes |
|---|---|---|
| `GET` | `/admin/categories` | All categories (including inactive) with `products_count` |
| `POST` | `/admin/categories` | Create, returns `201` and the category |
| `GET` | `/admin/categories/{slug}` | One category with `analysis_specs` and `sections` |
| `PUT/PATCH` | `/admin/categories/{slug}` | Update. Only send fields you want to change |
| `DELETE` | `/admin/categories/{slug}` | `204` (also deletes its products) |

Body for create/update:

```json
{
  "name": "Disco Dust",
  "slug": "disco-dust",
  "description": "Edible glitter",
  "image": "products/efc/disco.jpg",
  "sort_order": 9,
  "is_active": true,
  "analysis_specs": [
    { "characteristic": "Heavy Metals (as Pb), Max.", "requirement": "10 ppm" }
  ],
  "sections": [
    {
      "key": "guide",
      "title": "Application Guide",
      "items": [
        {
          "title": "Dry Dusting",
          "subtitle": null,
          "color_hex": null,
          "attributes": [{ "label": "Method", "value": "Brush on dry surface" }]
        }
      ]
    }
  ]
}
```

`slug` is required on create and must be unique (letters, numbers, dashes). **`analysis_specs` and `sections` replace all existing rows when sent** (send the full list). Omit them to leave them unchanged; send `[]` to clear.

### 2.3 Products

| Method | Route | Notes |
|---|---|---|
| `GET` | `/admin/products` | Paginated (25/page). Query: `category_id`, `search` (name), `page` |
| `POST` | `/admin/products` | Create, returns `201` |
| `GET` | `/admin/products/{id}` | One product (numeric id) with `category`, `analysis_specs`, `detail_attributes` |
| `PUT/PATCH` | `/admin/products/{id}` | Update |
| `DELETE` | `/admin/products/{id}` | `204` (soft delete) |

List response (trimmed):
```json
{
  "data": [
    {
      "id": 1, "category_id": 1, "slug": "tartrazine", "name": "Tartrazine",
      "group_key": "yellow", "is_active": true,
      "image": "products/synthesis-food-color/high-quality-Tartrazine.jpg",
      "image_url": "http://localhost:8000/storage/products/synthesis-food-color/high-quality-Tartrazine.jpg",
      "category": { "id": 1, "name": "Synthetic food colours", "slug": "synthetic-food-colours" }
    }
  ],
  "current_page": 1, "last_page": 1, "per_page": 25, "total": 4
}
```

Create body (`category_id`, `slug`, `name` required; everything else optional/nullable):
```json
{
  "category_id": 1,
  "slug": "sunset-yellow",
  "name": "Sunset Yellow",
  "group_key": "yellow",
  "group_name": "Yellow",
  "color_hex": "#f59e0b",
  "image": "products/synthesis-food-color/sunset.jpg",
  "description": "Orange-yellow azo dye.",
  "moq": "250 Kgs",
  "supply_ability": "20 Metric Tons / Month",
  "port": "JNPT, (Nhava Sheva) Mumbai",
  "cas_no": "2783-94-0",
  "other_names": null,
  "mf": null,
  "einecs_no": null,
  "fema_no": null,
  "place_of_origin": "Maharashtra, India",
  "types": null,
  "brand_name": "Exim India Corporation",
  "model_number": null,
  "grade": "Food Grade",
  "color_desc": "Orange Powder",
  "application_summary": "Bakery, Beverages",
  "purity": "85% Min",
  "shelf_life": "3 Years",
  "packaging_details": "10/25/50 Kg drums",
  "delivery_detail": "Shipped in 15 days after payment",
  "storage": "Keep in closed containers",
  "extra": { "certificates": ["ISO", "FSSAI"], "notes": { "minOrder": 100 } },
  "sort_order": 16,
  "is_active": true,
  "analysis": [{ "characteristic": "Assay", "requirement": "85% Min" }],
  "attributes": [{ "label": "E Number", "value": "E110" }]
}
```

Notes:
- `slug` must be unique **within its category**.
- Admin bodies use **snake_case** (`supply_ability`, `cas_no`, ...), unlike the public API.
- `extra` is a free-form JSON object (any keys, nested values allowed). Sending it **replaces the whole object**; send `null` or `{}` to clear. It is returned as-is on both the admin and public API.
- `analysis` and `attributes` replace all existing rows when sent (omit to leave unchanged, `[]` to clear). Leave `analysis` empty to fall back to the category's default specs on the public API.
- `image` is the **path** returned by the upload endpoint (2.5), and the admin response also gives a ready-to-use `image_url`.

### 2.4 Contact enquiries

| Method | Route | Notes |
|---|---|---|
| `GET` | `/admin/contacts` | Paginated (25/page), newest first. Query: `type` (`contact` or `order`), `status` (`new`, `read`, `replied`, `archived`), `page` |
| `GET` | `/admin/contacts/{id}` | One enquiry |
| `PATCH` | `/admin/contacts/{id}` | Body `{ "status": "read" }` |
| `DELETE` | `/admin/contacts/{id}` | `204` |

Response shape (Laravel resource with `data`, `links`, `meta`):
```json
{
  "data": [
    {
      "id": 7,
      "type": "order",
      "name": "Asha Rao",
      "email": "asha@example.com",
      "phone": "+91 98765 43210",
      "company": null,
      "product_interest": null,
      "product": { "id": 1, "name": "Tartrazine", "slug": "tartrazine" },
      "quantity": "250 Kgs",
      "address": "Plot 4, MIDC, Pune",
      "message": null,
      "status": "new",
      "ip_address": "203.0.113.5",
      "created_at": "2026-09-24T10:31:05.000000Z"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 25, "total": 1 }
}
```

`product` is `null` for `contact` type enquiries.

### 2.5 Testimonials

Full CRUD, same pattern as categories/products.

| Method | Route | Notes |
|---|---|---|
| `GET` | `/admin/testimonials` | All testimonials (including inactive), ordered |
| `POST` | `/admin/testimonials` | Create, returns `201` |
| `GET` | `/admin/testimonials/{id}` | One testimonial |
| `PUT/PATCH` | `/admin/testimonials/{id}` | Update |
| `DELETE` | `/admin/testimonials/{id}` | `204` |

Body (`name` and `quote` required; everything else optional):
```json
{
  "name": "Jane Doe",
  "role": "QA Lead",
  "company": "Acme Foods",
  "avatar": "testimonials/jane.jpg",
  "quote": "Excellent partner.",
  "sort_order": 6,
  "is_active": true
}
```

`avatar` is the **path** returned by the upload endpoint (2.6) — upload the photo first, then save the returned `path` here.

### 2.6 Image upload

`POST /admin/uploads` as `multipart/form-data`, field `image` (jpg, jpeg, png, webp; max 4 MB).

```bash
curl -X POST http://localhost:8000/api/admin/uploads \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
  -F "image=@./sunset.jpg"
```

`201`
```json
{
  "path": "products/Yk3xQ...jpg",
  "url": "http://localhost:8000/storage/products/Yk3xQ...jpg"
}
```

Save `path` into the product/category `image` field. Use `url` for an instant preview.

```js
const fd = new FormData()
fd.append('image', file)
const res = await fetch(`${API}/admin/uploads`, {
  method: 'POST',
  headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
  body: fd,   // do NOT set Content-Type; the browser adds the boundary
})
```

---

## 3. Migrating the existing frontend

| Frontend today | From the API |
|---|---|
| `import ... from constants/*Data.js` | `api('/categories/{slug}/products')` |
| `NAV_LINKS[].dropdown` (Navbar) | `api('/categories')`, link with `category.path` |
| `PRODUCTS.find(p => p.id === id)` (detail page) | `api('/categories/{slug}/products/{id}')` |
| `product.colorFamily` | `product.groupKey` (label: `groupName`) |
| `product.ciNo`, `eNumber`, `packaging`, ... | `product.attributes.find(a => a.label === 'E Number')?.value`, or render the list generically |
| `product.image` (imported asset) | `product.image` (absolute URL) |
| `*_ANALYSIS_SPECS` / `product.analysis` | `product.analysis` (already falls back to the category's specs) |
| Extra lists (application guides, comparison table, name lists) | `category.sections` from `GET /categories/{slug}` |
| `OrderModal` submit | `POST /orders` with `product_id: product.productId` |
| `TestimonialsSection` hardcoded array | `GET /testimonials` |
| `ContactForm` submit | `POST /contact` |

Notes:
- Fetch lists and details separately: the list endpoint is enough for cards, and the detail endpoint returns the same fields.
- The Disco Dust "variety shades" list is not in the API (it was dropped on purpose). That page still needs its local constant.
- Chemicals (a certificate image) and Botanical Extracts have no products (`productCount: 0`). Handle the empty state.

## 4. Local setup for frontend developers

```bash
cd exim-india-backend
composer install
cp .env.example .env && php artisan key:generate
# set DB_* in .env (MariaDB) and ADMIN_PASSWORD
php artisan migrate --seed
php artisan storage:link
php artisan serve            # http://localhost:8000
```

In the frontend `.env`: `VITE_API_URL=http://localhost:8000/api`
