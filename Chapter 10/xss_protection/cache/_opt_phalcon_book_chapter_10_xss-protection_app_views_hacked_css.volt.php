<?php if ($secure) { ?>
  <p style="<?= $this->escaper->escapeCss($style) ?>">The world is a big place</p>
<?php } else { ?>
  <p style="<?= $style ?>">The world is a big place</p>
<?php } ?>
