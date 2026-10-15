<?php

namespace App\Http\Controllers;

use App\Models\Barcode;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Cache;

class LabelsController extends Controller
{
    /**
     * Display the label-printing form.
     */
    public function show(Request $request)
    {
        $barcode_settings = Barcode::select(DB::raw('CONCAT(name, ", ", COALESCE(description, "")) as name, id, is_default'))->get();
        $default          = $barcode_settings->where('is_default', 1)->first();
        $barcode_settings = $barcode_settings->pluck('name', 'id');

        return view('backend.labels.show', compact('barcode_settings'));
    }

    /**
     * Render and return the printable barcode HTML.
     *
     * For each product that carries a wholesale_price we emit TWO label groups:
     *   1. Retail label  — tagged "(Retail)"   — uses product_price
     *   2. Wholesale label — tagged "(Wholesale)" — uses wholesale_price
     */
    public function printLabel(Request $request)
    {
        try {
            $products        = $request->get('products');
            $print           = $request->get('print');
            $barcode_setting = $request->get('barcode_setting');

            // Guard: nothing to print
            if (empty($products) || !is_array($products)) {
                return response('<h3 style="font-family:sans-serif;padding:20px">No products submitted. Please go back and try again.</h3>', 422);
            }

            if (empty($barcode_setting)) {
                return response('<h3 style="font-family:sans-serif;padding:20px">No barcode setting selected. Please go back and choose a paper size.</h3>', 422);
            }

            $barcode_details = Barcode::find($barcode_setting);
            if (!$barcode_details) {
                return response('<h3 style="font-family:sans-serif;padding:20px">Barcode setting #'.(int)$barcode_setting.' not found. Please go back and choose a valid paper size.</h3>', 422);
            }

            // Business name
            $general_setting = Cache::remember('general_setting', 60 * 60 * 24 * 365, function () {
                return DB::table('general_settings')->latest()->first();
            });
            $business_name = $general_setting->company_name ?? '';

            $all_labels = [];

            foreach ($products as $value) {
                $qty           = (int) ($value['quantity'] ?? 1);
                $retailPrice   = $value['product_price']      ?? ($value['default_price'] ?? 0);
                $wholesalePrice= $value['wholesale_price']    ?? null;
                $promoPrice    = $value['product_promo_price'] ?? null;
                $currency      = $value['currency']           ?? '';
                $currencyPos   = $value['currency_position']  ?? 'left';
                $productName   = $value['product_name']       ?? '';
                $subSku        = $value['sub_sku']            ?? '';
                $brandName     = $value['brand_name']         ?? '';
                $productId     = $value['product_id']         ?? null;

                // ── Retail label(s) ──────────────────────────────────
                $retailLabel = [
                    'product_name'        => $productName . (($wholesalePrice) ? ' (Retail)' : ''),
                    'product_actual_name' => $productName,
                    'product_price'       => $retailPrice,
                    'product_promo_price' => $promoPrice,
                    'price_type_label'    => 'Retail',
                    'currency'            => $currency,
                    'currency_position'   => $currencyPos,
                    'product_id'          => $productId,
                    'brand_name'          => $brandName,
                    'product_type'        => 'standard',
                    'sub_sku'             => $subSku,
                    'barcode_type'        => 'C128',
                    'unit'                => 1,
                ];
                for ($i = 0; $i < $qty; $i++) {
                    $all_labels[] = $retailLabel;
                }

                // ── Wholesale label(s) — only when wholesale price exists ──
                if ($wholesalePrice && (float)$wholesalePrice > 0) {
                    $wholesaleLabel = [
                        'product_name'        => $productName . ' (Wholesale)',
                        'product_actual_name' => $productName,
                        'product_price'       => $wholesalePrice,
                        'product_promo_price' => null,
                        'price_type_label'    => 'Wholesale',
                        'currency'            => $currency,
                        'currency_position'   => $currencyPos,
                        'product_id'          => $productId,
                        'brand_name'          => $brandName,
                        'product_type'        => 'standard',
                        'sub_sku'             => $subSku,
                        'barcode_type'        => 'C128',
                        'unit'                => 1,
                    ];
                    for ($i = 0; $i < $qty; $i++) {
                        $all_labels[] = $wholesaleLabel;
                    }
                }
            }

            // ── CONTINUOUS ROLL ──────────────────────────────────────
            if ($barcode_details->is_continuous) {
                $barcode_details->paper_width  = $barcode_details->width;
                $barcode_details->paper_height = $barcode_details->height;

                return view('backend.labels.print_label_continuous')->with([
                    'print'           => $print,
                    'labels'          => $all_labels,
                    'business_name'   => $business_name,
                    'barcode_details' => $barcode_details,
                ]);
            }

            // ── SHEET PRINTING ───────────────────────────────────────
            $stickers_per_sheet        = (int) ($barcode_details->stickers_in_one_sheet ?? 1);
            $product_details_page_wise = [];
            $page                      = 0;

            foreach ($all_labels as $index => $label) {
                if ($index % $stickers_per_sheet === 0) {
                    $product_details_page_wise[$page] = [];
                }
                $product_details_page_wise[$page][] = $label;
                if (($index + 1) % $stickers_per_sheet === 0) {
                    $page++;
                }
            }

            $html = '';
            foreach ($product_details_page_wise as $page_products) {
                $html .= view('backend.labels.print_label')->with([
                    'print'           => $print,
                    'page_products'   => $page_products,
                    'business_name'   => $business_name,
                    'barcode_details' => $barcode_details,
                    'margin_top'      => $barcode_details->top_margin,
                    'margin_left'     => $barcode_details->left_margin,
                    'paper_width'     => $barcode_details->paper_width,
                    'paper_height'    => $barcode_details->paper_height,
                ])->render();
            }

            return response($html);

        } catch (\Throwable $e) {
            \Log::error('[LabelsController] printLabel failed — '
                . $e->getMessage()
                . ' in ' . $e->getFile() . ':' . $e->getLine());

            // Show a readable error page so the root cause is visible in the browser
            $msg  = htmlspecialchars($e->getMessage());
            $file = htmlspecialchars(str_replace(base_path(), '', $e->getFile()));
            $line = $e->getLine();
            $html = <<<HTML
<!DOCTYPE html><html><head><meta charset="utf-8">
<title>Label Print Error</title>
<style>body{font-family:sans-serif;padding:30px;background:#fff7f7}
h2{color:#c00}pre{background:#f4f4f4;padding:12px;border-radius:6px;white-space:pre-wrap;word-break:break-all}</style>
</head><body>
<h2>Label Print Error</h2>
<p><strong>Message:</strong> {$msg}</p>
<p><strong>File:</strong> {$file} &nbsp;<strong>Line:</strong> {$line}</p>
<p style="margin-top:20px"><a href="javascript:window.close()">Close this tab</a> and try again.</p>
</body></html>
HTML;
            return response($html, 500);
        }
    }
}
