
<h3>CSRF example</h3>

<div>
  <form method="post">
    <input type="text" name="email" placeholder="email"   value=""/>
    <input type="hidden" name="<?= $this->security->getTokenKey() ?>" value="<?= $this->security->getToken() ?>"/>
    <input type="submit" formaction="<?= $this->url->get('signin') ?>" value="Sign In"/>
  </form>
</div>

<div>
  <?= $this->getContent() ?>
</div>
