function setClearForm($elId,$value) {
	var $id=$($elId);
	var $existing=$id.val();
	if($existing==$value){
		$id.val("");
	}
	else if($existing==""){
		$id.val($value);
	}
}
$(document).ready(function() {
	$('div.ig_thumb_col a').lightBox();
	$('#hashtag').bind({
		focus: function(){setClearForm('#hashtag','Type a Hashtag to Search')},
		blur:  function(){setClearForm('#hashtag','Type a Hashtag to Search')}
	});
});
