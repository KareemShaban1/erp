<table align="center" style=" overflow: hidden !important;">
@foreach($page_products as $page_product)

{{-- 	@if($loop->index % $barcode_details->stickers_in_one_row == 0)
		<!-- create a new row -->
		<tr>
		<!-- <columns column-count="{{$barcode_details->stickers_in_one_row}}" column-gap="{{$barcode_details->col_distance*1}}"> -->
	@endif --}}
		<td align="center" valign="center">
			<div style="overflow: hidden !important;display: flex; flex-wrap: wrap;align-content: center;width: 2.5 in; height: 1.5 in;">


				<div>

					{{-- Business Name --}}
					@if(!empty($print['business_name']))
						<b style="margin-bottom: 5px;display: block !important; font-size: {{20*$factor}}px">{{$business_name}}</b>
					@endif

					{{-- Product Name --}}
					@if(!empty($print['name']))
						<span style="margin-bottom: 1px;display: block !important; font-size: {{22*$factor}}px">
							{{$page_product->product_actual_name}}

						</span>
					@endif

					{{-- Variation --}}
					@if(!empty($print['variations']) && $page_product->is_dummy != 1)
						<span style="margin-bottom: 10px;display: block !important; font-size: {{22*$factor}}px">
							<b>{{$page_product->product_variation_name}}</b>:{{$page_product->variation_name}}
						</span>
					@endif

					{{-- Price --}}
					@if(!empty($print['price']))
					<span style="margin-bottom: 5px;font-size: {{22*$factor}}px">
						<b style="margin-bottom: 5px;">@lang('lang_v1.price'):</b>
						{{session('currency')['symbol'] ?? ''}}


						@if($print['price_type'] == 'inclusive')
							{{@num_format($page_product->sell_price_inc_tax)}}
						@else
							{{@num_format($page_product->default_sell_price)}}
						@endif
					</span>
					@endif
					@if(!empty($print['exp_date']) && !empty($page_product->exp_date))
						<br>
						<span style="font-size: {{14*$factor}}px">
							<b>@lang('product.exp_date'):</b>
							{{$page_product->exp_date}}
						</span>
						@if($barcode_details->is_continuous)
						<br>
						@endif
					@endif

					@if(!empty($print['packing_date']) && !empty($page_product->packing_date))
						<span style="font-size: {{14*$factor}}px">
							<b>@lang('lang_v1.packing_date'):</b>
							{{$page_product->packing_date}}
						</span>
					@endif
					<br>

					{{-- Barcode --}}
					<img style="max-width:150px !important;height: 70px !important;" src="data:image/png;base64,{{DNS1D::getBarcodePNG($page_product->sub_sku, $page_product->barcode_type, 3,30,array(39, 48, 54), true)}}">
				</div>
			</div>

		</td>

	@if($loop->iteration % $barcode_details->stickers_in_one_row == 0)
		</tr>
	@endif
@endforeach
</table>

<style type="text/css">
	@media print{

		table{
			page-break-after: always;
		}
		@page {
		size: {{$paper_width}}in {{$paper_height}}in;

		/*width: {{$barcode_details->paper_width}}in !important;*/
		/*height:@if($barcode_details->paper_height != 0){{$barcode_details->paper_height}}in !important @else auto @endif;*/
		margin-top: {{$margin_top}}in !important;
		margin-bottom: {{$margin_top}}in !important;
		margin-left: {{$margin_left}}in !important;
		margin-right: {{$margin_left}}in !important;
	}
	}
</style>
