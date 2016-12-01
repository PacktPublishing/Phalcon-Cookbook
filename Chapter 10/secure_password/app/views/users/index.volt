<h3>Create</h3>
<form method="post">
  <input type="text" name="username" placeholder="username"/>
  <input type="password" name="password" = placeholder="password"/>
  <input type="submit" formaction="{{ url('users/create') }}" value="Create User"/>
</form>

<h3>Signin</h3>
<form method="post">
  <input type="text" name="username" placeholder="username"/>
  <input type="password" name="password" = placeholder="password"/>
  <input type="submit" formaction="{{ url('users/signin') }}" value="Sign User"/>
</form>

<h3>Change Password</h3>
<form method="post">
  <input type="text" name="username" placeholder="username"/>
  <input type="password" name="password" = placeholder="password"/>
  <input type="submit" formaction="{{ url('users/changePassword') }}" value="Change Password"/>
</form>
