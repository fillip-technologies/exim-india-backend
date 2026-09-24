# Exim India – Laravel Backend Plan

**Status: implemented** (MariaDB via the `mysql` connection; 15 feature tests pass on in-memory SQLite). Laravel 12 / PHP 8.2 in `exim-india-backend`. The backend serves the React frontend (`exim-india-frontend`) as a JSON API and gives the owner an admin login to manage products and read enquiries.

## 1. Scope

| Area | What it does |
|---|---|
| Admin auth | One admin logs in, manages categories, products and reads contact enquiries |
| Categories | The 20 items in the "Products" navbar dropdown |
| Products | Product detail pages (specs, image, analysis table) |
| Contact | Saves the Contact Us form and the product "Order / Inquiry" modal |

Out of scope for v1: public user registration, cart/payments, tracking widget.

## 2. Tables (8)

### 2.1 `users` (admin login)
Use Laravel's existing `users` table, plus one column.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | string | |
| email | string unique | login |
| password | string | hashed |
| is_admin | boolean default true | lets you add non-admin roles later |
| remember_token, timestamps | | default |

No public registration route. Create the admin with a seeder (`AdminSeeder`), with the email and password read from `.env`.

### 2.2 `categories` (navbar → Products dropdown)

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | string | "Synthetic food colours" |
| slug | string unique | `synthetic-food-colours`, matches the frontend URL `/products/{slug}` |
| description | text nullable | |
| image | string nullable | category card image (used on the Products page) |
| sort_order | unsigned int default 0 | dropdown order |
| is_active | boolean default true | |
| timestamps | | |

Seed with the 20 categories from `src/constants/navigation.js`. Chemicals is a single certificate image, so it has no products.

### 2.3 `products` (product detail page)
Fields come from the objects in `src/constants/*Data.js`.

| Column | Type | Frontend field |
|---|---|---|
| id | bigint PK | |
| category_id | FK → categories, cascadeOnDelete, indexed | |
| slug | string | `id` in the frontend (`egg-yellow`); unique per category |
| name | string | `name` |
| group_key | string nullable | filter chip key (was `colorFamily` / `category` / `formType` / `variety` / `gradeFamily`) |
| group_name | string nullable | readable label for the group |
| description | text nullable | `description` |
| color_hex | string(9) nullable | `colorHex` |
| image | string nullable | `image` |
| moq | string nullable | `moq` |
| supply_ability | string nullable | `supplyAbility` |
| port | string nullable | `port` |
| cas_no | string nullable | `casNo` |
| other_names | text nullable | `otherNames` |
| mf | string nullable | `mf` |
| einecs_no | string nullable | `einecsNo` |
| fema_no | string nullable | `femaNo` |
| place_of_origin | string nullable | `placeOfOrigin` |
| types | string nullable | `types` |
| brand_name | string nullable | `brandName` |
| model_number | string nullable | `modelNumber` |
| grade | string nullable | `grade` |
| color_desc | string nullable | `colorDesc` |
| application_summary | text nullable | `applicationSummary` |
| purity | string nullable | `purity` |
| shelf_life | string nullable | `shelfLife` |
| packaging_details | text nullable | `packagingDetails` |
| delivery_detail | string nullable | `deliveryDetail` |
| storage | text nullable | `storage` |
| extra | json nullable | free-form JSON added by the admin for anything without a dedicated column |
| sort_order | unsigned int default 0 | |
| is_active | boolean default true | |
| timestamps, softDeletes | | |

Indexes: `unique(category_id, slug)`, `index(is_active)`.

Why not one table per category? All ~220 products share the same columns. Category-specific fields (C.I. No., E Number, Packaging, ...) are stored as label/value rows in `detail_attributes`, so every product returns exactly the same keys.

### 2.4 `analysis_specs` (test specification rows)
Separate table for the "Specification & Quality Analysis" rows. One polymorphic table serves both categories (shared default rows) and products (their own rows).

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| specable_type / specable_id | morphs | `App\Models\Category` or `App\Models\Product` |
| characteristic | string | e.g. "pH Value (Direct)" |
| requirement | string | e.g. "2.50 – 4.50" |
| sort_order | unsigned int | |
| timestamps | | |

A product shows its own rows if it has any; otherwise the API falls back to its category's rows.

### 2.5 `detail_attributes` (uniform key/value extras)
Polymorphic `label` / `value` rows (`attributable_type/id`, `sort_order`). Owner is a Product (C.I. No., E Number, Packaging, Pharmacopoeia, ...) or a SectionItem.

### 2.6 `category_sections` and `section_items` (category page extras)
Application guides, shade lists, comparison tables. A section (`key`, `title`) has items (`title`, `subtitle`, `color_hex`); each item's other fields are `detail_attributes` rows. Every section item has the same keys.

### 2.7 `contacts` (Contact Us + product order inquiry)

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| type | enum(`contact`,`order`) default `contact` | Contact Us form vs product "Order" modal |
| name | string | |
| email | string | |
| phone | string nullable | |
| company | string nullable | Contact form only |
| product_interest | string nullable | Contact form dropdown value |
| product_id | FK → products nullOnDelete, nullable | set for `order` type |
| quantity | string nullable | order modal |
| address | text nullable | order modal |
| message | text nullable | |
| status | enum(`new`,`read`,`replied`,`archived`) default `new` | admin workflow |
| ip_address | string(45) nullable | spam tracing |
| timestamps | | |

Indexes: `status`, `created_at`.

### Relationships
- `Category / Product morphMany AnalysisSpec`
- `Product / SectionItem morphMany DetailAttribute`; `Category hasMany CategorySection hasMany SectionItem`
- `Category hasMany Product`, `Product belongsTo Category`
- `Product hasMany Contact`, `Contact belongsTo Product (nullable)`

## 3. API

Public (no auth), prefix `/api`:

| Method | Route | Purpose |
|---|---|---|
| GET | `/categories` | Navbar dropdown and Products page (active only, ordered) |
| GET | `/categories/{slug}` | Category info and its analysis specs |
| GET | `/categories/{slug}/products` | Product list for the category page (supports `?color_family=`) |
| GET | `/categories/{slug}/products/{productSlug}` | Product detail (falls back to the category's analysis specs) |
| POST | `/contact` | Save Contact Us form (`type=contact`) |
| POST | `/orders` | Save the Order modal (`type=order`, requires `product_id`) |

Admin (Sanctum token auth, `is_admin` middleware):

| Method | Route | Purpose |
|---|---|---|
| POST | `/api/admin/login` | Returns a token |
| POST | `/api/admin/logout` | Revokes the token |
| GET | `/api/admin/me` | Current admin |
| apiResource | `/api/admin/categories` | CRUD |
| apiResource | `/api/admin/products` | CRUD (`?category_id=`, `?search=`) |
| POST | `/api/admin/uploads` | Image upload (stores under `storage/app/public/products`) |
| GET | `/api/admin/contacts` | List, filter by `type` and `status`, paginated |
| GET/PATCH/DELETE | `/api/admin/contacts/{id}` | View, change status, delete |

Responses use Eloquent API Resources (`CategoryResource`, `ProductResource`, `ContactResource`). The resources emit camelCase keys (`colorHex`, `moq`, …) so the frontend components work with minimal change.

## 4. Implementation steps

1. **Setup**: `.env` (DB, `APP_URL`, `FRONTEND_URL`), `php artisan install:api` (Sanctum), `php artisan storage:link`, CORS allowing the frontend origin.
2. **Migrations**: add `is_admin` to users; create `categories`, `products`, `contacts`.
3. **Models**: `Category`, `Product`, `Contact` with fillable, casts (json, boolean) and relationships. Use route-model binding on `slug`.
4. **Seeders**
   - `AdminSeeder` for the admin user.
   - `CategorySeeder` for the 20 nav items.
   - `ProductSeeder`, fed from a JSON export of the frontend `constants/*Data.js` files. Write a one-off Node script that converts them to `database/seeders/data/products.json`; the image imports become file paths.
5. **Public API**: controllers, Resources, `Route::apiResource` read-only routes.
6. **Contact/Order**: `StoreContactRequest` and `StoreOrderRequest` (FormRequest validation), a `throttle:5,1` rate limit, and a honeypot field for spam. Optionally send a notification email to `info@eximindiacorporation.com` (queued Mailable).
7. **Admin auth and CRUD**: Sanctum login, `EnsureAdmin` middleware, admin controllers with Form Requests, and image upload handling.
8. **Frontend integration** (separate PR in the frontend repo)
   - Replace the `constants/*Data.js` imports with API calls (a small `api.js` client plus loading/empty states).
   - Navbar dropdown loads from `/api/categories`.
   - `ContactForm` and `OrderModal` POST to the API instead of `setSubmitted(true)`.
   - Move product images from `src/assets` to the backend storage. Until then, `image` can hold a frontend asset path.
9. **Tests** (PHPUnit / Pest feature tests): public listing endpoints, contact/order validation and throttle, admin auth guard, product CRUD.
10. **Deploy**: production `.env`, `php artisan migrate --force`, `config:cache`, `route:cache`; a queue worker if email notifications are enabled.

## 5. Decisions to confirm

- **Admin panel UI** (built as JSON API; Filament still optional): JSON API only (build an admin React page later), or use Filament for a ready-made admin in about an hour? Filament is faster and would replace the `/api/admin/*` CRUD routes. It's optional; the schema above works either way.
- **Images**: store on the Laravel server (`storage/public`) or a CDN/S3?
- **Email**: should each enquiry also email `info@eximindiacorporation.com`?
- **Chemicals** and **Botanical Extracts** categories currently have no product data in the frontend (Chemicals is a certificate image). Confirm they stay as category-only pages.
