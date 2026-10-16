import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const backendDir = path.resolve(__dirname, '..');
const frontendDir = path.resolve(backendDir, '../exim-india-frontend');

async function run() {
  console.log('Loading Vite from frontend node_modules...');
  const { createServer } = await import(`${frontendDir}/node_modules/vite/dist/node/index.js`);

  const server = await createServer({
    root: frontendDir,
    server: { middlewareMode: true },
  });

  console.log('Importing frontend constants...');
  const bakeryMod = await server.ssrLoadModule('/src/constants/bakeryColorsData.js');
  const chemMod = await server.ssrLoadModule('/src/constants/chemicalsData.js');
  const navMod = await server.ssrLoadModule('/src/constants/navigation.js');

  await server.close();

  // Ensure directories exist
  const dataDir = path.join(backendDir, 'database/seeders/data');
  const bakeryStorageDir = path.join(backendDir, 'storage/app/public/products/bakery-colors');
  const chemicalsStorageDir = path.join(backendDir, 'storage/app/public/products/chemicals');
  fs.mkdirSync(dataDir, { recursive: true });
  fs.mkdirSync(bakeryStorageDir, { recursive: true });
  fs.mkdirSync(chemicalsStorageDir, { recursive: true });

  // Copy images
  const bakerySrcAssets = path.join(frontendDir, 'src/assets/bakery-colors');
  if (fs.existsSync(bakerySrcAssets)) {
    for (const file of fs.readdirSync(bakerySrcAssets)) {
      fs.copyFileSync(path.join(bakerySrcAssets, file), path.join(bakeryStorageDir, file));
    }
  }

  const chemSrcAssets = path.join(frontendDir, 'src/assets/chemicals');
  if (fs.existsSync(chemSrcAssets)) {
    for (const file of fs.readdirSync(chemSrcAssets)) {
      fs.copyFileSync(path.join(chemSrcAssets, file), path.join(chemicalsStorageDir, file));
    }
  }

  // Process Bakery Products
  const bakeryProducts = bakeryMod.BAKERY_PRODUCTS.map((p) => {
    let imgName = null;
    if (typeof p.image === 'string') {
      imgName = path.basename(p.image);
    }

    return {
      slug: p.id,
      name: p.name,
      tagline: p.tagline || null,
      description: p.description || null,
      usage_guide: p.usageGuide || null,
      image: imgName ? `products/bakery-colors/${imgName}` : null,
      category: 'bakery-colors',
      grade: p.grade || null,
      purity: p.purity || null,
      shelf_life: p.shelfLife || null,
      place_of_origin: p.placeOfOrigin || null,
      brand_name: p.brandName || null,
      model_number: p.modelNumber || null,
      types: p.types || null,
      color_desc: p.colorDesc || null,
      application_summary: p.applicationSummary || null,
      packaging_details: p.packagingDetails || null,
      delivery_detail: p.deliveryDetail || null,
      moq: p.moq || null,
      storage: p.storage || null,
      applications: p.applications || [],
      application_techniques: (p.applicationTechniques || []).map((t) => ({
        title: t.title,
        body: t.desc || t.body || null,
        icon: t.icon || null,
      })),
      analysis: p.analysis || [],
      pack_sizes: (p.packSizes || []).map((ps) => ({
        size: ps.size,
        container: ps.container || null,
        target: ps.target || null,
      })),
      colors: (p.colors || []).map((c) => ({
        id: c.id,
        name: c.name,
        hex: c.hex,
        tone: c.tone || null,
        bgGradient: c.bgGradient || null,
        is_blend: Boolean(c.bgGradient || (c.name && (c.name.startsWith('DB') || c.name.startsWith('DH') || c.name.startsWith('DS') || c.name.startsWith('SV')))),
      })),
    };
  });

  // Process Chemicals Products
  const chemicalsProducts = chemMod.CHEMICALS_PRODUCTS.map((p) => {
    let imgName = null;
    if (typeof p.image === 'string') {
      imgName = path.basename(p.image);
    }

    return {
      slug: p.id,
      name: p.name,
      short_name: p.shortName || null,
      cas_no: p.casNo || null,
      mf: p.formula || null,
      grade: p.grade || null,
      group_name: p.category || null,
      group_key: p.category ? p.category.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') : null,
      image: imgName ? `products/chemicals/${imgName}` : null,
      description: p.description || null,
      category: 'chemicals',
      variant_mode: 'none',
    };
  });

  fs.writeFileSync(
    path.join(dataDir, 'bakery_products.json'),
    JSON.stringify(bakeryProducts, null, 2)
  );

  fs.writeFileSync(
    path.join(dataDir, 'chemicals_products.json'),
    JSON.stringify(chemicalsProducts, null, 2)
  );

  console.log(`Exported ${bakeryProducts.length} bakery products to bakery_products.json`);
  console.log(`Exported ${chemicalsProducts.length} chemical products to chemicals_products.json`);
}

run().catch((err) => {
  console.error(err);
  process.exit(1);
});
