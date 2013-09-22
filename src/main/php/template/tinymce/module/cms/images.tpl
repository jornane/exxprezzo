<!-- IF upload -->
<p>Upload new file: {input:file} <input type="submit" /></p>
<p>Note: uploading a file which has the same name as a file that already exists,<br />
will overwrite the existing file</p>
<!-- /IF upload -->
<!-- FOR image -->
<div>
	<a href="{image.href}" id="file{image.fileId}" class="callback"><img src="{image.thumb.href}" alt="" width="200" /><br />{image.filename}</a>
</div>
<!-- /FOR image -->
<!-- NOT image -->
<p>No files are associated with this page yet.</p>
<!-- /NOT image -->
