#!/usr/bin/env python3
"""
Merge 6 separate sprinkle products into 1 unified product
with shape × colour × pack_size variant axes.
"""
import json, copy

with open('full_catalogue.json') as f:
    catalogue = json.load(f)

# Find bakery-colors category
bakery = next(c for c in catalogue if c['slug'] == 'bakery-colors')
products = bakery['products']

sprinkle_ids = [
    'dazzling-sprinkles',
    'dazzling-sprinkles-4mm',
    'dazzling-sprinkles-6mm',
    'dazzling-sprinkles-hearts',
    'dazzling-sprinkles-stars',
    'dazzling-sprinkles-vermicelli',
]

sprinkles = [p for p in products if p.get('id') in sprinkle_ids]
non_sprinkles = [p for p in products if p.get('id') not in sprinkle_ids]

# Gather shared colors: use the superset (deduplicate by hex, keep first occurrence)
# We use the 1.5mm entry as the master for shared solid colors
master = next(p for p in sprinkles if p['id'] == 'dazzling-sprinkles')
master_4mm = next(p for p in sprinkles if p['id'] == 'dazzling-sprinkles-4mm')
master_hearts = next(p for p in sprinkles if p['id'] == 'dazzling-sprinkles-hearts')
master_stars = next(p for p in sprinkles if p['id'] == 'dazzling-sprinkles-stars')
master_vermicelli = next(p for p in sprinkles if p['id'] == 'dazzling-sprinkles-vermicelli')

# Deduplicate colors by name across all shapes — use master 1.5mm as canonical for shared
all_color_names = {}
for sp in sprinkles:
    for c in sp.get('colors', []):
        n = c['name']
        if n not in all_color_names:
            all_color_names[n] = {
                'name': n,
                'hex': c.get('hex', '#cccccc'),
                'tone': c.get('tone', n),
                'bgGradient': c.get('bgGradient', None),
                'is_blend': c.get('is_blend', False),
            }

# Build canonical color list (solids first, blends after, sorted by name within group)
solid_colors = {n: v for n, v in all_color_names.items() if not n.startswith('D') or len(n) < 3}
blend_colors = {n: v for n, v in all_color_names.items() if n not in solid_colors}

# Reorder: canonical solids alphabetically, then blends by code
canonical_colors = []
for name in sorted(solid_colors.keys()):
    canonical_colors.append(solid_colors[name])

# Blend codes: DB01-07 (balls), DH01-08 (hearts), DS01-10 (stars), SV01-05 (vermicelli)
blend_order = ['DB01','DB02','DB03','DB04','DB05','DB06','DB07',
               'DH01','DH02','DH03','DH04','DH05','DH06','DH07','DH08',
               'DS01','DS02','DS03','DS04','DS05','DS06','DS07','DS08','DS09','DS010',
               'SV01','SV02','SV03','SV04','SV05']
for code in blend_order:
    if code in blend_colors:
        canonical_colors.append(blend_colors[code])

# Re-assign IDs sequentially
for i, c in enumerate(canonical_colors):
    c['id'] = i + 1

# Shapes definition
shapes = [
    {'id': 'balls-1.5mm', 'name': 'Balls 1.5mm', 'icon': '⚪', 'type': 'ball',
     'colors': [c['name'] for c in master.get('colors', [])]},
    {'id': 'balls-4mm',  'name': 'Balls 4mm',  'icon': '⚪', 'type': 'ball',
     'colors': [c['name'] for c in master_4mm.get('colors', [])]},
    {'id': 'balls-6mm',  'name': 'Balls 6mm',  'icon': '⚪', 'type': 'ball',
     'colors': [c['name'] for c in next(p for p in sprinkles if p['id']=='dazzling-sprinkles-6mm').get('colors', [])]},
    {'id': 'hearts',     'name': 'Hearts',      'icon': '❤️', 'type': 'shape',
     'colors': [c['name'] for c in master_hearts.get('colors', [])]},
    {'id': 'stars',      'name': 'Stars',       'icon': '⭐', 'type': 'shape',
     'colors': [c['name'] for c in master_stars.get('colors', [])]},
    {'id': 'vermicelli', 'name': 'Vermicelli',  'icon': '🥢', 'type': 'strand',
     'colors': [c['name'] for c in master_vermicelli.get('colors', [])]},
]

# Unified product
unified = {
    'id': 'dazzling-sprinkles',
    'name': 'Dazzling Sprinkles Series',
    'image': master.get('image', '/src/assets/bakery-colors/sprikle.png'),
    'tagline': 'Balls · Hearts · Stars · Vermicelli • 18+ Dazzling Shades • Confectioner\'s Glaze Protection',
    'colorDesc': '18 Designer Shades (11 High-Gloss Solids + 7 Confectionery Blends) across all shapes & sizes',
    'categoryName': 'Bakery Colors',
    'grade': master.get('grade'),
    'purity': master.get('purity'),
    'shelfLife': master.get('shelfLife'),
    'placeOfOrigin': master.get('placeOfOrigin'),
    'brandName': master.get('brandName'),
    'modelNumber': 'EXIM-SPRINKLES-SERIES',
    'types': 'Edible Confectionery Sprinkles — Balls (1.5mm / 4mm / 6mm), Hearts, Stars, Vermicelli Strands',
    'applicationSummary': master.get('applicationSummary'),
    'packagingDetails': master.get('packagingDetails'),
    'deliveryDetail': master.get('deliveryDetail'),
    'moq': master.get('moq'),
    'description': master.get('description'),
    'applications': master.get('applications'),
    'applicationTechniques': [
        {
            'title': "Special Confectioner's Glaze Coating",
            'desc': "Engineered with a protective food-grade glaze barrier that prevents moisture bleeding and color leakage when exposed to wet creams, frostings, and mousses.",
            'icon': '🛡️'
        },
        {
            'title': 'Ultimate Pearlescent Finish',
            'desc': 'High-sheen reflective pearl luster gives confections a luxurious jewel-like shimmer that catches light brilliantly.',
            'icon': '✨'
        },
        {
            'title': 'Multiple Shapes & Calibrated Sizes',
            'desc': 'Choose from precision-calibrated balls (1.5mm, 4mm, 6mm), heart-shaped sugar pieces, star shapes, and classic vermicelli strands for every decorating occasion.',
            'icon': '🎨'
        },
        {
            'title': '18+ Curated Shades & Multi-Color Blends',
            'desc': '11 vibrant solid metallic colors plus designer blends (DB, DH, DS, SV series) ready for festive occasions — available across all shapes.',
            'icon': '🎨'
        }
    ],
    'usageGuide': master.get('usageGuide'),
    'packSizes': [
        {'size': '100gm',  'container': 'Bottle', 'target': 'Home Bakers & Retail Confectionery'},
        {'size': '500gm',  'container': 'Pouch',  'target': 'Artisan Patisseries & Bakeries'},
        {'size': '500gm',  'container': 'Jar',    'target': 'Commercial Cake Studios & Kitchens'},
        {'size': '25kg',   'container': 'HDPE Drum', 'target': 'Bulk Industrial & Export Manufacture'},
    ],
    'analysis': master.get('analysis'),
    'storage': master.get('storage'),
    # New fields for dynamic variant axes
    'shapes': shapes,          # Axis 1: shape/size
    'colors': canonical_colors, # Axis 2: colour
    # pack sizes go into packSizes (Axis 3)
}

# Replace the old sprinkle products with the unified one
# Insert at position of the first sprinkle
first_sprinkle_idx = next(i for i, p in enumerate(products) if p.get('id') in sprinkle_ids)
new_products = []
inserted = False
for p in products:
    if p.get('id') in sprinkle_ids:
        if not inserted:
            new_products.append(unified)
            inserted = True
        # Skip old separate entries
    else:
        new_products.append(p)

bakery['products'] = new_products

with open('full_catalogue.json', 'w') as f:
    json.dump(catalogue, f, indent=2, ensure_ascii=False)

print(f'✅ Merged {len(sprinkle_ids)} sprinkle products → 1 unified product')
print(f'   Shapes: {[s["name"] for s in shapes]}')
print(f'   Colors: {len(canonical_colors)} ({len([c for c in canonical_colors if not c.get("bgGradient")])} solid + {len([c for c in canonical_colors if c.get("bgGradient")])} blend)')
print(f'   Pack sizes: {[ps["size"] for ps in unified["packSizes"]]}')
print(f'   Total bakery products: {len(bakery["products"])}')
