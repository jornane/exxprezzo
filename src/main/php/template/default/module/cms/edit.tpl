<!-- NOT exists -->
<p>The page does not yet exist. A new page is being created.</p>
<!-- /NOT exists -->
<h1>Edit page {PATH}</h1>
<p>Title:<br />
	{input:title}</p>
<p>Read privileges:<br />
	<!-- INPUT FOR readprivileges -->
	<input type="checkbox" name="{readprivileges.name}" value="{readprivileges.value}">{readprivileges.caption}<br />
	<!-- /INPUT FOR readprivileges -->
</p>
<p>Write privileges:<br />
	{writeprivileges}</p>
<p>Content:<br />
<p>{input:content}</p>
<p><input type="submit" /> {input:delete}</p>
