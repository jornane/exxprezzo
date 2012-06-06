<h1>Edit page {PATH}</h1>
<p>Title:<br />
	{input:title}</p>
<p>Read privileges:<br />
	<!-- input for readprivileges -->
	<input type="checkbox" name="{readprivileges.name}" value="{readprivileges.value}">{readprivileges.caption}<br />
	<!-- /input for readprivileges -->
</p>
<p>Write privileges:<br />
	{writeprivileges}</p>
<p>Content:<br />
<!-- INPUT IF content -->
<textarea name="{content.name}">{content.value}</textarea>
<!-- /INPUT IF content -->
</p>
<!-- INPUT NOT content -->
<p>NOT CONTENT</p>
<!-- /INPUT NOT content -->
