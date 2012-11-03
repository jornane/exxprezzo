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

<!-- if commissieItem -->
<ul>
	<!-- for commissieItem -->
		<li><a href="{baseURL}{commissieItem.link}">{commissieItem.name}</a></li>
	<!-- /for commissieItem -->
</ul>
<!-- /if commissieItem -->

{test}