<!-- Ga ervan uit dat als er een copro opstaat, dat dit dan leden van het huidig bestuur zijn -->
<!-- if coproItem -->
<h1>Besturen</h1>
<div id="huidigBestuur">
<h2>Huidig Bestuur</h2>
<ul>
<!-- for coproItem -->
	<li class="copro <!-- if coproItem.board -->board<!-- /if coproItem.board -->">
		<h3>{coproItem.name}</h3>
<!-- if coproItem.board -->
		<p class="board" onselectstart="return false;">{coproItem.board}</p>
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
</div>
<div id="coprolist">
<h2>Oudere Besturen</h2>
<!-- /if coproItem -->

<!-- not coproItem -->
<div id="coprolist">
<h2>Commissies</h2>
<!-- /not coproItem -->

<!-- if commissieItem -->
<ul>
	<!-- for commissieItem -->
		<li><a href="{baseURL}{commissieItem.link}">{commissieItem.name}</a></li>
	<!-- /for commissieItem -->
</ul>
</div>
<!-- /if commissieItem -->
