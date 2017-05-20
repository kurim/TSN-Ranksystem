<?PHP
session_start();

require_once('../other/config.php');

function getclientip() {
	if (!empty($_SERVER['HTTP_CLIENT_IP']))
		return $_SERVER['HTTP_CLIENT_IP'];
	elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
	elseif(!empty($_SERVER['HTTP_X_FORWARDED']))
		return $_SERVER['HTTP_X_FORWARDED'];
	elseif(!empty($_SERVER['HTTP_FORWARDED_FOR']))
		return $_SERVER['HTTP_FORWARDED_FOR'];
	elseif(!empty($_SERVER['HTTP_FORWARDED']))
		return $_SERVER['HTTP_FORWARDED'];
	elseif(!empty($_SERVER['REMOTE_ADDR']))
		return $_SERVER['REMOTE_ADDR'];
	else
		return false;
}

if (isset($_POST['logout'])) {
    $_SESSION = array();
    session_destroy();
	if($_SERVER['HTTPS'] == "on") {
		header("Location: https://".$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\'));
	} else {
		header("Location: http://".$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\'));
	}
	exit;
}

if (!isset($_SESSION['username']) || $_SESSION['username'] != $webuser || $_SESSION['password'] != $webpass || $_SESSION['clientip'] != getclientip()) {
	if($_SERVER['HTTPS'] == "on") {
		header("Location: https://".$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\'));
	} else {
		header("Location: http://".$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\'));
	}
	exit;
}

require_once('nav.php');

if (isset($_POST['update']) && $_SESSION['username'] == $webuser && $_SESSION['password'] == $webpass && $_SESSION['clientip'] == getclientip()) {
	$analyticsid    = $_POST['analyticsid'];
	$pagename		= $_POST['pagename'];
	if (isset($_POST['iconcheck'])) $iconcheck = 1; else $iconcheck = 0;
	if (isset($_POST['analyticscheck'])) $analyticscheck = 1; else $analyticscheck = 0;
	if ($mysqlcon->exec("UPDATE $dbname.config set iconcheck='$iconcheck',analyticscheck='$analyticscheck',analyticsid='$analyticsid',pagename='$pagename'") === false) {
        $err_msg = print_r($mysqlcon->errorInfo(), true);
		$err_lvl = 3;
    } else {
        $err_msg = $lang['wisvsuc'];
		$err_lvl = NULL;
    }
	$logpath				= $_POST['logpath'];
	$config[0]['uniqueid']	= $_POST['uniqueid'];
}
?>
		<div id="page-wrapper">
<?PHP if(isset($err_msg)) error_handling($err_msg, $err_lvl); ?>
			<div class="container-fluid">
				<div class="row">
					<div class="col-lg-12">
						<h1 class="page-header">
							<?php echo $lang['kurim0001']; ?>
						</h1>
					</div>
				</div>
				<form class="form-horizontal" data-toggle="validator" name="update" method="POST">
					<div class="row">
						<div class="col-md-6">
							<div class="panel panel-default">
								<div class="panel-body">
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#wipagedesc"><?php echo $lang['kurim0002']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8 required-field-block">
											<input type="text" class="form-control" name="pagename" value="<?php echo $pagename; ?>" >
											<div class="help-block with-errors"></div>
											<div class="required-icon"><div class="text">*</div></div>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#wiservericondesc"><?php echo $lang['kurim0003']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8">
											<?PHP if ($servericon == 1) {
												echo '<input class="switch-animate" type="checkbox" checked data-size="mini" name="iconcheck" value="',$servericon,'">';
											} else {
												echo '<input class="switch-animate" type="checkbox" data-size="mini" name="iconcheck" value="',$servericon,'">';
											} ?>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#wianalyticsdesc"><?php echo $lang['kurim0004']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8">
											<?PHP if ($analytics == 1) {
												echo '<input class="switch-animate" type="checkbox" checked data-size="mini" name="analyticscheck" value="',$analytics,'">';
											} else {
												echo '<input class="switch-animate" type="checkbox" data-size="mini" name="analyticscheck" value="',$analytics,'">';
											} ?>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#wianalyticsiddesc"><?php echo $lang['kurim0005']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8">
											<textarea class="form-control" data-pattern="^(UA-[0-9]+-[0-9]+)$" data-error="Check all unique IDs are correct and your list do not ends with a comma!" rows="1" name="analyticsid" maxlength="500"><?php echo $config[0]['analyticsid']; ?></textarea>
											<div class="help-block with-errors"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">&nbsp;</div>
					<div class="row">
						<div class="text-center">
							<button type="submit" name="update" class="btn btn-primary"><?php echo $lang['wisvconf']; ?></button>
						</div>
					</div>
					<div class="row">&nbsp;</div>
				</form>
			</div>
		</div>
	</div>

<div class="modal fade" id="wipagedesc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $lang['kurim0002']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['kurim0002desc']; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="wiservericondesc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $lang['kurim0003']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['kurim0003desc']; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="wianalyticsdesc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $lang['kurim0004']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['kurim0004desc']; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="wianalyticsiddesc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $lang['kurim0005']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['kurim0005desc']; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
      </div>
    </div>
  </div>
</div>
<script>
$('form[data-toggle="validator"]').validator({
	custom: {
		pattern: function ($el) {
			var pattern = new RegExp($el.data('pattern'));
			return pattern.test($el.val());
		}
	},
	delay: 100,
	errors: {
		pattern: "There should be an error in your value, please check all could be right!"
	}
});
</script>
</body>
</html>