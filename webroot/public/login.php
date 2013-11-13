<?php
require_once '../../define_root.php';
require_once INCLUDE_ROOT."Model/Site.php";

if($user->loggedIn()) {
	redirect_to("index");
}

if(!empty($_POST)) {
	if( 
		$user->login($_POST['email'],$_POST['password'])
	)
	{
		alert('<strong>Success!</strong> You were logged in!', 'alert-success');
		redirect_to('index');
	}
	else {
		alert(implode($user->errors),'alert-danger');
	}
}

require_once INCLUDE_ROOT . "View/header.php";
require_once INCLUDE_ROOT . "View/public_nav.php";


require_once INCLUDE_ROOT . "View/header.php";
require_once INCLUDE_ROOT . "View/public_nav.php";
?>
<div class="col-md-3 col-md-offset-1 well">
<h2>Login</h2>
<form class="" id="login" name="login" method="post" action="<?=WEBROOT?>public/login">
	<div class="form-group small-left">
		<label class="control-label sr-only" for="email">
			<?php echo _("Email"); ?>
		</label>
		<div class="controls">
			<div class="input-group">
			  <span class="input-group-addon"><i class="fa fa-envelope-o fa-fw"></i></span>
			  <input class="form-control"  required type="email" placeholder="Your email address" name="email" id="email">
			</div>
		</div>
	</div>
	<div class="form-group small-left">
		<label class="control-label sr-only" for="password">
			<?php echo _("Password"); ?>
		</label>
		<div class="controls">
			<div class="input-group">
			  <span class="input-group-addon"><i class="fa fa-key fa-fw"></i></span>
	  		  <input class="form-control" required type="password" placeholder="Your password" name="password" id="password">
			</div>
		</div>
	</div>
	<div class="form-group small-left">
		<div class="controls">
			<input type="submit" value="<?php echo _("login"); ?>"  class="btn btn-default btn-info">
		</div>
	</div>
</form>
</div>
<?php
require_once INCLUDE_ROOT . "View/footer.php";
