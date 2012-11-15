<!-- if coproItem -->
<ul>
<!-- for coproItem -->
	<li class="copro <!-- if coproItem.board -->board<!-- /if coproItem.board -->">
		<h3>{coproItem.name}</h3>
<!-- if coproItem.board -->
		<p class="board" onselectstart="return false;">Board {coproItem.board}</p>
<!-- /if coproItem.board -->
		<p class="pic"><img src="{coproItem.photoUrl}"></p>
	<ul>
		<!-- for coproItem.commissions -->
			<li><a href="{baseURL}{coproItem.commissions.link}">{coproItem.commissions.name}</a></li>
		<!-- /for coproItem.commissions -->
	</ul>
	</li>
<!-- /for coproItem -->
</ul>
<!-- /if coproItem -->

<script type="text/javascript">
/*
var rotate = function(){
	var val = "rotate("+(Math.random()*4-2)+"deg)";
	$(this).css("-webkit-transform", val);
	$(this).css("-moz-transform", val);
	$(this).css("-o-transform", val);
	$(this).css("-ms-transform", val);
};
$(".copro").each(rotate);
*/
$(".copro p img").each(function(){
	$(this).parent().css("background-image", "url(" + $(this).attr('src') + ")");
	$(this).remove();
});
</script>
