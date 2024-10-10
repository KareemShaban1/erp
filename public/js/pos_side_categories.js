
function moveToC2(direction){
	var direction_value ="100px";
	var c_height;
	if(direction=='top'){
	
		c_height = $(".side-btn-categories").outerHeight()+400;
		c_top = parseInt($(".side-btn-categories").prop('style').marginTop)
		if(c_top <= 0){

			$(".side-btn-categories").css({
                     "margin-top": `+=${direction_value}` });
		}else{
			var x = $(".container-category").width()-400;
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