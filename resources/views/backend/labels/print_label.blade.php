<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">

    <style type="text/css">
        td{
            /* border: 1px dotted lightgray; */
            padding: 0px !important;
            margin: 0px !important;
        }
        @media print {

            * {
                box-sizing: border-box;
            }

            tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            td {
                padding: 0;
                vertical-align: top;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .label-box {
                width: {{$barcode_details->width}}in;
                height: {{$barcode_details->height}}in;
                padding: 2px;
                overflow: hidden;
                font-size: 10px;
                line-height: 1.1;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .label-box div,
            .label-box span {
                margin: 0;
                padding: 0;
            }

            .barcode-img {
                width: 90%;
                height: auto;
                max-height: {{$barcode_details->height * 0.40}}in;
                display: block;
                margin: 2px auto;
            }

        }
    </style>
    
</head>

<body onload="window.print()">

    <table align="center" style="border-spacing: {{$barcode_details->col_distance * 1}}in {{$barcode_details->row_distance * 1}}in; border-collapse: separate; overflow: hidden !important;">
        @foreach($page_products as $page_product)

        @if($loop->index % $barcode_details->stickers_in_one_row == 0)
        <!-- create a new row -->
        <tr>
        <!-- <columns column-count="{{$barcode_details->stickers_in_one_row}}" column-gap="{{$barcode_details->col_distance*1}}"> -->
        @endif
        <td align="center" valign="center">
            <div class="label-box">
                <div>

                    {{-- Business Name --}}
                    @if(!empty($print['business_name']))
                        <b style="display: block !important; font-size: {{$print['business_name_size']}}px">{{$business_name}}</b>
                    @endif

                    {{-- Product Name --}}
                    @if(!empty($print['name']))
                        <span style="display: block !important; font-size: {{$print['name_size']}}px">
                            {{$page_product['product_actual_name']}}
                        </span>
                    @endif

                    {{-- Brand Name --}}
                    @if(!empty($print['brand_name']))
                        <span style="display: block !important; font-size: {{$print['brand_name_size']}}px">
                            {{$page_product['brand_name']}}
                        </span>
                    @endif

                    {{-- Variation --}}

                    {{-- product_custom_fields --}}
                    {{-- <br> --}}
    {{--
                    @if(!empty($print['packing_date']) && !empty($page_product->packing_date))
                        <span style="font-size: {{$print['packing_date_size']}}px">
                            <b>@lang('lang_v1.packing_date'):</b>
                            {{$page_product->packing_date}}
                        </span>
                    @endif --}}
                    {{-- Price Type Badge (Retail / Wholesale) --}}
                    @if(!empty($page_product['price_type_label']))
                    <span style="display:block;font-size:9px;font-weight:bold;text-transform:uppercase;
                          color: {{ $page_product['price_type_label'] === 'Wholesale' ? '#0066CC' : '#333' }};
                          letter-spacing:.04em;margin-bottom:1px;">
                        {{ $page_product['price_type_label'] }}
                    </span>
                    @endif

                    {{-- Price --}}
                    @if(!empty($print['price']))
                    @php
                        $lbl_currency = $page_product['currency'] ?? '';
                        // Map 'left'/'right' → 'prefix'/'suffix' for format_currency()
                        $lbl_cur_pos  = (($page_product['currency_position'] ?? 'left') === 'left') ? 'prefix' : 'suffix';
                        $lbl_promo    = isset($page_product['product_promo_price'])
                                            ? (float) $page_product['product_promo_price']
                                            : 0;
                        $lbl_has_promo = isset($print['promo_price'])
                                            && $page_product['price_type_label'] !== 'Wholesale'
                                            && $lbl_promo > 0;
                    @endphp
                    <span style="font-size: {{$print['price_size']}}px;">
                        @if($lbl_has_promo)
                            <span style="text-decoration: line-through; color:#888;">
                                {{format_currency($page_product['product_price'], $lbl_currency, $lbl_cur_pos)}}
                            </span>
                            <span style="color:#c00; font-weight:bold;">
                                {{format_currency($lbl_promo, $lbl_currency, $lbl_cur_pos)}}
                            </span>
                        @else
                            {{format_currency($page_product['product_price'], $lbl_currency, $lbl_cur_pos)}}
                        @endif
                    </span>
                    @endif
                    {{-- Barcode --}}
                    <!-- <img style="max-width:90% !important;height: {{$barcode_details->height*0.24}}in !important; display: block;" src="data:image/png;base64,{{DNS1D::getBarcodePNG($page_product['sub_sku'], $page_product['barcode_type'], 1,30, array(0, 0, 0), false)}}"> -->
                    <img class="barcode-img" src="data:image/png;base64,{{ DNS1D::getBarcodePNG($page_product['sub_sku'], $page_product['barcode_type'], 2, 50) }}">

                    <span style="font-size: 10px !important">
                        {{$page_product['sub_sku']}}
                    </span>
                </div>
            </div>

        </td>

        @if($loop->iteration % $barcode_details->stickers_in_one_row == 0)
            </tr>
        @endif
        @endforeach
    </table>
</body>
