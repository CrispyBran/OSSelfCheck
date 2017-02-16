<!-- keypad icon -->

<div id="keypad_icon">
	<table width="100%" cellpadding="3" title="selfcheck_button">
	    <tr>
	    	<td onclick="show_keypad()" title="selfcheck_button">
	    		<p>touch here</p>
	    		<p>for keypad</p>
	    	</td>
	    	<td onclick="show_keypad()" style="width:5px;" title="selfcheck_button"><img src="images/keypad_icon.gif"/></td>
	    </tr>
	</table>
</div>

<!-- end keypad icon -->

<!-- keypad -->

<div id="keypad_container">
	<table class="keypad">
		<tr>
			<td colspan="3" class="keypad_screen">
				<form>
					<input type="password" name="barcode" class="keypad_display"><br>
				</form>
			</td>
		</tr>
		<tr>
			<td><div data-val="1" title="selfcheck_button">1</div></td>
			<td><div data-val="2" title="selfcheck_button">2</div></td>
			<td><div data-val="3" title="selfcheck_button">3</div></td>
		</tr>
		<tr>
			<td><div data-val="4" title="selfcheck_button">4</div></td>
			<td><div data-val="5" title="selfcheck_button">5</div></td>
			<td><div data-val="6" title="selfcheck_button">6</div></td>
		</tr>
		<tr>
			<td><div data-val="7" title="selfcheck_button">7</div></td>
			<td><div data-val="8" title="selfcheck_button">8</div></td>
			<td><div data-val="9" title="selfcheck_button">9</div></td>
		</tr>
		<tr>
			<td onclick="delete_keypad_entry();"><div title="selfcheck_button"><span>delete</span></div></td>
			<td><div data-val="0" title="delete_button">0</div></td>
			<td colspan="2"><div title="cancel_button"><span onclick="tb_remove();">cancel</span></div></td>
		</tr>
	</table>
	<div onclick="$('#barcode').val($('#prompt .keypad_display').val());$('#form').submit();" class="ok_button button" title="Enter patron barcode." style="width:100%;">
		<h1>OK</h1>
	</div>
</div>

<!-- end keypad -->

<script type="text/javascript">
//keypad functions
function show_keypad(){
	tb_remove();
	tb_show($('#keypad_container').html());
	var keypad_key=$('#prompt .keypad div');
	keypad_key.click(function (){
		if (typeof $(this).data('val')!= 'undefined'){
			var keypad_display=$('#prompt .keypad_display');
			if (keypad_display.val().length<19){
				keypad_display.val(keypad_display.val() + $(this).data('val'));
			}
		}
	});
}

function delete_keypad_entry(){
	var keypad_display=$('#prompt .keypad_display').val();
	var keypad_length=keypad_display.length-1;
	keypad_newdisplay=keypad_display.substr(0,keypad_length);
	$('#prompt .keypad_display').val(keypad_newdisplay);
}
</script>