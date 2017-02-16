<?php 
$uniq_id=uniqid();
?>
<div id="prompt_container_<?php echo $uniq_id;?>" class="prompt_container">
	<h1><?php 
		if ($response_message==$SIP_renewal_limit || $response_message==$SIP_recheck_limit){
			echo wordwrap($CUSTOM_renewal_limit, 40, "<br />");
		} else {
			echo wordwrap($CUSTOM_item_problems, 40, "<br />");
		}?>
	</h1>
	<div class="ok_button button" title="selfcheck_button">
		<h1 onclick="tb_remove()">OK</h1>
	</div>
</div>
<script type="text/javascript">
$(document).ready(function(){
	$("#item_list .loading").hide();
	tb_remove(); //hide any existing notices
	tb_show($('#prompt_container_<?php echo $uniq_id;?>').html());
	$.dbj_sound.play('<?php echo $error_sound;?>');
});
</script>