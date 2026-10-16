# Exim India – Fully Dynamic Product Database Plan

Goal: one schema where **every product can have its own colours, sizes, shapes, grades, packs and blends**, any combination of them can be sold ("mix & match"), and the admin adds all of it from the dashboard with **no code change and no new table**.

This extends the existing backend (`plan.md`, Laravel 12, MariaDB). Nothing in `plan.md` is thrown away except the single `color_hex` on `products` and the free-form `extra` JSON being used for pack sizes.

---

## 1. What the frontend data looks like today (audit)

### 1.1 Navbar = the source of truth for categories

`src/constants/navigation.js` lists **19** categories (the existing `plan.md` says 20):

Bakery Colors · Synthetic food colours · Lake colours · Blended Colours · Cosmetic Colours · Pharmaceutical Colours · Liquid Flavours · Emulsion Flavours · Powder Flavours · Disco Dust Powder · Food Additives · Chemicals · Botanical Extracts · Titanium Dioxide · Natural Food Colors · Natural Fruit Powder (Spray Dried) · Edible Lustre · Fluorescent Colours · Pearl Pigment Powder

These slugs are correct and become the `categories` table as-is. The two other places that list categories are **wrong / out of sync** and must stop being hard-coded:

| Place | Problem |
|---|---|
| `dashboard/products/productStructure.js` → `PRODUCT_CATEGORIES` | Only 11 entries, different labels, merges "Chemicals & Titanium Dioxide" into one. Must load from `GET /api/categories`. |
| `pages/product/Products.jsx` (`/products` route is commented out in `App.jsx`) | Not driven by the navbar list. Must render from `GET /api/categories`. |
| Routes `mango-pulp`, `fruit-fragrance` exist in `App.jsx` but are **not in the navbar**; `botanical-extracts` is in the navbar but has **no route and no data** | Handled by `show_in_nav` + `is_active` flags (see 2.1). |

### 1.2 Product shape (Bakery Colors – the richest example)

`constants/bakeryColorsData.js` → `BAKERY_PRODUCTS` has 13 products. Each one has:

| Frontend field | Nature | Example |
|---|---|---|
| `id`, `name`, `image`, `tagline`, `description` | product content | `luster-dust` |
| `grade, purity, shelfLife, placeOfOrigin, brandName, modelNumber, types, colorDesc, applicationSummary, packagingDetails, deliveryDetail, moq, storage` | uniform spec fields | |
| `applications[]` | list of strings | "Chocolates & Bonbons" |
| `applicationTechniques[]` | `{title, desc, icon}` | "Wet Painting" |
| `usageGuide` | text | |
| `analysis[]` | `{characteristic, requirement}` | "Lead (Pb)" / "< 02.00 ppm Max" |
| **`packSizes[]`** | `{size, container, target}` | `25gm / Bottle / Artisan Patisseries` |
| **`colors[]`** | `{id, name, hex, tone}` | `Intense Gold #d4af37` |
| **blend colours** | colour with `bgGradient` (a mix of several colours) | `DB01` rainbow, `DH03`, `DS07`… |

Observations that drive the design:

1. **Colours are per product, not global** – Luster Dust has 18, Gel Colours 28, Hearts 18, Stars 19. The same name ("Gold", "Red", "Pink") repeats with a *different hex* in different products (`Gold #d4af37` in Metallic Powder Glow vs `Intense Gold #d4af37` / `Brilliant Gold #ffd700` in Luster Dust; `Red #c0392b` vs `Red #d63031` in Gel Colours). So colour *names* must be reusable but a product may override the swatch.
2. **Pack sizes are per product and carry extra info** (container, target buyer). The same size can have a different container (`500gm Pouch` vs `500gm Jar`). So a pack is not just a number.
3. **Blends exist already** (`DB01…DB07`, `DH01…`, `DS01…`, `SV01…`) with a gradient built by hand. These should be *mixes of other colours*, computed, not typed.
4. **Six sprinkle products are really one product × (shape/size) × colour.** `Balls 1.5mm / 4mm / 6mm / Hearts / Stars / Vermicelli` repeat the same colour palette with small differences (Hearts has no Black; blend codes are `DB`, `DH`, `DS`, `SV`).
5. Colour and size are also hidden in other categories: flavours (`colorFamily`, MOQ in Kgs/ml), Titanium Dioxide (`gradeFamily`: rutile/anatase), Mango Pulp (`variety`), Disco Dust (`DISCO_DUST_VARIETY_SHADES` with `code`, `group`, `colorHex`), Pearl Pigment (`PEARL_EFFECTS_GUIDE`), Lake colours (`eNumber`, `ciNo`).

### 1.3 Chemicals (`chemicalsData.js`)

3 products, one flat record each: `id, name, shortName, casNo, formula, grade, category, image, description`.

- `formula` → `products.mf` (already exists).
- `shortName` → new column `short_name`.
- `category` ("Chemicals & Intermediates", "Inorganic Pigments & Chemicals") is a **sub-group inside the category**, not a category → `group_name` / `group_key`.
- Chemicals have **no colour axis**, only optional pack/grade. The dynamic model must allow a product with **zero** axes.

### 1.4 What is wrong with the current `plan.md` schema for this goal

| Limitation | Why it blocks "dynamic" |
|---|---|
| `products.color_hex` = one colour per product | A product cannot have 18 colours. Colours today are stored as a row per colour in `section_items`, or squashed into `extra` JSON. |
| `packSizes` has no table | Lost in `extra`. Cannot filter or order by size. |
| No concept of a purchasable combination | An order can only point to a product, not "Luster Dust – Rose Gold – 25gm". |
| No blends / kits | Gradient strings are copy-pasted. |

---

## 2. The model: Product → Axes (options) → Variants

Same idea as Shopify / WooCommerce, adapted to this catalogue:

```
Category
  └── Product                      "Luster Dust"           (shared content, specs)
        ├── Axes (options)         Colour, Pack Size       (which dimensions this product has)
        │     └── Axis values      Rose Gold, 25gm Bottle  (allowed values per axis)
        └── Variants (SKUs)        Rose Gold + 25gm        (one row per sellable combination)
              └── variant_options  → links variant to one value per axis
```

- **Axis** (a.k.a. attribute): `colour`, `pack_size`, `shape`, `size`, `grade`, `flavour`, `finish`… The admin can create new axes any time.
- **Axis value**: `Rose Gold #b76e79`, `4mm`, `Heart`, `Rutile`, `25gm`.
- **Variant**: a concrete combination. Each variant has its own SKU, image, MOQ, price, stock, active flag.
- Any product can use **any** set of axes (0, 1, 2, 3…). Chemicals use none → one default variant (or none). Luster Dust uses 2. Sprinkles-as-one-product uses 3.

### 2.1 Tables

#### `categories` (extend existing)
Keep existing columns; add:

| Column | Type | Notes |
|---|---|---|
| show_in_nav | boolean default true | navbar dropdown filter; `mango-pulp`, `fruit-fragrance` seed as `false` until the owner decides |
| nav_label | string nullable | label in navbar if different from `name` (e.g. "Natural Fruit Powder (Spray Dried)") |
| page_template | string default `standard` | optional hint for the listing page layout |

Seed the 19 navbar rows (+ the 2 hidden ones). `botanical-extracts` is seeded `is_active = true` with 0 products (empty-state page) – confirm below.

#### `products` (extend existing)
Keep all columns from `plan.md` **except**:

| Change | Reason |
|---|---|
| **remove** `color_hex` | colour lives on variants/values |
| **remove** `moq` as the only MOQ → keep as the *default* MOQ; variants may override | |
| **add** `short_name` string nullable | Chemicals `shortName` |
| **add** `tagline` string nullable | bakery `tagline` |
| **add** `usage_guide` text nullable | bakery `usageGuide` |
| **add** `variant_mode` enum(`none`,`single_axis`,`multi_axis`) default `none` | quick UI switch, derived from axes |
| **add** `default_variant_id` FK nullable | what the card/detail page shows first |
| **add** `is_featured` boolean default false | |

`group_key` / `group_name` stay: they are the filter chips on listing pages (`colorFamily`, `fruitFamily`, `gradeFamily`, Chemicals' sub-category).

#### `attributes` (the axes – global registry)

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| code | string unique | `colour`, `pack_size`, `shape`, `size`, `grade`, `flavour`, `finish` |
| name | string | label shown to users: "Colour", "Pack Size" |
| input_type | enum(`swatch`,`text`,`image`,`pack`) | how the frontend renders chips: swatch circle / plain chip / image tile / pack card (size+container+target) |
| unit | string nullable | `gm`, `kg`, `ml`, `mm` for numeric axes |
| sort_order, is_active, timestamps | | |

Seeded: `colour (swatch)`, `pack_size (pack)`, `shape (text)`, `size (text)`, `grade (text)`, `variety (text)`.

#### `attribute_values` (reusable values, with a swatch)

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| attribute_id | FK → attributes, cascade | |
| slug | string | `rose-gold`; `unique(attribute_id, slug)` |
| label | string | "Rose Gold" |
| code | string nullable | blend/shade code `DB01`, `5M`, `E102` |
| hex | string(9) nullable | main swatch colour |
| gradient | string nullable | CSS gradient; **auto-built for blends** (see `attribute_value_components`), or hand-set |
| image | string nullable | swatch/tile image |
| tone | string nullable | the existing `tone` text ("Mirror chrome silver") |
| numeric_value | decimal(12,3) nullable | for sizes (25) so they sort/filter correctly |
| unit | string nullable | overrides the attribute unit |
| group_name | string nullable | shade grouping (`Copper & Bronze`) from `DISCO_DUST_VARIETY_SHADES.group` |
| is_blend | boolean default false | true for DB01 etc. |
| sort_order, is_active, timestamps | | |

Values are **global and reusable** (one "Rose Gold" row shared by Luster Dust, Metallic Powder Glow and Gel Colours). A product that needs a different hex or tone for the same name uses an override (next table).

#### `product_attributes` (which axes a product uses, in what order)

| Column | Type | Notes |
|---|---|---|
| product_id | FK, cascade | |
| attribute_id | FK | |
| sort_order | unsigned int | axis order on the detail page |
| is_required | boolean default true | must the buyer pick this axis? |
| is_variant_axis | boolean default true | `false` = informational only (e.g. grade shown as a fact, not a choice) |

PK `(product_id, attribute_id)`.

#### `product_attribute_values` (allowed values per product + per-product overrides)

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| product_id | FK, cascade | |
| attribute_value_id | FK → attribute_values | |
| label_override | string nullable | |
| hex_override | string(9) nullable | same colour name, different hex in this product |
| tone_override | string nullable | product-specific `tone` text |
| image | string nullable | product-specific swatch photo |
| extra | json nullable | **pack data**: `{ "container": "Bottle", "target": "Artisan Patisseries" }` for pack axis values |
| sort_order | unsigned int | |

`unique(product_id, attribute_value_id)`. This is where today's `colors[]` and `packSizes[]` end up – one row per entry. The "pack_size" axis stores `container` and `target` here because they are product-specific; if you prefer typed columns add `container` and `target` as nullable strings (recommended – they are used by the UI and are not rare).

#### `product_variants` (the sellable combinations)

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| product_id | FK, cascade, indexed | |
| sku | string unique | auto: `LUSTER-ROSEGOLD-25GM` |
| signature | string | sorted `attribute_id:value_id` pairs, e.g. `1:14\|2:31`; `unique(product_id, signature)` prevents duplicate combinations |
| image | string nullable | variant-specific photo; falls back to product image |
| moq | string nullable | falls back to product MOQ |
| supply_ability | string nullable | |
| price | decimal(12,2) nullable | optional (dashboard `productStructure.js` already has price/currency/unit) |
| currency | char(3) nullable | `USD`/`INR`/`EUR` |
| price_unit | string nullable | `Kg`, `25 Kg Drum`, `Liter` |
| stock_status | enum(`in_stock`,`low_stock`,`out_of_stock`,`on_request`) default `on_request` | |
| is_default | boolean default false | |
| is_active | boolean default true | |
| sort_order, timestamps, softDeletes | | |

#### `variant_options` (what each variant is made of)

| Column | Type | Notes |
|---|---|---|
| variant_id | FK, cascade | |
| attribute_id | FK | |
| attribute_value_id | FK | |

PK `(variant_id, attribute_id)` → a variant has exactly one value per axis. Index `(attribute_value_id)` for "all products in Rose Gold" filters.

#### `attribute_value_components` (mixes / blends)

A blend colour is a value made out of other values.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| blend_value_id | FK → attribute_values (where `is_blend = true`) | `DB01` |
| component_value_id | FK → attribute_values | `Blue`, `Pink`, `Yellow`, `Green`, `Pearl White` |
| ratio | decimal(5,2) default 1 | relative share (for the gradient stops and for the formula) |
| sort_order | unsigned int | |

The API builds `gradient` automatically: `radial-gradient(circle at 35% 35%, <c1> 0%, <c2> 25%, …)` from the components' hex, so the hand-typed `bgGradient` strings disappear. If a blend has no components, the stored `gradient` is used (so legacy data still renders).

#### `bundles` + `bundle_items` (mix & match boxes / assortments)

For "pick any 6 colours", "festive kit", "starter set":

| `bundles` | Type | Notes |
|---|---|---|
| id, slug, name, description, image | | |
| product_id | FK nullable | set when the bundle belongs to one product ("Any 6 Luster Dust colours") |
| kind | enum(`fixed`,`pick_n`) | `fixed` = predefined contents, `pick_n` = buyer picks |
| pick_count | unsigned int nullable | N for `pick_n` |
| price / currency | nullable | |
| is_active, sort_order, timestamps | | |

| `bundle_items` | Type | Notes |
|---|---|---|
| bundle_id | FK, cascade | |
| variant_id | FK | the SKU included (`fixed`) or allowed in the pick list (`pick_n`) |
| quantity | unsigned int default 1 | |

#### Unchanged from `plan.md` (reused as-is)

- `analysis_specs` (polymorphic: category fallback → product → optionally **variant**; the same table, add `Variant` as another `specable_type`, useful when e.g. Rutile vs Anatase pack specs differ).
- `detail_attributes` (label/value extras: `C.I. No.`, `E Number`, `CAS`, `FEMA`, `Pharmacopoeia`) for Product, SectionItem and now Variant.
- `category_sections` / `section_items` (shade guides, comparison matrices such as `TIO2_COMPARISON_MATRIX`, `PEARL_EFFECTS_GUIDE`, `FRUIT_POWDER_APPLICATIONS`).
- `contacts`, `testimonials`, `users`.

#### New: `product_points` (the lists that were arrays of strings/objects)

Replaces `applications[]` and `applicationTechniques[]`.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| product_id | FK, cascade | |
| kind | enum(`application`,`technique`,`feature`) | |
| title | string | "Wet Painting (Evapurex Mix)" or "Chocolates & Bonbons" |
| body | text nullable | the `desc` |
| icon | string nullable | the emoji / icon key |
| sort_order | unsigned int | |

#### New: `contact_items` (order / enquiry lines = mix & match order)

Today a contact of type `order` has one `product_id`. A buyer who wants *3 colours × 2 pack sizes* needs several lines.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| contact_id | FK → contacts, cascade | |
| product_id | FK nullable → products | |
| variant_id | FK nullable → product_variants | |
| bundle_id | FK nullable → bundles | |
| label_snapshot | string | "Luster Dust – Rose Gold – 25gm" (kept even if the variant is later deleted) |
| quantity | string/decimal | |
| note | string nullable | custom shade request ("customised in any colour of your choice") |

`contacts.product_id` stays for backward compatibility with the current modal; the new OrderModal writes lines to `contact_items`.

#### Extra fields per product (custom fields)

Some products carry fields others don't: Lake colours have `eNumber` / `ciNo` / `fdcName`, Chemicals have `casNo` / `formula`, Titanium Dioxide has `ciName`, Flavours have `femaNo`, Mango Pulp has `variety` and brix, Pearl Pigment has a coating/substrate. The uniform columns on `products` stay for the fields almost every product has; **everything else is an admin-defined extra field**, with no migration.

Two tables replace the loose `detail_attributes` rows and the `extra` JSON for this purpose:

**`field_definitions`** (the registry – what extra fields exist)

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| code | string unique | `e_number`, `ci_no`, `fda_name`, `cas_no`, `brix`, `shelf_temp` |
| label | string | "E Number", "C.I. No." |
| type | enum(`text`,`textarea`,`number`,`select`,`multiselect`,`boolean`,`url`,`file`,`color`) | drives the admin input and how the page renders it |
| options | json nullable | choices for `select` / `multiselect`, e.g. `["Rutile","Anatase"]` |
| unit | string nullable | `%`, `ppm`, `°Brix`, `°C` |
| group_name | string nullable | block on the detail page: "Identification", "Regulatory", "Technical" |
| applies_to | enum(`product`,`variant`) default `product` | a field can be per product or per SKU (e.g. a batch-specific purity) |
| is_filterable | boolean default false | usable as a listing filter |
| is_public | boolean default true | `false` = admin-only (internal notes) |
| is_searchable | boolean default false | included in site search |
| sort_order, timestamps | | |

**`field_assignments`** (which fields a category or product offers)

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| field_definition_id | FK, cascade | |
| category_id | FK nullable, cascade | field is offered for every product in the category (Lake colours → E Number, C.I. No.) |
| product_id | FK nullable, cascade | field offered for one product only |
| is_required | boolean default false | |
| default_value | text nullable | |
| sort_order | unsigned int | |

Exactly one of `category_id` / `product_id` is set. The product edit form shows its category's fields plus its own, so the owner never sees fields that don't apply.

**`field_values`** (the stored value, typed)

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| field_definition_id | FK, cascade | |
| owner_type / owner_id | morphs (`Product` or `ProductVariant`) | |
| value_text | text nullable | text, textarea, select, url, color |
| value_number | decimal(18,4) nullable | number (range filters and sorting work) |
| value_boolean | boolean nullable | |
| value_json | json nullable | multiselect, file lists |
| timestamps | | |

`unique(field_definition_id, owner_type, owner_id)`; index `(field_definition_id, value_number)` and `(field_definition_id, value_text(100))` for filters.

How it fits the rest:
- `detail_attributes` stays only for *ad-hoc one-off* label/value rows typed on the spot (and for `section_items`). Anything reused across products should be a `field_definition`.
- `products.extra` JSON is kept only as an import escape hatch; seeding moves its content into `field_values`.
- Fields flagged `applies_to = variant` appear on the variant table row, so one colour or pack can carry its own value.
- Custom fields are returned grouped: `"fields": [{ "group": "Identification", "items": [{ "code": "e_number", "label": "E Number", "type": "text", "value": "E102" }] }]`; `number` values are returned with their `unit`.

Examples:

| Category / product | Extra fields assigned |
|---|---|
| Lake colours | E Number, C.I. No., FD&C Name, Dye content %, Aluminium substrate |
| Synthetic food colours | Colour Index No., E Number, INS No., FDA Name |
| Chemicals | CAS No., Molecular Formula, Purity %, Hazard class, UN No. |
| Titanium Dioxide | C.I. Name, TiO₂ content %, Oil absorption, Surface treatment |
| Mango Pulp | Variety, Brix, Acidity, Can size |
| Liquid / powder flavours | FEMA No., Dosage %, Solubility |
| One product only (e.g. a single Luster Dust) | Mica source, Particle size (µm) – assigned via `product_id` |

### 2.2 Relationship summary

```
Category 1─* Product
Product  *─* Attribute            via product_attributes
Attribute 1─* AttributeValue
Product  *─* AttributeValue       via product_attribute_values (overrides, pack data)
Product  1─* Variant
Variant  *─* AttributeValue       via variant_options (one value per attribute)
AttributeValue(blend) *─* AttributeValue(component) via attribute_value_components
Product  1─* ProductPoint
Product / Variant / Category 1─* AnalysisSpec (morph)
Product / Variant / SectionItem 1─* DetailAttribute (morph)
Bundle   *─* Variant              via bundle_items
Contact  1─* ContactItem ─> Product / Variant / Bundle
FieldDefinition 1─* FieldAssignment ─> Category or Product
FieldDefinition 1─* FieldValue ─> Product or Variant (morph)
```

---

## 3. "Different colours, different sizes, and mix & match" – how each need is met

| Need | How it works |
|---|---|
| Some products have extra fields others don't | admin defines a `field_definition` and assigns it to a category or a single product; value stored in `field_values` |
| Product A has 18 colours, product B has 28 | each product only has the values it enables in `product_attribute_values` |
| Same colour name, different hex per product | global value + `hex_override` in `product_attribute_values` |
| Product has colours **and** sizes | two axes → variants = combinations. Admin clicks "Generate variants" to create the grid, then switches individual cells off (`is_active = false`) |
| Not every combination exists (e.g. Hearts has no Black) | just don't create that variant (or deactivate it); frontend greys out impossible chips |
| Different sizes per colour | variants carry their own MOQ/price/image; a colour can have only some packs |
| Product with only one axis (flavours: 1 kg / 5 kg) | one axis, variants = values |
| Product with none (Chemicals) | no axes, 0 or 1 default variant |
| New dimension later (e.g. "Finish", "Texture") | add a row to `attributes` – no migration |
| Blended / mixed colour | `is_blend = true` + `attribute_value_components` (ratios) → gradient computed |
| Mix & match purchasing | (a) `contact_items`: buyer adds many variants to one enquiry; (b) `bundles` for fixed kits and "pick N" boxes |
| Custom shade on request | `contact_items.note` + a `custom` flag on the product (`extra.custom_shades = true`) |

### 3.1 Sprinkles – decision

Today: 6 separate products (Balls 1.5/4/6mm, Hearts, Stars, Vermicelli), each with its own page, image and colours.

- **Option A – keep 6 products** (recommended for launch). Keeps current URLs (`/products/bakery-colors/dazzling-sprinkles-hearts`), images and SEO unchanged. Each has the `colour` axis. Easy migration: one-to-one.
- **Option B – 1 product "Dazzling Sprinkles"** with axes `shape/size` × `colour` × `pack_size`. One page, a shape switcher, fewer duplicated records. Existing URLs become redirects. Use this later if the owner wants a single sprinkles page.

The schema supports both; moving from A to B is a data change only.

---

## 4. Worked examples

### 4.1 Luster Dust (2 axes: colour × pack size)

```
product: luster-dust  (category: bakery-colors)
axes   : colour (swatch), pack_size (pack)
values : colour → Intense Gold #d4af37, Brilliant Gold, Moon Gold, Silver, … (18)
         pack_size → 4.25gm Bottle, 25gm Bottle, 500gm Foil Pouch, 25kg HDPE Drum
variants (18 × 4 = 72 SKUs, generated):
  LUSTER-INTENSE-GOLD-4.25GM   {colour: Intense Gold, pack: 4.25gm}
  LUSTER-INTENSE-GOLD-25GM     …
```

### 4.2 Dazzling Sprinkles Balls 1.5mm (colour incl. blends)

```
values : Black, Blue … Yellow (11 plain) + DB01 … DB07 (blend)
DB01   : is_blend = true, components = Blue, Pink, Yellow, Green, Pearl White (ratio 1 each)
         → gradient generated: radial-gradient(circle at 35% 35%, …)
```

### 4.3 Titanium Dioxide (grade axis, no colour)

```
products: rutile-grade, anatase-grade   (keep as two products, they have different CAS/specs)
axes    : pack_size  (25kg bag, 1 MT jumbo)
group_key: rutile / anatase  (listing filter chips)
```

### 4.4 Chemicals

```
product: diethanolamine-dea   short_name DEA, cas_no 111-42-2, mf C4H11NO2
group_name: "Chemicals & Intermediates"   (was `category`)
axes: none  →  variant_mode = none
```

### 4.5 API shape for a product (what React receives)

```json
{
  "id": 3,
  "slug": "luster-dust",
  "name": "Luster Dust",
  "category": { "slug": "bakery-colors", "name": "Bakery Colors" },
  "image": "/storage/products/luster-dust.png",
  "tagline": "18 Spectacular Metallic Colours • Food Grade Magic",
  "description": "...",
  "specs": { "grade": "...", "shelfLife": "24 Months", "moq": "1 Box / Custom Batch" },
  "applications": ["Chocolates & Bonbons"],
  "techniques": [{ "title": "Wet Painting", "desc": "...", "icon": "🖌️" }],
  "analysis": [{ "characteristic": "Lead (Pb)", "requirement": "< 02.00 ppm Max" }],
  "options": [
    { "code": "colour", "name": "Colour", "type": "swatch", "values": [
        { "id": 14, "slug": "rose-gold", "label": "Rose Gold", "hex": "#b76e79", "tone": "Metallic blush pink", "gradient": null }
    ]},
    { "code": "pack_size", "name": "Pack Size", "type": "pack", "values": [
        { "id": 31, "label": "25gm", "container": "Bottle", "target": "Artisan Patisseries & Bakeries" }
    ]}
  ],
  "variants": [
    { "id": 501, "sku": "LUSTER-ROSE-GOLD-25GM", "options": { "colour": 14, "pack_size": 31 },
      "image": null, "moq": "1 Box", "stockStatus": "on_request", "active": true }
  ],
  "defaultVariantId": 501
}
```

Keys stay camelCase like the existing Resources, so the current components (`ProductDetailHeader`, `ProductDetailTabs`, `BakeryProductCard`, `OrderModal`) need only small edits (`colors` → `options[colour].values`, `packSizes` → `options[pack_size].values`).

Listing endpoint returns the lightweight card (`name, image, slug, group, variantCount, colourSwatches[first 6]`), not all 72 variants.

---

## 5. Admin dashboard (what the owner can do)

Product edit page gets these blocks (replacing `productStructure.js` LocalStorage):

1. **Basics** – category dropdown (from API), name, slug, tagline, description, image.
2. **Specs** – the uniform fields (grade, purity, shelf life, origin, MOQ…).
2b. **Extra fields** – the fields assigned to this category/product (inputs picked by `type`), plus "Add field to this product" (pick an existing definition or create one: label, type, unit, group, filterable, public) and free one-off label/value rows (`detail_attributes`).
3. **Options (axes)** – "Add option": pick Colour / Pack Size / Shape… or create a new one.
4. **Values per option** – colour picker + name + tone for colours; size + container + target for packs; "Mark as blend → pick component colours + ratios".
5. **Variants** – "Generate all combinations", then a table (SKU, image, MOQ, price, stock, active). Bulk toggle by colour or by size.
6. **Applications / techniques** – repeatable rows (`product_points`).
7. **Analysis table** – repeatable rows (`analysis_specs`), "copy from category defaults".
8. **Bundles** – create a kit or a "pick N" box from this product's variants.

Global screens: **Attributes** (axes + shared values/blends library), **Extra Fields** (definitions and which categories use them) and **Categories** (ordering, `show_in_nav`).

Admin API additions:

| Method | Route | Purpose |
|---|---|---|
| apiResource | `/api/admin/attributes` | axes |
| apiResource | `/api/admin/attributes/{id}/values` | values + blend components |
| PUT | `/api/admin/products/{id}/options` | set axes and enabled values (with overrides) |
| POST | `/api/admin/products/{id}/variants/generate` | build the combination grid (skips existing `signature`s) |
| apiResource | `/api/admin/products/{id}/variants` | edit / disable single SKUs |
| apiResource | `/api/admin/bundles` | kits |
| apiResource | `/api/admin/fields` | extra field definitions |
| PUT | `/api/admin/fields/{id}/assignments` | assign to categories / products |
| PUT | `/api/admin/products/{id}/fields` | save a product's extra field values |

Public additions: `GET /api/categories/{slug}/products/{productSlug}` now returns `options` + `variants` (section 4.5); `GET /api/products?colour=rose-gold&pack_size=25gm` filters through `variant_options`; `POST /api/orders` accepts `items: [{variantId|bundleId, quantity, note}]`.

---

## 6. Migration order (new files after the existing ones)

1. `add_nav_fields_to_categories` (show_in_nav, nav_label, page_template)
2. `alter_products_for_variants` (add short_name, tagline, usage_guide, variant_mode, default_variant_id, is_featured; drop color_hex)
3. `create_attributes_table`
4. `create_attribute_values_table`
5. `create_attribute_value_components_table`
6. `create_product_attributes_table`
7. `create_product_attribute_values_table`
8. `create_product_variants_table`
9. `create_variant_options_table`
10. `create_product_points_table`
11. `create_bundles_and_bundle_items_tables`
12. `create_contact_items_table`
13. `create_field_definitions_table`
14. `create_field_assignments_table`
15. `create_field_values_table`

Indexes: `product_variants(product_id, is_active)`, `product_variants unique(product_id, signature)`, `variant_options(attribute_value_id)`, `attribute_values unique(attribute_id, slug)`, `product_attribute_values unique(product_id, attribute_value_id)`.

Model rules: `ProductVariant::signature` is built from sorted option pairs in a model observer; the `generate` action uses the cartesian product of the product's enabled values per axis.

---

## 7. Seeding from the frontend files

Write one Node script (`scripts/export-catalogue.mjs`) that imports every `src/constants/*Data.js` and emits `database/seeders/data/catalogue.json`; then a `CatalogueSeeder` loads it.

| Frontend source | Goes to |
|---|---|
| `NAV_LINKS[Products].dropdown` | `categories` (19 rows, `sort_order` = index) |
| Each `*_PRODUCTS` / `*_COLORS` entry | `products` |
| `BAKERY_PRODUCTS[].colors[]` | `attribute_values` (colour; reuse by name+hex) + `product_attribute_values` |
| `colors[]` with `bgGradient` | `is_blend = true`; components parsed from the `tone` text where possible, else keep `gradient` as-is |
| `BAKERY_PRODUCTS[].packSizes[]` | `attribute_values` (pack_size) + `product_attribute_values` (container, target) |
| `applications[]`, `applicationTechniques[]` | `product_points` |
| `analysis[]` / `*_ANALYSIS_SPECS` | `analysis_specs` (product rows for bakery; category rows for the shared lists) |
| `DISCO_DUST_VARIETY_SHADES` (`code`, `group`, `colorHex`) | `attribute_values` (colour with `code`, `group_name`, `hex`) |
| Category-specific keys (`eNumber`, `ciNo`, `fdcName`, `casNo`, `ciName`, `femaNo`, `einecsNo`, `variety` …) | one `field_definitions` row per key (label, type, group), assigned to its category; values → `field_values` |
| `colorFamily / fruitFamily / gradeFamily / variety / category` | `products.group_key` / `group_name` |
| `colorHex` on non-bakery products (e.g. Tartrazine `#facc15`) | one colour value on a single-colour product, shown as the card swatch (a product with `colour` axis of 1 value) |
| `PEARL_EFFECTS_GUIDE`, `TIO2_COMPARISON_MATRIX`, `FRUIT_POWDER_APPLICATIONS`, `SPECIALTY_LIQUID_ENHANCERS` | `category_sections` / `section_items` (as in `plan.md`) |
| `chemicalsData.js` | 3 products, `short_name`, `cas_no`, `mf` (from `formula`), `group_name` (from `category`) |
| image imports | copied to `storage/app/public/products/...`; DB stores the path |

Variants after seeding: for products with colours but no pack sizes, one variant per colour; for bakery products, colours × packs generated with all combinations active.

---

## 8. Frontend changes needed (after the API exists)

1. `Navbar` – load `GET /api/categories?nav=1` (fallback to `NAV_LINKS` while loading).
2. `Products.jsx` and the dashboard category dropdown – use the same endpoint.
3. Replace each `constants/*Data.js` import by a hook (`useCategoryProducts(slug)`, `useProduct(slug, id)`).
4. Detail page: render `options` generically – a `<OptionPicker>` that switches on `type` (`swatch`, `pack`, `text`) instead of one hard-coded colour section per category; keep the selected variant in state and show its image/MOQ.
5. `OrderModal` – cart-style list of selected variants (mix & match) posting `items[]`.
6. Dashboard product add/edit – replace `productStructure.js` LocalStorage with the API and add the Options/Variants blocks of section 5.

---

## 9. Phases

| Phase | Deliverable |
|---|---|
| 1 | Migrations 1–9, models, observers (signature, default variant), seed 19 categories |
| 2 | Export script + `CatalogueSeeder`; verify counts match the frontend (e.g. Luster Dust 18 colours × 4 packs = 72 variants) |
| 3 | Public API with `options`/`variants`, filters, feature tests |
| 4 | Admin API: attributes, options, variant generator, bundles |
| 5 | Frontend: navbar/Products from API, generic `OptionPicker`, detail pages per category |
| 3b | Extra-field tables, models and API output (`fields` grouped by `group_name`) |
| 6 | Dashboard product editor, multi-line enquiries (`contact_items`) |

---

## 10. Decisions to confirm

1. **Sprinkles**: keep 6 products (A) or merge to one (B)?
2. **Hidden categories**: `mango-pulp` and `fruit-fragrance` have pages but are not in the navbar – add them to the navbar, or keep them hidden?
3. **Botanical Extracts**: in the navbar but no page or data – keep as an empty "coming soon" category?
4. **Prices**: public price display, or "request quote" only (then keep `price` admin-only / unused)?
5. **Blends**: are the blend components in `tone` text accurate enough to auto-derive (e.g. DB01 = Blue, Pink, Yellow, Green, Pearl), or should the owner enter ratios by hand?
6. **Extra fields**: should some be filterable on listing pages (e.g. E Number, Brix, Purity), and should any stay admin-only?
7. **Custom shades**: should "custom colour on request" be a selectable option on the order form?
