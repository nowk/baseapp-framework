<div class="<?=$type;?> message">

<?php if ($type == 'error') { ?>
<code style="display:none;"><!--ERROR--></code>
<?php } else if ($type == 'success') { ?>
<code style="display:none;"><!--SUCCESS--></code>
<?php } ?>

<?=$message;?>

</div>
