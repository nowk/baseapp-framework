<html> 
<head> 
<?php if ($debug <2 ) { ?>
	<META http-equiv="refresh" content="5;URL=<?php echo $location; ?>"> </head> 
<?php } ?>
<body> 

<center> You are in <strong>Debugging Mode Level <?php echo $debug;?></strong>

<?php if ($debug <2 ) { ?>
	You will be redirected to the new location automatically in 5 seconds. 
<?php } else {?>
    Automatic Redirection is disabled.
<?php } ?>
<br />Click Here to go to <a href="<?php echo $location; ?>"> <?php echo $location; ?></a> instantly.</center> 
</body> 
</html> 