<?php
$has_accr='';
function acf_render($acf,$value){
	global $has_accr;
	$label = $acf['label'];
	
	
	

	
	if (array_key_exists('sub_fields',$acf)) {
		echo "<h4> $label </h4><div class='row'> ";
		foreach ($acf['sub_fields'] as $child_acf) {
			// var_dump($child_acf);
			echo "<div class='col'>";
			acf_render($child_acf,$value[$child_acf['name']]);
			echo "</div>";
		}
		echo "</div>";
		return;
	}
	if ($acf['type']==='accordion'){
		if ($has_accr!==""){
			echo "</div></div>";
		}
		$has_accr="1";
		echo "<div class='card'><div class='card-header h5 text-white bg-secondary'>$label</div><div class='card-body' >";
	}else if ($acf['type']==='wysiwyg'){
		echo "<div class='card'><h5 class='card-header'>$label:</h5><div class='card-body'>";
		if ($value =="") echo " --- ";
		else echo "<div style=''>$value</div>";
		echo "</div></div>";

	}else{
		
		echo "<label >$label:</label> $value</p>";
	}
}
?>