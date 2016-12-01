<?php if ($secure) { ?>
  You were protected from XSS with proper escaping.
  <p><?= $this->escaper->escapeHtml($post) ?></p>
<?php } else { ?>
  You were just hacked with XSS!
  <p><?= $post ?></p>
<?php } ?>
