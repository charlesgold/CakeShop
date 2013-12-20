<?php
echo $installBlurb;

echo $this->Form->create('MyApp');
?>

<input type="text" name="shopName" value=""/>
<input type="submit" name="shopNameSubmit" value="Install"/>

<?php echo $this->Form->end(); ?>
