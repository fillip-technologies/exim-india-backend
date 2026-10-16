<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\AttributeValueComponent;
use App\Models\Category;
use App\Models\FieldAssignment;
use App\Models\FieldDefinition;
use App\Models\FieldValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductPoint;
use App\Models\ProductVariant;
use App\Models\VariantOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Standard Variant Attributes
        $attributes = [
            'colour'       => ['name' => 'Colour', 'input_type' => 'swatch', 'sort_order' => 1],
            'pack_size'    => ['name' => 'Pack Size', 'input_type' => 'pack', 'sort_order' => 2],
            'shape'        => ['name' => 'Shape', 'input_type' => 'text', 'sort_order' => 3],
            'size'         => ['name' => 'Size', 'input_type' => 'text', 'sort_order' => 4],
            'grade'        => ['name' => 'Grade', 'input_type' => 'text', 'sort_order' => 5],
            'variety'      => ['name' => 'Variety', 'input_type' => 'text', 'sort_order' => 6],
            'fruit_family' => ['name' => 'Fruit Family', 'input_type' => 'text', 'sort_order' => 7],
        ];

        $attrModels = [];
        foreach ($attributes as $code => $data) {
            $attrModels[$code] = Attribute::firstOrCreate(['code' => $code], $data);
        }

        // 2. Comprehensive Dynamic Field Definitions
        $fields = [
            'ci_no'               => ['label' => 'C.I. No.', 'type' => 'text', 'group_name' => 'Identification', 'sort_order' => 1],
            'ci_name'             => ['label' => 'C.I. Name', 'type' => 'text', 'group_name' => 'Identification', 'sort_order' => 2],
            'e_number'            => ['label' => 'E Number', 'type' => 'text', 'group_name' => 'Identification', 'sort_order' => 3],
            'fda_name'            => ['label' => 'FD&C Name', 'type' => 'text', 'group_name' => 'Identification', 'sort_order' => 4],
            'color_no'            => ['label' => 'Color No.', 'type' => 'text', 'group_name' => 'Identification', 'sort_order' => 5],
            'cas_no'              => ['label' => 'CAS No.', 'type' => 'text', 'group_name' => 'Identification', 'sort_order' => 6],
            'einecs_no'           => ['label' => 'EINECS No.', 'type' => 'text', 'group_name' => 'Identification', 'sort_order' => 7],
            'ec_no'               => ['label' => 'EC No.', 'type' => 'text', 'group_name' => 'Identification', 'sort_order' => 8],
            'fema_no'             => ['label' => 'FEMA No.', 'type' => 'text', 'group_name' => 'Identification', 'sort_order' => 9],
            'molecular_formula'   => ['label' => 'Molecular Formula', 'type' => 'text', 'group_name' => 'Identification', 'sort_order' => 10],
            'grade'               => ['label' => 'Grade', 'type' => 'text', 'group_name' => 'Quality', 'sort_order' => 11],
            'purity'              => ['label' => 'Purity', 'type' => 'text', 'group_name' => 'Quality', 'sort_order' => 12],
            'shelf_life'          => ['label' => 'Shelf Life', 'type' => 'text', 'group_name' => 'Quality', 'sort_order' => 13],
            'brix'                => ['label' => 'Brix', 'type' => 'number', 'unit' => '°Brix', 'group_name' => 'Technical', 'sort_order' => 14],
            'acidity'             => ['label' => 'Acidity', 'type' => 'text', 'group_name' => 'Technical', 'sort_order' => 15],
            'solubility'          => ['label' => 'Solubility', 'type' => 'text', 'group_name' => 'Technical', 'sort_order' => 16],
            'dosage'              => ['label' => 'Recommended Dosage', 'type' => 'text', 'group_name' => 'Technical', 'sort_order' => 17],
            'brand_name'          => ['label' => 'Brand', 'type' => 'text', 'group_name' => 'Identification', 'sort_order' => 18],
            'model_number'        => ['label' => 'Model / SKU', 'type' => 'text', 'group_name' => 'Identification', 'sort_order' => 19],
            'place_of_origin'     => ['label' => 'Place of Origin', 'type' => 'text', 'group_name' => 'Origin', 'sort_order' => 20],
            'moq'                 => ['label' => 'Minimum Order Quantity', 'type' => 'text', 'group_name' => 'Logistics', 'sort_order' => 21],
            'supply_ability'      => ['label' => 'Supply Ability', 'type' => 'text', 'group_name' => 'Logistics', 'sort_order' => 22],
            'packaging_details'   => ['label' => 'Packaging Details', 'type' => 'text', 'group_name' => 'Logistics', 'sort_order' => 23],
            'delivery_detail'     => ['label' => 'Delivery Detail', 'type' => 'text', 'group_name' => 'Logistics', 'sort_order' => 24],
            'port'                => ['label' => 'Port of Dispatch', 'type' => 'text', 'group_name' => 'Logistics', 'sort_order' => 25],
            'storage'             => ['label' => 'Storage Instructions', 'type' => 'text', 'group_name' => 'Logistics', 'sort_order' => 26],
            'application_summary' => ['label' => 'Application Summary', 'type' => 'text', 'group_name' => 'Applications', 'sort_order' => 27],
            'olfactory_note'      => ['label' => 'Olfactory Note', 'type' => 'text', 'group_name' => 'Sensory', 'sort_order' => 28],
            'pharmacopoeia'       => ['label' => 'Pharmacopoeia Standards', 'type' => 'text', 'group_name' => 'Compliance', 'sort_order' => 29],
            'variety'             => ['label' => 'Variety', 'type' => 'text', 'group_name' => 'Specifications', 'sort_order' => 30],
            'shade_name'          => ['label' => 'Shade Name', 'type' => 'text', 'group_name' => 'Visual', 'sort_order' => 31],
            'shade_color'         => ['label' => 'Shade Color', 'type' => 'text', 'group_name' => 'Visual', 'sort_order' => 32],
            'fruit_family'        => ['label' => 'Fruit Family', 'type' => 'text', 'group_name' => 'Specifications', 'sort_order' => 33],
            'ball_size'           => ['label' => 'Ball Diameter', 'type' => 'text', 'group_name' => 'Physical', 'sort_order' => 34],
            'sprinkle_type'       => ['label' => 'Sprinkle Type', 'type' => 'text', 'group_name' => 'Physical', 'sort_order' => 35],
        ];

        $fieldModels = [];
        foreach ($fields as $code => $data) {
            $fieldModels[$code] = FieldDefinition::firstOrCreate(['code' => $code], [
                ...$data,
                'applies_to' => 'product',
                'is_public'  => true,
            ]);
        }

        // 3. Category Field Assignments
        $categoryFields = [
            'synthetic-food-colours' => ['ci_no', 'e_number', 'fda_name', 'cas_no', 'purity', 'grade', 'shelf_life'],
            'lake-colours'           => ['ci_no', 'e_number', 'fda_name', 'cas_no', 'purity', 'grade'],
            'blended-colours'        => ['cas_no', 'fema_no', 'purity', 'grade', 'shelf_life'],
            'cosmetic-colours'       => ['ci_no', 'cas_no', 'purity', 'grade', 'shelf_life'],
            'pharmaceutical-colours' => ['ci_no', 'e_number', 'pharmacopoeia', 'cas_no', 'purity', 'grade'],
            'chemicals'              => ['cas_no', 'molecular_formula', 'grade'],
            'titanium-dioxide'       => ['ci_name', 'cas_no', 'purity', 'grade'],
            'liquid-flavours'        => ['fema_no', 'dosage', 'solubility', 'shelf_life'],
            'powder-flavours'        => ['fema_no', 'dosage', 'solubility', 'shelf_life'],
            'emulsion-flavours'      => ['fema_no', 'dosage', 'solubility', 'shelf_life'],
            'mango-pulp'             => ['variety', 'brix', 'acidity', 'shelf_life'],
            'natural-fruit-powder'   => ['fruit_family', 'purity', 'shelf_life'],
            'fruit-fragrance'        => ['olfactory_note', 'shelf_life'],
            'natural-food-colors'    => ['ec_no', 'cas_no', 'purity', 'shelf_life'],
            'pearl-pigment-powder'   => ['ci_name', 'cas_no', 'purity', 'shelf_life'],
            'edible-lustre'          => ['ci_name', 'e_number', 'shade_color', 'cas_no', 'shelf_life'],
            'disco-dust-powder'      => ['cas_no', 'purity', 'shelf_life'],
            'food-additives'         => ['cas_no', 'purity', 'shelf_life'],
            'fluorescent-colours'    => ['shade_name', 'shade_color', 'shelf_life'],
            'bakery-colors'          => ['ball_size', 'sprinkle_type', 'grade', 'purity', 'shelf_life'],
        ];

        foreach ($categoryFields as $catSlug => $fCodes) {
            $cat = Category::where('slug', $catSlug)->first();
            if ($cat) {
                foreach ($fCodes as $idx => $fCode) {
                    if (isset($fieldModels[$fCode])) {
                        FieldAssignment::firstOrCreate([
                            'field_definition_id' => $fieldModels[$fCode]->id,
                            'category_id'         => $cat->id,
                        ], [
                            'sort_order' => $idx + 1,
                        ]);
                    }
                }
            }
        }

        // 4. Load Full Frontend Catalogue JSON
        $catalogueFile = __DIR__ . '/data/full_catalogue.json';
        if (! file_exists($catalogueFile)) {
            $this->command?->warn("Catalogue JSON file not found at: {$catalogueFile}");
            return;
        }

        $catalogue = json_decode(file_get_contents($catalogueFile), true);
        if (! is_array($catalogue)) {
            return;
        }

        foreach ($catalogue as $catData) {
            $catSlug = $catData['slug'];
            $category = Category::where('slug', $catSlug)->first();
            if (! $category) {
                $category = Category::create([
                    'slug'        => $catSlug,
                    'name'        => $catData['title'] ?? ucfirst(str_replace('-', ' ', $catSlug)),
                    'description' => $catData['description'] ?? null,
                    'show_in_nav' => ! in_array($catSlug, ['mango-pulp', 'fruit-fragrance']),
                    'nav_label'   => $catData['title'] ?? ucfirst(str_replace('-', ' ', $catSlug)),
                    'is_active'   => true,
                    'sort_order'  => 1,
                ]);
            }

            $products = $catData['products'] ?? [];
            foreach ($products as $pIndex => $p) {
                $slug = $p['id'] ?? $p['slug'] ?? Str::slug($p['name']);

                // Prepare core product attributes
                $productData = [
                    'name'                => $p['name'],
                    'short_name'          => $p['shortName'] ?? null,
                    'tagline'             => $p['tagline'] ?? null,
                    'group_key'           => $p['groupKey'] ?? $p['colorFamily'] ?? $p['fruitFamily'] ?? $p['category'] ?? null,
                    'group_name'          => $p['groupName'] ?? $p['colorFamily'] ?? null,
                    'image'               => $this->normalizeImagePath($p['image'] ?? null),
                    'description'         => $p['description'] ?? null,
                    'usage_guide'         => $p['usageGuide'] ?? null,
                    'moq'                 => $p['moq'] ?? null,
                    'supply_ability'      => $p['supplyAbility'] ?? null,
                    'port'                => $p['port'] ?? null,
                    'cas_no'              => $p['casNo'] ?? null,
                    'other_names'         => $p['otherNames'] ?? null,
                    'mf'                  => $p['mf'] ?? $p['formula'] ?? null,
                    'einecs_no'           => $p['einecsNo'] ?? null,
                    'fema_no'             => $p['femaNo'] ?? null,
                    'place_of_origin'     => $p['placeOfOrigin'] ?? null,
                    'types'               => $p['types'] ?? null,
                    'brand_name'          => $p['brandName'] ?? null,
                    'model_number'        => $p['modelNumber'] ?? null,
                    'grade'               => $p['grade'] ?? null,
                    'color_desc'          => $p['colorDesc'] ?? null,
                    'application_summary' => $p['applicationSummary'] ?? null,
                    'purity'              => $p['purity'] ?? null,
                    'shelf_life'          => $p['shelfLife'] ?? null,
                    'packaging_details'   => $p['packagingDetails'] ?? $p['packaging'] ?? null,
                    'delivery_detail'     => $p['deliveryDetail'] ?? null,
                    'storage'             => $p['storage'] ?? null,
                    'sort_order'          => $pIndex + 1,
                    'is_active'           => true,
                    'is_featured'         => false,
                ];

                $product = Product::updateOrCreate(
                    ['category_id' => $category->id, 'slug' => $slug],
                    $productData
                );

                // 4a. Sync Analysis Specifications
                $analysisRows = $this->formatAnalysisRows($p['analysis'] ?? []);
                if (! empty($analysisRows)) {
                    $product->syncAnalysisSpecs($analysisRows);

                    // Ensure category also has fallback analysis specs if empty
                    if ($category->analysisSpecs()->count() === 0) {
                        $category->syncAnalysisSpecs($analysisRows);
                    }
                }

                // 4b. Sync Detail Attributes
                $detailAttributes = $this->extractDetailAttributes($p);
                if (! empty($detailAttributes)) {
                    $product->syncDetailAttributes($detailAttributes);
                }

                // 4c. Sync Dynamic Field Values (EAV)
                $this->syncFieldValues($product, $p, $fieldModels);

                // 4d. Sync Product Points (Applications & Techniques)
                $this->syncProductPoints($product, $p);

                // 4e. Sync Attributes & Product Variants
                $this->syncProductVariants($product, $p, $attrModels);
            }
        }
    }

    private function normalizeImagePath(?string $img): ?string
    {
        if (! $img) {
            return null;
        }

        if (str_starts_with($img, 'products/')) {
            return $img;
        }

        return preg_replace('#^/src/assets/(img/)?#', 'products/', $img);
    }

    private function formatAnalysisRows(mixed $analysis): array
    {
        if (empty($analysis) || ! is_array($analysis)) {
            return [];
        }

        $rows = [];
        foreach ($analysis as $row) {
            if (isset($row['characteristic'])) {
                $rows[] = [
                    'characteristic' => (string) $row['characteristic'],
                    'requirement'    => (string) ($row['requirement'] ?? ''),
                ];
            }
        }

        return $rows;
    }

    private function extractDetailAttributes(array $p): array
    {
        $map = [
            'colorNo'       => 'Color No.',
            'ciNo'          => 'C.I. No.',
            'ciName'        => 'C.I. Name',
            'eNumber'       => 'E Number',
            'fdcName'       => 'FD&C Name',
            'casNo'         => 'CAS No.',
            'einecsNo'      => 'EINECS No.',
            'ecNo'          => 'EC No.',
            'femaNo'        => 'FEMA No.',
            'formula'       => 'Molecular Formula',
            'mf'            => 'Molecular Formula',
            'grade'         => 'Grade',
            'purity'        => 'Purity',
            'shelfLife'     => 'Shelf Life',
            'olfactoryNote' => 'Olfactory Note',
            'pharmacopoeia' => 'Pharmacopoeia',
            'variety'       => 'Variety',
            'shadeName'     => 'Shade',
            'fruitFamily'   => 'Fruit Family',
            'ballSize'      => 'Ball Size',
            'sprinkleType'  => 'Sprinkle Type',
        ];

        $results = [];
        $seen = [];
        foreach ($map as $key => $label) {
            if (! empty($p[$key]) && is_scalar($p[$key]) && ! isset($seen[$label])) {
                $results[] = [
                    'label' => $label,
                    'value' => (string) $p[$key],
                ];
                $seen[$label] = true;
            }
        }

        return $results;
    }

    private function syncFieldValues(Product $product, array $p, array $fieldModels): void
    {
        $fieldMap = [
            'ci_no'               => $p['ciNo'] ?? null,
            'ci_name'             => $p['ciName'] ?? null,
            'e_number'            => $p['eNumber'] ?? null,
            'fda_name'            => $p['fdcName'] ?? null,
            'color_no'            => $p['colorNo'] ?? null,
            'cas_no'              => $p['casNo'] ?? null,
            'einecs_no'           => $p['einecsNo'] ?? null,
            'ec_no'               => $p['ecNo'] ?? null,
            'fema_no'             => $p['femaNo'] ?? null,
            'molecular_formula'   => $p['mf'] ?? $p['formula'] ?? null,
            'grade'               => $p['grade'] ?? null,
            'purity'              => $p['purity'] ?? null,
            'shelf_life'          => $p['shelfLife'] ?? null,
            'place_of_origin'     => $p['placeOfOrigin'] ?? null,
            'moq'                 => $p['moq'] ?? null,
            'supply_ability'      => $p['supplyAbility'] ?? null,
            'packaging_details'   => $p['packagingDetails'] ?? $p['packaging'] ?? null,
            'delivery_detail'     => $p['deliveryDetail'] ?? null,
            'port'                => $p['port'] ?? null,
            'storage'             => $p['storage'] ?? null,
            'application_summary' => $p['applicationSummary'] ?? null,
            'olfactory_note'      => $p['olfactoryNote'] ?? null,
            'pharmacopoeia'       => $p['pharmacopoeia'] ?? null,
            'variety'             => $p['variety'] ?? null,
            'shade_name'          => $p['shadeName'] ?? null,
            'shade_color'         => $p['shadeColor'] ?? null,
            'fruit_family'        => $p['fruitFamily'] ?? null,
            'ball_size'           => $p['ballSize'] ?? null,
            'sprinkle_type'       => $p['sprinkleType'] ?? null,
        ];

        foreach ($fieldMap as $code => $val) {
            if ($val !== null && $val !== '' && isset($fieldModels[$code])) {
                $fv = FieldValue::firstOrNew([
                    'field_definition_id' => $fieldModels[$code]->id,
                    'owner_type'          => Product::class,
                    'owner_id'            => $product->id,
                ]);
                $fv->setTypedValue($val)->save();
            }
        }
    }

    private function syncProductPoints(Product $product, array $p): void
    {
        $applications = $p['applications'] ?? [];
        $techniques = $p['applicationTechniques'] ?? [];

        if (empty($applications) && empty($techniques)) {
            return;
        }

        $product->points()->delete();
        $sortOrder = 1;

        if (is_array($applications)) {
            foreach ($applications as $app) {
                $title = is_array($app) ? ($app['title'] ?? $app['name'] ?? null) : (string) $app;
                if ($title) {
                    ProductPoint::create([
                        'product_id' => $product->id,
                        'kind'       => ProductPoint::KIND_APPLICATION,
                        'title'      => $title,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }

        if (is_array($techniques)) {
            foreach ($techniques as $tech) {
                if (is_array($tech) && ! empty($tech['title'])) {
                    ProductPoint::create([
                        'product_id' => $product->id,
                        'kind'       => ProductPoint::KIND_TECHNIQUE,
                        'title'      => $tech['title'],
                        'body'       => $tech['desc'] ?? $tech['body'] ?? null,
                        'icon'       => $tech['icon'] ?? null,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }
    }

    private function syncProductVariants(Product $product, array $p, array $attrModels): void
    {
        $colors    = $p['colors'] ?? [];
        $packSizes = $p['packSizes'] ?? [];
        $shapes    = $p['shapes'] ?? [];
        $colorHex  = $p['colorHex'] ?? null;

        // Case 1: Multi-Axis (both multiple colors and pack sizes, e.g. bakery colors)
        if (! empty($colors) && ! empty($packSizes)) {
            $product->updateQuietly(['variant_mode' => 'multi_axis']);

            // ── Attach colour attribute ────────────────────────────────────────────
            ProductAttribute::firstOrCreate([
                'product_id'   => $product->id,
                'attribute_id' => $attrModels['colour']->id,
            ], [
                'sort_order'      => 2,
                'is_required'     => true,
                'is_variant_axis' => true,
            ]);

            // ── Attach pack_size attribute ─────────────────────────────────────────
            ProductAttribute::firstOrCreate([
                'product_id'   => $product->id,
                'attribute_id' => $attrModels['pack_size']->id,
            ], [
                'sort_order'      => 3,
                'is_required'     => true,
                'is_variant_axis' => true,
            ]);

            // ── Attach shape attribute (optional 3rd axis) ─────────────────────────
            $shapeValueModels = [];
            if (! empty($shapes)) {
                ProductAttribute::firstOrCreate([
                    'product_id'   => $product->id,
                    'attribute_id' => $attrModels['shape']->id,
                ], [
                    'sort_order'      => 1,   // shown first — choose shape before colour
                    'is_required'     => true,
                    'is_variant_axis' => true,
                ]);

                foreach ($shapes as $sIdx => $s) {
                    $sName = $s['name'] ?? ('Shape ' . ($sIdx + 1));
                    $sSlug = $s['id'] ?? Str::slug($sName);

                    $shapeVal = AttributeValue::firstOrCreate(
                        ['attribute_id' => $attrModels['shape']->id, 'slug' => $sSlug],
                        [
                            'label'      => $sName,
                            'code'       => $s['icon'] ?? null,
                            'sort_order' => $sIdx + 1,
                        ]
                    );

                    ProductAttributeValue::updateOrCreate([
                        'product_id'         => $product->id,
                        'attribute_value_id' => $shapeVal->id,
                    ], [
                        'sort_order' => $sIdx + 1,
                    ]);

                    $shapeValueModels[$sName] = [
                        'model'  => $shapeVal,
                        'colors' => $s['colors'] ?? [],   // color names available for this shape (empty = all)
                    ];
                }
            }

            // ── Save colour attribute values ────────────────────────────────────────
            $colorValueModels = [];
            foreach ($colors as $cIdx => $c) {
                $cName   = $c['name'] ?? ('Color ' . ($cIdx + 1));
                $valSlug = Str::slug($cName) ?: ('color-' . ($c['id'] ?? $cIdx));

                $val = AttributeValue::firstOrCreate(
                    ['attribute_id' => $attrModels['colour']->id, 'slug' => $valSlug],
                    [
                        'label'      => $cName,
                        'code'       => $cName,
                        'hex'        => $c['hex'] ?? null,
                        'gradient'   => $c['bgGradient'] ?? null,
                        'tone'       => $c['tone'] ?? null,
                        'is_blend'   => $c['is_blend'] ?? false,
                        'sort_order' => $cIdx + 1,
                    ]
                );

                ProductAttributeValue::updateOrCreate([
                    'product_id'         => $product->id,
                    'attribute_value_id' => $val->id,
                ], [
                    'label_override' => $cName,
                    'hex_override'   => $c['hex'] ?? null,
                    'tone_override'  => $c['tone'] ?? null,
                    'sort_order'     => $cIdx + 1,
                ]);

                $colorValueModels[$cName] = $val;
            }

            // ── Link blend components for blended colours ──────────────────────────
            foreach ($colors as $c) {
                if (! empty($c['is_blend']) && ! empty($c['tone']) && isset($colorValueModels[$c['name']])) {
                    $blendVal = $colorValueModels[$c['name']];
                    if (preg_match('/\((.*?)\)/', $c['tone'], $matches)) {
                        $compNames = array_map('trim', explode(',', $matches[1]));
                        $compOrder = 1;
                        foreach ($compNames as $cName) {
                            foreach ($colorValueModels as $candName => $candVal) {
                                if (! $candVal->is_blend
                                    && (stripos($candName, $cName) !== false || stripos($cName, $candName) !== false)
                                ) {
                                    AttributeValueComponent::firstOrCreate([
                                        'blend_value_id'     => $blendVal->id,
                                        'component_value_id' => $candVal->id,
                                    ], [
                                        'ratio'      => 1.0,
                                        'sort_order' => $compOrder++,
                                    ]);
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            // ── Save pack_size attribute values ────────────────────────────────────
            $packValueModels = [];
            foreach ($packSizes as $psIdx => $ps) {
                $psSize = $ps['size'] ?? 'Standard';
                $slug   = Str::slug($psSize);
                preg_match('/([0-9.]+)\s*([a-zA-Z]+)?/', $psSize, $numMatches);
                $numVal = isset($numMatches[1]) ? (float) $numMatches[1] : null;
                $unit   = $numMatches[2] ?? null;

                $val = AttributeValue::firstOrCreate(
                    ['attribute_id' => $attrModels['pack_size']->id, 'slug' => $slug],
                    [
                        'label'         => $psSize,
                        'numeric_value' => $numVal,
                        'unit'          => $unit,
                        'sort_order'    => $psIdx + 1,
                    ]
                );

                ProductAttributeValue::updateOrCreate([
                    'product_id'         => $product->id,
                    'attribute_value_id' => $val->id,
                ], [
                    'container'  => $ps['container'] ?? null,
                    'target'     => $ps['target'] ?? null,
                    'sort_order' => $psIdx + 1,
                ]);

                $packValueModels[] = $val;
            }

            // ── Generate Cartesian variants ─────────────────────────────────────────
            $firstVariantId = null;
            $variantIndex   = 1;

            if (! empty($shapeValueModels)) {
                // 3-axis Cartesian: shape × colour × pack_size
                foreach ($shapeValueModels as $sName => $shapeData) {
                    $shapeVal        = $shapeData['model'];
                    $shapeColorNames = $shapeData['colors'];

                    // Filter to only colors available for this shape (empty => all colors)
                    $filteredColors = empty($shapeColorNames)
                        ? array_values($colorValueModels)
                        : array_values(array_filter(
                            $colorValueModels,
                            fn ($v) => in_array($v->label, $shapeColorNames)
                        ));

                    foreach ($filteredColors as $cVal) {
                        foreach ($packValueModels as $psVal) {
                            $signature = ProductVariant::buildSignature([
                                $attrModels['shape']->id     => $shapeVal->id,
                                $attrModels['colour']->id    => $cVal->id,
                                $attrModels['pack_size']->id => $psVal->id,
                            ]);

                            $skuShape = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($shapeVal->label)), 0, 6));
                            $skuColor = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($cVal->label)), 0, 6));
                            $skuPack  = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($psVal->label)), 0, 5));
                            $sku      = "EX-{$product->id}-{$shapeVal->id}-{$cVal->id}-{$psVal->id}-{$skuShape}-{$skuColor}-{$skuPack}";

                            $isFirst = ($variantIndex === 1);
                            $variant = ProductVariant::updateOrCreate(
                                ['product_id' => $product->id, 'signature' => $signature],
                                [
                                    'sku'          => $sku,
                                    'moq'          => $product->moq,
                                    'stock_status' => ProductVariant::STOCK_ON_REQUEST,
                                    'is_default'   => $isFirst,
                                    'is_active'    => true,
                                    'sort_order'   => $variantIndex++,
                                ]
                            );

                            VariantOption::firstOrCreate(['variant_id' => $variant->id, 'attribute_id' => $attrModels['shape']->id, 'attribute_value_id' => $shapeVal->id]);
                            VariantOption::firstOrCreate(['variant_id' => $variant->id, 'attribute_id' => $attrModels['colour']->id, 'attribute_value_id' => $cVal->id]);
                            VariantOption::firstOrCreate(['variant_id' => $variant->id, 'attribute_id' => $attrModels['pack_size']->id, 'attribute_value_id' => $psVal->id]);

                            if ($isFirst) {
                                $firstVariantId = $variant->id;
                            }
                        }
                    }
                }
            } else {
                // 2-axis Cartesian: colour × pack_size  (standard bakery products)
                foreach (array_values($colorValueModels) as $cVal) {
                    foreach ($packValueModels as $psVal) {
                        $signature = ProductVariant::buildSignature([
                            $attrModels['colour']->id    => $cVal->id,
                            $attrModels['pack_size']->id => $psVal->id,
                        ]);

                        $skuColor = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($cVal->label)), 0, 8));
                        $skuPack  = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($psVal->label)), 0, 6));
                        $sku      = "EX-{$product->id}-{$cVal->id}-{$psVal->id}-{$skuColor}-{$skuPack}";

                        $isFirst = ($variantIndex === 1);
                        $variant = ProductVariant::updateOrCreate(
                            ['product_id' => $product->id, 'signature' => $signature],
                            [
                                'sku'          => $sku,
                                'moq'          => $product->moq,
                                'stock_status' => ProductVariant::STOCK_ON_REQUEST,
                                'is_default'   => $isFirst,
                                'is_active'    => true,
                                'sort_order'   => $variantIndex++,
                            ]
                        );

                        VariantOption::firstOrCreate(['variant_id' => $variant->id, 'attribute_id' => $attrModels['colour']->id, 'attribute_value_id' => $cVal->id]);
                        VariantOption::firstOrCreate(['variant_id' => $variant->id, 'attribute_id' => $attrModels['pack_size']->id, 'attribute_value_id' => $psVal->id]);

                        if ($isFirst) {
                            $firstVariantId = $variant->id;
                        }
                    }
                }
            }

            if ($firstVariantId) {
                $product->updateQuietly(['default_variant_id' => $firstVariantId]);
            }
            return;
        }

        // Case 2: Product with a single color hex
        if ($colorHex) {
            $product->updateQuietly(['variant_mode' => 'single_axis']);

            $val = AttributeValue::firstOrCreate(
                ['attribute_id' => $attrModels['colour']->id, 'slug' => $product->slug . '-color'],
                [
                    'label'      => $product->color_desc ?: $product->name,
                    'hex'        => $colorHex,
                    'sort_order' => 0,
                ]
            );

            $product->attributeValues()->syncWithoutDetaching([$val->id]);

            $variant = $product->variants()->firstOrCreate(
                ['signature' => "{$attrModels['colour']->id}:{$val->id}"],
                [
                    'sku'            => 'EX-' . $product->id . '-' . strtoupper(substr(str_replace('-', '', $product->slug), 0, 10)) . '-STD',
                    'moq'            => $product->moq,
                    'supply_ability' => $product->supply_ability,
                    'is_default'     => true,
                    'is_active'      => true,
                ]
            );

            $variant->variantOptions()->firstOrCreate([
                'attribute_id'       => $attrModels['colour']->id,
                'attribute_value_id' => $val->id,
            ]);

            $product->updateQuietly(['default_variant_id' => $variant->id]);
            return;
        }

        // Case 3: No variants (e.g. industrial chemicals)
        $product->updateQuietly(['variant_mode' => 'none']);
    }
}
