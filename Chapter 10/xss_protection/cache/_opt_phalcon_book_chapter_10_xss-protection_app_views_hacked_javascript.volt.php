
<?php if ($secure) { ?>
  You were protected from XSS with proper escaping.
  <script>
    window.title = '<?= $this->escaper->escapeJs($title) ?>'
  </script>
<?php } else { ?>
  You were just hacked with XSS!
  <script>
    window.title = '<?= $title ?>'
  </script>
<?php } ?>
