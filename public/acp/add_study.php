<?php
/* require_once $_SERVER['DOCUMENT_ROOT']."/tmp/config/config.php"; */
require_once "../config/config.php";
global $currentUser;
if(!userIsAdmin()) {
  header("Location: index.php");
  die();
}
?>
<?php
global $language,$available_languages,$lang;
if(!empty($_POST)) {
  $errors=array();
  $study=new Study;
  $study->Constructor($_POST['prefix'],$currentUser->id);
  if(!$study->status) {
    $errors=$study->GetErrors();
  } else {
    if(!$study->Register())
      $errors=$study->GetErrors();
    else {
      if(!$study->CreateDB())
        $errors=$study->GetErrors();
      else
        header("Location: view_study.php?id=".$study->id."");
    }
  }
}
?>
<?php
include("pre_content.php");
?>	
<?php
if(!empty($_POST) and count($errors)>0) {
?>
<div id="errors">
<?php errorOutput($errors); ?>
</div>
<?php
    }
?>
<form id="add_study" name="add_study" method="post" action="add_study.php">
  <p>
  <p>
  <label>Datenbank Prefix
  </label>
  <input type="text" name="prefix" id="prefix"  value="<?php if(isset($_POST['prefix'])) echo $_POST['prefix']; ?>"/>
  </p>
  <button type="submit">Studie anlegen</button>
  </form>

<?php
include("post_content.php");
?>	