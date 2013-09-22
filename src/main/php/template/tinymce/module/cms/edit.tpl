<script type="text/javascript" src="././resources/tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
tinyMCE.init({
		// General options
		mode : "textareas",
		theme : "exxprezzo",

		plugins : "inlinepopups,autoresize,exxprezzo",
		inlinepopups_skin : "redmond",

		theme_advanced_resizing : true,

		link_href : "{fileManager.href}",
		image_href : "{imageManager.href}",
});
</script>

<!-- NOT exists -->
<p>The page does not yet exist. A new page is being created.</p>
<!-- /NOT exists -->
<h1>Edit page {path}</h1>
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
