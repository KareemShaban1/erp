    <div class="card-pos-trans ml-2">
        <div class=" flex-c card-body-pos-trans" style="padding: 5px;">
            {{-- <span id=""  class="plusSlide" onclick="moveToC('right');" style="float:right;">&#10094;</span> --}}
            <div onclick="event.preventDefault();moveToC2('top');" class="button outline_ top" style="width: 60px;">&#708;</div>	
            <div class="card-bv">
                    <div class="side-btn-categories">
						<div  category-id="all" class="product_sub_category_item button outline_ product_category_item">@lang('lang_v1.all_category')</div>

						@foreach($categories as $category)
                        <div class="button outline_ product_category_item" id="" type="submit" category-id="{{$category['id']}}">{{$category['name']}}</div>
							@if(!empty($category['sub_categories']))
                                    @foreach($category['sub_categories'] as $sc)
                                        <div id="" type="submit" category-id="{{$sc['id']}}" class="product_sub_category_item button outline_ product_category_item">{{$sc['name']}}</div>
                                    @endforeach
                            @endif
                        @endforeach
                    </div>
                </div>
                <div onclick="event.preventDefault(); moveToC2('bottom');" class="button outline_  bottom" style="width: 60px;">&#709;</div>
            {{-- <span class=" plusSlide " onclick="moveToC('left');" style="float:left;">&#10095;</span> --}}
        </div>
    </div>

@section('css')
	@parent
	<style type="text/css">
.container-bv{
	overflow: hidden;
	width: 600px;
	margin-left: 5px;
			margin-right: 5px;
}
	.side-btn-categories{
			width: auto;
			margin-right: 3px;
    		transition: all 0.8s ease 0s;
		}
		.card-bv{
			height: 300px;
			margin-top: 50px;
			overflow: hidden;
		}
		.mySlides {display:none;}
		.plusSlide{
			font-weight: bolder;
			font-size: 15px;
			cursor: pointer;
			font-weight: bolder;
			font-size: 15px;
			cursor: pointer;
			z-index: 54545;
			padding-right: 10px;
			padding-left: 10px;
			padding-bottom: 2px;
		}

		.container-category{
			margin-right: 0px;
			padding-top: 1px;
			width: fit-content;
			transition: all 0.8s ease 0s;

		}
		.container-category div{
			border-left: 0px solid white;
			border-radius: 10px;
			padding: 1px 6px;
			cursor: pointer;
			color:white ;
			background-color: #0095e8 ;
			display: inline-block;	
			transition: 0.3s;
			margin:0px 2px;
			width: max-content;
			
		}
		.container-category div:hover{
			color:white ;
			background-color: #B5D6F7 ;
			border-radius: 12px;
			padding: 0px 8px;
		}
		.flex{
			display: flex;
			flex-direction: row;
			justify-content: space-between;
		}
		.flex-c{
			display: flex;
			flex-direction: column;
			justify-content: space-between;
		}
		.categories_{
			margin: 10px 5px;
			border: 1px solid white;
			border-radius: 4px;
			color: white;
			margin-right:5px; 
			
		}

	</style>
	<style type="text/css">
		.button {
			cursor: pointer;
			outline: none;
		}
		.button.outline_ {
			position: relative;
			z-index: 3;
			background: #0095e8;
			color: white;
			font-size: 13px;
			border-color: #006faa;
			border-style: solid;
			border-width: 1px;
			border-radius: 14px;
			font-weight: bolder;
			padding: 2px 4px;
			text-transform: uppercase;
			transition: all 0.2s linear;
			padding-top: 4px;
			margin-top: 10px;
			text-align: center;
		}
		.button.outline_:hover {
			color: #0095e8;
			background: #B5D6F7;
			border-color: #006faa ;
			transition: all 0.2s linear;		}
		.button.outline:active {
			border-radius: 14px;
		}
		.outline_.top , .outline_.bottom{
			background: transparent;
			font-size: 17px;
			font-weight: bolder;
			color: white;
			align-self: center;
			padding-top: unset;
			position: absolute;
			z-index: 10000;
			padding: 0px 2px;
		}
		.outline_.top {
			/* top:3px; */
		}
		.outline_.bottom {
			bottom:-55px;
		}
		.outline_.top:hover , .outline_.bottom:hover{
			color: black;
			background: #0095e8;
			border-color: #006faa ;
			transition: all 0.2s linear;
		}
	</style>
@endsection
@section('javascript')
@parent
<script type="text/javascript">
function moveToC2(direction){
	var direction_value ="100px";
	var c_height;
	if(direction=='top'){
	
		c_height = $(".side-btn-categories").outerHeight()+250;
		c_top = parseInt($(".side-btn-categories").prop('style').marginTop)
		if(c_top <= 0){

			$(".side-btn-categories").css({
                     "margin-top": `+=${direction_value}` });
		}else{
			var x = $(".side-btn-categories").height()-250;
				$(".side-btn-categories").css(
					{"margin-top":"-="+x+"px"});
			}
		
	}else if(direction=='bottom'){
		c_top = parseInt($(".side-btn-categories").prop('style').marginTop)+400
			if(c_top >= 0){
			$(".side-btn-categories").css({
				"margin-top": `-=${direction_value}`});
			}else{
				$(".side-btn-categories").css(
					{"margin-top":'50px'});
			}
	}
}
// #0095e8 
// box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px; 
</script>

@endsection