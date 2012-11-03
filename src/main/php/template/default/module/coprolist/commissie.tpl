<!-- if coproItem -->
<ul>
<!-- for coproItem -->
	<li>
		<h3>{coproItem.name}</h3>
		<img src="{coproItem.photoUrl}" width=100px>
	<ul>
		<!-- for coproItem.commissions -->
			<li><a href="{baseURL}{coproItem.commissions.link}">{coproItem.commissions.name}</a></li>
		<!-- /for coproItem.commissions -->
	</ul>
	</li>
<!-- /for coproItem -->
</ul>
<!-- /if coproItem -->

{test}