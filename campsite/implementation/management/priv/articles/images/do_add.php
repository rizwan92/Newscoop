<?php  

require_once($_SERVER['DOCUMENT_ROOT'].'/classes/common.php');
load_common_include_files("article_images");
require_once($_SERVER['DOCUMENT_ROOT'].'/classes/Article.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/classes/Image.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/classes/Issue.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/classes/Section.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/classes/Language.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/classes/Publication.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/classes/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/classes/Input.php');
require_once($_SERVER['DOCUMENT_ROOT']."/$ADMIN_DIR/camp_html.php");

list($access, $User) = check_basic_access($_REQUEST);
if (!$access) {
	header("Location: /$ADMIN/logout.php");
	exit;
}
if (!$User->hasPermission('AddImage')) {
	camp_html_display_error(getGS('You do not have the right to add images' ));
	exit;
}

$f_publication_id = Input::Get('f_publication_id', 'int', 0);
$f_issue_number = Input::Get('f_issue_number', 'int', 0);
$f_section_number = Input::Get('f_section_number', 'int', 0);
$f_language_id = Input::Get('f_language_id', 'int', 0);
$f_language_selected = Input::Get('f_language_selected', 'int', 0);
$f_article_number = Input::Get('f_article_number', 'int', 0);
$f_image_template_id = Input::Get('f_image_template_id', 'int', 0);
$f_image_description = Input::Get('f_image_description');
$f_image_photographer = Input::Get('f_image_photographer');
$f_image_place = Input::Get('f_image_place');
$f_image_date = Input::Get('f_image_date');
$f_image_url = Input::Get('f_image_url', 'string', '', true);
$BackLink = Input::Get('BackLink', 'string', null, true);

if (!Input::IsValid()) {
	camp_html_display_error(getGS('Invalid input: $1', Input::GetErrorString()));
	exit;			
}

$articleObj =& new Article($f_language_selected, $f_article_number);

// If the template ID is in use, dont add the image.
if (ArticleImage::TemplateIdInUse($f_article_number, $f_image_template_id)) {
	header('Location: '.camp_html_article_url($articleObj, $f_language_selected, 'images/popup.php'));
	exit;
}

$attributes = array();
$attributes['Description'] = $f_image_description;
$attributes['Photographer'] = $f_image_photographer;
$attributes['Place'] = $f_image_place;
$attributes['Date'] = $f_image_date;
if (!empty($f_image_url)) {
	$image = Image::OnAddRemoteImage($f_image_url, $attributes, $User->getUserId());
}
elseif (!empty($_FILES['f_image_file'])) {
	$image = Image::OnImageUpload($_FILES['f_image_file'], $attributes, $User->getUserId());
}
else {
	header('Location: '.camp_html_article_url($articleObj, $f_language_id, 'images/popup.php'));
	exit;
}

// Check if image was added successfully
if (!is_object($image)) {
	header('Location: '.camp_html_display_error($image, $BackLink));
	exit;	
}

ArticleImage::AddImageToArticle($image->getImageId(), $articleObj->getArticleNumber(), $f_image_template_id);

$logtext = getGS('The image $1 has been added.', $attributes['Description']);
Log::Message($logtext, $User->getUserName(), 41);

// Go back to article image list.
//$redirectLocation = camp_html_article_url($articleObj, $f_language_id, 'images/edit.php')
//	   ."&ImageId=".$image->getImageId()."&ImageTemplateId=$ImageTemplateId";
////echo $redirectLocation;
//header("Location: $redirectLocation");
//exit;
?>
<script>
window.opener.location.reload();
window.close();
</script>
