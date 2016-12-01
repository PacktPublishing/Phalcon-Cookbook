
<h3>CSRF example</h3>

<div>
  <form method="post">
    <input type="text" name="email" placeholder="email"   value=""/>
    <input type="hidden" name="{{ security.getTokenKey() }}" value="{{ security.getToken() }}"/>
    <input type="submit" formaction="{{ url('signin') }}" value="Sign In"/>
  </form>
</div>

<div>
  {{content()}}
</div>
