<?php 
$uniq_id=uniqid();
?>
<div id="prompt_container_<?php echo $uniq_id;?>" class="prompt_container">
	<!-- buttons -->
	<h1 style="font-style:italic;white-space:nowrap"><?php echo $popupmsg;?></h1>
	<table style="width:50%" cellpadding="10">
		<tr>
			<td>
				<div class="ok_button button" onclick="tb_remove();" title="selfcheck_button">
					<h1>OK</h1>
				</div>
			</td>
		</tr>
	</table>
</div>
<script type="text/javascript">
$(document).ready(function(){
	$("#item_list .loading").hide();
	tb_remove(); //hide any existing notices
	tb_show($('#prompt_container_<?php echo $uniq_id;?>').html());
});
</script>