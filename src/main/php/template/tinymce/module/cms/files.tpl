<!-- IF upload -->
<p>Upload new file: {input:file} <input type="submit" /></p>
<!-- /IF upload -->
<p>Note: uploading a file which has the same name as a file that already exists,<br />
will overwrite the existing file</p>
<!-- IF file -->
<table>
	<tr>
		<th>Filename</th>
		<th>Created</th>
		<th>Last modified</th>
		<th>Size</th>
	</tr>
<!-- FOR file -->
	<tr>
		<td><a href="{file.href}" class="callback">{file.file.filename}</a></td>
		<td>{file.file.created}</td>
		<td>{file.file.updated}</td>
		<td>{file.file.size}</td>
	</tr>
<!-- /FOR file -->
</table>
<!-- /IF file -->
<!-- NOT file -->
<p>No files are associated with this page yet.</p>
<!-- /NOT file -->