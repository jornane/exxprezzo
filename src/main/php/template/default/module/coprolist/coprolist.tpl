<!-- if coproItem -->
<ul>
<!-- for coproItem -->
	<li>
		<h3>{coproItem.name}<h3>
		<img src="{coproItem.photoUrl}">
	</li>
	<ul>
		<!-- for coproItem.commissions -->
			<li><a href="{coproItem.commissions.link}>{coproItem.commissions.name}</a></li>
		<!-- /for coproItem.commissions -->
		</ul>
<!-- /for coproItem -->
</ul>
<!-- /if coproItem -->

{test}