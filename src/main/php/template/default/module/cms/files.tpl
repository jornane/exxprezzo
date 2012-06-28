<p>Upload new file: {input:file} <input type="submit" /></p>
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
		<td><a href="{file.href}">{file.filename}</a></td>
		<td>{file.created}</td>
		<td>{file.updated}</td>
		<td>{file.size}</td>
	</tr>
<!-- /FOR file -->
</table>
<!-- /IF file -->
<!-- NOT file -->
<p>No files are associated with this page yet.</p>
<!-- /NOT file -->