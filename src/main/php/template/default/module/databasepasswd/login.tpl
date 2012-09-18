<!-- NS login -->
<h1>Login</h1>
<table>
	<tr>
		<th>Username:</th>
		<td>{input:username}</td>
	</tr>
		<th>Password:</th>
		<td>{input:password}</td>
	</tr>
	<tr>
		<td colspan="2">
			<input type="submit" value="Login" />
		</td>
	</tr>
</table>
<!-- /NS login -->
<!-- NS logout -->
<table>
	<tr>
		<th>Currently logged in:</th>
		<td>{realName}</td>
	</tr>
	<tr>
		<th>Last login:</th>
		<td>{lastLogin}</td>
	</tr>
	<tr>
		<td colspan="2"><input type="submit" value="Logout" /></td>
	</tr>
</table>
<!-- /NS logout -->
