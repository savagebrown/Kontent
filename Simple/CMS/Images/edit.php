<?php
    require_once 'Includes/Configuration.php';

    $image_id = $_GET['image_id'];
	$image_table = $_GET['image_table'];
	$owner_id_field = $_GET['owner_id_field'];
	$owner_page = $_GET['owner_page'];
	$image_owner_title = $_GET['image_owner_title'];
	$owner_param = $_GET['owner_param'];
	$image_path = $_GET['path'];
	$display_image_placement = $_GET['inline'];
	$markup_text = $_GET['markup_text'];

    $sql_img = <<<SQL

SELECT id, $owner_id_field, placement, caption 
FROM $image_table 
WHERE id = $image_id

SQL;
    $q_img = $db->query($sql_img);
    if (DB::iserror($q_img)) { sb_error($q_img); }
    
    $r_img = $q_img->fetchrow(DB_FETCHMODE_ASSOC);

    $image_fullpath = $image_path.'/ss/'.$r_img[$owner_id_field].'_'.$r_img['id'].'.jpg';
    list($width, $height, $type, $attr) = getimagesize($image_fullpath);

	$image_placement_1 = ($r_img['placement']==1)?' selected="selected"':'';
	$image_placement_2 = ($r_img['placement']==2)?' selected="selected"':'';
	$image_placement_3 = ($r_img['placement']==3)?' selected="selected"':'';

    echo <<<HTML

<h1 id="image-edit-popup-title">Editing image for <strong>$image_owner_title</strong></h1>
<div id="image-edit-popup-container">
    
<table id="image-edit-popup">
	<tr>
	<td>
	    <form action="$owner_page?$owner_param={$r_img[$owner_id_field]}" method="post" name="image_frm" id="image_frm">

		    <p><lable style="margin-bottom:5px;" for="image_caption">Image Caption:</lable></p>
		    <p><textarea name="image_caption">{$r_img['caption']}</textarea></p>

HTML;

	if ($display_image_placement==1) {
echo <<<HTML

			<p>
				<lablefor="image_placement">Image Alignment:</lable>
				<select name="image_placement">
					<option$image_placement_1 value="1">Left align</option>
					<option$image_placement_2 value="2">Right align</option>
					<option$image_placement_3 value="3">Span text</option>
				</select>

				<img src="{$g['page']['images']}/icon_img_left.gif" width="10" height="9" alt="place image left" />
				<img src="{$g['page']['images']}/icon_img_right.gif" width="10" height="9" alt="place image right" />
				<img src="{$g['page']['images']}/icon_img_span.gif" width="10" height="9" alt="span image" />
			</p>

HTML;
}

echo <<<HTML

		    <p><input value="Add" name="btnUpdateCaption" type="image" src="{$g['page']['buttons']}/btn-savechanges.gif" /></p>
		    
			<input type="hidden" name="image_id" value="{$r_img['id']}" />
			<input type="hidden" name="image_table" value="$image_table" />
			<input type="hidden" name="image_path" value="$image_path" />
			<input type="hidden" name="image_inline" value="$display_image_placement" />
	
			<input type="hidden" name="owner_id_field" value="$owner_id_field" />
			<input type="hidden" name="owner_page" value="$owner_page" />
			<input type="hidden" name="owner_param" value="$owner_param" />
			<input type="hidden" name="owner_id" value="{$r_img[$owner_id_field]}" />
	
		    <input type="hidden" name="imageeditsubmitted" value="true" />

	    </form>
	</td>
	<td class="preview">
		<img src="$image_fullpath" width="100" height="100" />
		<p><a class="attention" href="javascript:;" onclick="cf=confirm('Are you sure you want to delete this image?');if (cf)window.location='?imgdeleteid={$r_img['id']}'; return false;">delete this image</a></p>

	</td>
	</tr>
	</table>

	<div style="clear:both;"></div>
	<div class="markup-instructions">
		highlight and copy <strong class="highlight">markup</strong> to place this image in your text
	</div>
	<div class="markup-copypaste">
		$markup_text
	</div>

</div>
HTML;


?>