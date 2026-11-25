# FalconMedia_LinkedVariants

Links products that are variants of each other (colour, size, scent, region, ...) and shows them on the product
page as a row of tiles above the product options. Each tile is a plain link to the other product page, so the
variants stay separate products with their own URL, images and stock. Built for Magento 2 / Mage-OS with a Hyvä
theme.

**Hyvä only.** The storefront part ships a layout for the `hyva_` handles, a Tailwind template and a view model based on
`Hyva\Theme\ViewModel\CurrentProduct`. On a Luma theme nothing is rendered on the product page; the admin, the link
type and the API work on any theme.

## Installation

Replacing an earlier custom module that used the link type `color_variant`: disable or remove that module first, then
run `setup:upgrade`, so the data patch can take over its link type.

```bash
bin/magento module:disable <Vendor>_<OldModule>   # only when replacing an earlier module

composer require falconmedia/magento2-linked-variants
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento hyva:config:generate
npm --prefix app/design/frontend/<Vendor>/<theme>/web/tailwind run build
bin/magento cache:flush
```

Requires PHP 8.3+, Magento 2.4.x or Mage-OS, and `hyva-themes/magento2-theme-module`.

`setup:upgrade` creates the product link type `variant`. A link type `color_variant` from an earlier custom module is
renamed in place by the data patch, so existing links and their order are kept. If both types exist, the legacy links
are merged into `variant` with their positions and the legacy type is removed. The patch is safe to run when none, one
or both exist.

### Known limits

- Multi Source Inventory with more than one stock is not tested. Stock is read through the CatalogInventory stock
  status of the website, which MSI adapts for its default stock.
- A colour swatch is rendered with an inline `style="background-color: ..."` attribute. With a strict Content Security
  Policy this needs `style-src 'unsafe-inline'` (or Hyvä's CSP setup that allows inline style attributes); otherwise
  the swatch stays empty. Image and text modes have no inline styles.

## Admin

Catalog > Products > edit product > Related Products, Up-Sells, and Cross-Sells > **Linked Variants**.
Select the other variants in the grid, like related products. Links are kept as one group: linking A to B and C also
links B and C to each other and to A. Removing a product from the grid removes it from the whole group. The order in
the grid is the order on the product page.

The group is also kept in sync when links are changed through the API with link type `variant`: product saves with
`product_links` and `POST /V1/products/{sku}/links` (via the product save), and `PUT /V1/products/{sku}/links` and
`DELETE /V1/products/{sku}/links/variant/{linkedProductSku}` (product link repository `save`, `delete` and
`deleteById`).

Stores > Configuration > Catalog > **Linked Variants**, per store view:

| Setting | Meaning |
| --- | --- |
| Show Linked Variants on the Product Page | Enables the row. |
| Title | Text above the row. Empty: the store label of the name attribute. |
| Name Attribute | Dropdown or multiple select attribute that names a variant (for example colour, size, scent). Default: text after the last dash in the product name. |
| Display | Product image, swatch or text only. |
| Swatch Attribute | Attribute whose visual swatch is shown. Default: the name attribute. |
| Show Out of Stock Variants | Dimmed, still linked. |
| Out of Stock Text | Read out by screen readers. Empty: the default text. |

All texts go through the translation layer (`src/i18n`).

## Theme setup (Hyvä)

All classes use the prefix `fmlv-` and all custom properties the prefix `--fmlv-`, so nothing clashes with your theme.
The only Tailwind utility in the template is `sr-only`, so the template must be part of the Tailwind build (step 2).
The layout is `catalog_product_view_hyva.xml`; the module registers itself for `hyva:config:generate`.

1. Import the component CSS in your theme's `tailwind-source.css` (adjust the relative path):

   ```css
   @import "../../../../../../../vendor/falconmedia/magento2-linked-variants/src/view/frontend/tailwind/fm-linked-variants.css";
   ```

2. Let Tailwind see the module templates. Either run `bin/magento hyva:config:generate` (adds the module to
   `hyva-themes.json`) or add the path to the `content` array of your theme's `tailwind.config.js`:

   ```js
   content: [
       // ...
       '../../../../../../../vendor/falconmedia/magento2-linked-variants/src/view/frontend/templates/**/*.phtml',
   ],
   ```

3. Optional: map the properties onto your shop tokens. Every property has a neutral fallback.

   ```css
   :root {
       --fmlv-text: var(--shop-text);
       --fmlv-border: var(--shop-border);
       --fmlv-border-hover: var(--shop-text-muted);
       --fmlv-border-current: var(--shop-primary);
       --fmlv-focus: var(--shop-primary);
       --fmlv-radius: var(--shop-radius);
   }
   ```

| Property | Used for |
| --- | --- |
| `--fmlv-text` | Text colour |
| `--fmlv-text-muted` | Text of out of stock variants |
| `--fmlv-border` | Border of a tile |
| `--fmlv-border-hover` | Border on hover |
| `--fmlv-border-current` | Border of the variant being viewed |
| `--fmlv-focus` | Focus ring |
| `--fmlv-bg` | Tile background |
| `--fmlv-radius` | Corner radius |
| `--fmlv-gap` | Space between tiles |
| `--fmlv-padding` | Space inside a tile |
| `--fmlv-image-size` | Width and height of the image or swatch |
| `--fmlv-font-size` | Label size |
| `--fmlv-unavailable-opacity` | Opacity of out of stock images |
| `--fmlv-margin-bottom` | Space below the row |

## API

```php
use FalconMedia\LinkedVariants\Api\VariantLinksInterface;

/** @var VariantLinksInterface $variantLinks */
$variantLinks->getVariantLinks([12, 13]); // [12 => [14, 15], 13 => [14, 15]], linked ids in position order
$variantLinks->getLinkedProductIds(12);   // [14, 15]
```

The link type id is looked up by the code `variant` (`FalconMedia\LinkedVariants\Model\LinkType`); do not hardcode it.
The type is also registered with the core `LinkTypeProvider`, so `variant` works with the product link repository.

## Development

```bash
LINKED_VARIANTS_AUTOLOAD=/path/to/magento/vendor/autoload.php vendor/bin/phpunit
vendor/bin/phpstan analyse -c phpstan.neon --autoload-file=/path/to/magento/vendor/autoload.php
```

## License

MIT
