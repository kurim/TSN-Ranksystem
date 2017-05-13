﻿<?PHP
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
	$timezone 		= $_POST['timezone'];
	$dateformat 	= $_POST['dateformat'];
	$logpath		= addslashes($_POST['logpath']);
	$language  		= $_POST['languagedb'];
	if (isset($_POST['upcheck'])) $upcheck = 1; else $upcheck = 0;
	$updateinfotime = $_POST['updateinfotime'];
	$uniqueid       = $_POST['uniqueid'];
	$adminuuid     	= $_POST['adminuuid'];
	$analyticsid    = $_POST['analyticsid'];
	$pagename		= $_POST['pagename'];
	if (isset($_POST['iconcheck'])) $iconcheck = 1; else $iconcheck = 0;
	if (isset($_POST['analyticscheck'])) $analyticscheck = 1; else $analyticscheck = 0;
	if ($mysqlcon->exec("UPDATE $dbname.config set timezone='$timezone',dateformat='$dateformat',logpath='$logpath',language='$language',upcheck='$upcheck',updateinfotime='$updateinfotime',uniqueid='$uniqueid',adminuuid='$adminuuid',iconcheck='$iconcheck',analyticscheck='$analyticscheck',analyticsid='$analyticsid',pagename='$pagename'") === false) {
        $err_msg = print_r($mysqlcon->errorInfo(), true);
		$err_lvl = 3;
    } else {
        $err_msg = $lang['wisvsuc']." ".$lang['wisvres'];
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
							<?php echo $lang['wihlvs']; ?>
						</h1>
					</div>
				</div>
				<form class="form-horizontal" data-toggle="validator" name="update" method="POST">
					<div class="row">
						<div class="col-md-6">
							<div class="panel panel-default">
								<div class="panel-body">
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#wipagedesc"><?php echo $lang['wipage']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8 required-field-block">
											<input type="text" class="form-control" name="pagename" value="<?php echo $pagename; ?>" >
											<div class="help-block with-errors"></div>
											<div class="required-icon"><div class="text">*</div></div>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#witimedesc"><?php echo $lang['witime']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8">
											<select class="selectpicker show-tick form-control" data-live-search="true" name="timezone">
											<?PHP
											$timezonearr = DateTimeZone::listIdentifiers();
											foreach ($timezonearr as $timez) {
												if ($timez == $timezone) {
													echo '<option value="'.$timezone,'" selected=selected>',$timezone,'</option>';
												} else {
													echo '<option value="',$timez,'">',$timez,'</option>';
												}
											}
											?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#widaformdesc"><?php echo $lang['widaform']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8">
											<input type="text" class="form-control" name="dateformat" value="<?php echo $timeformat; ?>">
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#wilogdesc"><?php echo $lang['wilog']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8 required-field-block">
											<input type="text" class="form-control" data-pattern=".*(\/|\\)$" data-error="The Logpath must end with / or \" name="logpath" value="<?php echo $logpath; ?>" required>
											<div class="help-block with-errors"></div>
											<div class="required-icon"><div class="text">*</div></div>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#wivlangdesc"><?php echo $lang['wivlang']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8">
											<select class="selectpicker show-tick form-control" name="languagedb">
											<?PHP
											echo '<option data-subtext="العربية" value="ar"'.($language === 'ar' ? ' selected="selected"' : '').'>AR</option>';
											echo '<option data-subtext="Deutsch" value="de"'.($language === 'de' ? ' selected="selected"' : '').'>DE</option>';
											echo '<option data-subtext="english" value="en"'.($language === 'en' ? ' selected="selected"' : '').'>EN</option>';
											echo '<option data-subtext="français" value="fr"'.($language === 'fr' ? ' selected="selected"' : '').'>FR</option>';
											echo '<option data-subtext="italiano" value="it"'.($language === 'it' ? ' selected="selected"' : '').'>IT</option>';
											echo '<option data-subtext="Nederlands" value="nl"'.($language === 'nl' ? ' selected="selected"' : '').'>NL</option>';
											echo '<option data-subtext="românesc" value="ro"'.($language === 'ro' ? ' selected="selected"' : '').'>RO</option>';
											echo '<option data-subtext="русский" value="ru"'.($language === 'ru' ? ' selected="selected"' : '').'>RU</option>';
											?>
											</select>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="panel panel-default">
								<div class="panel-body">
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#wiadmuuiddesc"><?php echo $lang['wiadmuuid']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8 required-field-block">
											<input type="text" class="form-control" data-pattern="^([A-Za-z0-9\\\/\+]{27}=)$" data-error="Check the entered unique ID!" name="adminuuid" value="<?php echo $adminuuid; ?>" required>
											<div class="help-block with-errors"></div>
											<div class="required-icon"><div class="text">*</div></div>
										</div>
									</div>
								</div>
							</div>
							<div class="panel panel-default">
								<div class="panel-body">
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#wiupcheckdesc"><?php echo $lang['wiupcheck']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8">
											<?PHP if ($update == 1) {
												echo '<input class="switch-animate" type="checkbox" checked data-size="mini" name="upcheck" value="',$update,'">';
											} else {
												echo '<input class="switch-animate" type="checkbox" data-size="mini" name="upcheck" value="',$update,'">';
											} ?>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#wiuptimedesc"><?php echo $lang['wiuptime']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8">
											<input type="text" class="form-control" name="updateinfotime" value="<?php echo $updateinfotime; ?>">
											<script>
											$("input[name='updateinfotime']").TouchSpin({
												min: 0,
												max: 86400,
												verticalbuttons: true,
												prefix: 'Sec.:'
											});
											</script>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#wiupuiddesc"><?php echo $lang['wiupuid']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8">
											<textarea class="form-control" data-pattern="^([A-Za-z0-9\\\/\+]{27}=,)*([A-Za-z0-9\\\/\+]{27}=)$" data-error="Check all unique IDs are correct and your list do not ends with a comma!" rows="1" name="uniqueid" maxlength="500"><?php echo $config[0]['uniqueid']; ?></textarea>
											<div class="help-block with-errors"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">&nbsp;</div>
					<div class="row">
						<div class="col-md-6">
							<div class="panel panel-default">
								<div class="panel-body">
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#wiservericondesc"><?php echo $lang['wiservericon']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8">
											<?PHP if ($servericon == 1) {
												echo '<input class="switch-animate" type="checkbox" checked data-size="mini" name="iconcheck" value="',$servericon,'">';
											} else {
												echo '<input class="switch-animate" type="checkbox" data-size="mini" name="iconcheck" value="',$servericon,'">';
											} ?>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#wianalyticsdesc"><?php echo $lang['wianalytics']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8">
											<?PHP if ($analytics == 1) {
												echo '<input class="switch-animate" type="checkbox" checked data-size="mini" name="analyticscheck" value="',$analytics,'">';
											} else {
												echo '<input class="switch-animate" type="checkbox" data-size="mini" name="analyticscheck" value="',$analytics,'">';
											} ?>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#wianalyticsiddesc"><?php echo $lang['wianalyticsid']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
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

<div class="modal fade" id="witimedesc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $lang['witime']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['witimedesc']; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="widaformdesc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $lang['widaform']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['widaformdesc']; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="wilogdesc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $lang['wilog']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['wilogdesc']; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="wivlangdesc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $lang['wivlang']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['wivlangdesc']; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="wiupcheckdesc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $lang['wiupcheck']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['wiupcheckdesc']; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="wiuptimedesc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $lang['wiuptime']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['wiuptimedesc']; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="wiupuiddesc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $lang['wiupuid']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['wiupuiddesc']; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="wiadmuuiddesc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $lang['wiadmuuid']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['wiadmuuiddesc']; ?>
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
        <h4 class="modal-title"><?php echo $lang['wiservericon']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['wiservericondesc']; ?>
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
        <h4 class="modal-title"><?php echo $lang['wianalytics']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['wianalyticsdesc']; ?>
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
        <h4 class="modal-title"><?php echo $lang['wianalyticsid']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['wianalyticsiddesc']; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="wipagedesc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $lang['wipage']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['wipagedesc']; ?>
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