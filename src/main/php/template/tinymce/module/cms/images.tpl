<!-- IF upload -->
<p>Upload new file: {input:file} <input type="submit" /></p>
<!-- /IF upload -->
<p>Note: uploading a file which has the same name as a file that already exists,<br />
will overwrite the existing file</p>
<!-- IF image -->
<!-- FOR image -->
<div>
	<a href="{image.href}" class="callback"><img src="{image.thumbnail.thumb}" alt="" width="200" /><br />{image.file.filename}</a>
</div>
<!-- /FOR image -->
<!-- /IF image -->
<!-- NOT image -->
<p>No files are associated with this page yet.</p>
<!-- /NOT image -->