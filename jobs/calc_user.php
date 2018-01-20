<?PHP
function calc_user($ts3,$mysqlcon,$lang,$dbname,$slowmode,$timezone,$update,$grouptime,$boostarr,$resetbydbchange,$msgtouser,$uniqueid,$updateinfotime,$currvers,$substridle,$exceptuuid,$exceptgroup,$allclients,$logpath,$rankupmsg,$ignoreidle,$exceptcid,$ts,$resetexcept,$upchannel,$phpcommand) {
	$nowtime = time();

	if(($getversion = $mysqlcon->query("SELECT * FROM $dbname.job_check WHERE job_name='get_version'")) === false) {
		enter_logfile($logpath,$timezone,2,"calc_user -3:".print_r($mysqlcon->errorInfo(), true));
	} else {
		$getversion = $getversion->fetchAll();
		$updatetime = $nowtime - 43200;
		if ($getversion[0]['timestamp'] < $updatetime) {
			$newversion=get_data('https://ts-n.net/ranksystem/'.$upchannel,$currvers,$ts);
			if($mysqlcon->exec("UPDATE $dbname.job_check SET timestamp='$nowtime' WHERE job_name='get_version'") === false) {
				enter_logfile($logpath,$timezone,2,"calc_user -2:".print_r($mysqlcon->errorInfo(), true));
			}
			if($mysqlcon->exec("UPDATE $dbname.config SET newversion='$newversion'") === false) {
				enter_logfile($logpath,$timezone,2,"calc_user -1:".print_r($mysqlcon->errorInfo(), true));
			}
		}
	}
	
	$updatetime = $nowtime - $updateinfotime;
	if(($lastupdate = $mysqlcon->query("SELECT * FROM $dbname.job_check WHERE job_name='check_update'")) === false) {
		enter_logfile($logpath,$timezone,2,"calc_user 0:".print_r($mysqlcon->errorInfo(), true));
	}
	$lastupdate = $lastupdate->fetchAll();
	if ($lastupdate[0]['timestamp'] < $updatetime) {
		if(($getversion = $mysqlcon->query("SELECT newversion FROM $dbname.config")) === false) {
			enter_logfile($logpath,$timezone,2,"calc_user 1:".print_r($mysqlcon->errorInfo(), true));
		}
		$getversion = $getversion->fetch(PDO::FETCH_ASSOC);
		$newversion = $getversion['newversion'];
		if(version_compare($newversion, $currvers, '>') && $newversion != NULL) {
			if ($update == 1) {
				enter_logfile($logpath,$timezone,4,$lang['upinf']);
				foreach ($uniqueid as $clientid) {
					check_shutdown($timezone,$logpath); usleep($slowmode);
					try {
						$ts3->clientGetByUid($clientid)->message(sprintf($lang['upmsg'], $currvers, $newversion));
						enter_logfile($logpath,$timezone,4,"  ".sprintf($lang['upusrinf'], $clientid));
						if($mysqlcon->exec("UPDATE $dbname.job_check SET timestamp=$nowtime WHERE job_name='check_update'") === false) {
							enter_logfile($logpath,$timezone,2,"calc_user 3:".print_r($mysqlcon->errorInfo(), true));
						}
					}
					catch (Exception $e) {
						//enter_logfile($logpath,$timezone,4,"  ".sprintf($lang['upusrerr'], $clientid));
					}
				}
			}
			update_rs($mysqlcon,$lang,$dbname,$logpath,$timezone,$newversion,$phpcommand);
		}
	}


	if(($dbdata = $mysqlcon->query("SELECT * FROM $dbname.job_check WHERE job_name='calc_user_lastscan'")) === false) {
		enter_logfile($logpath,$timezone,2,"calc_user 4:".print_r($mysqlcon->errorInfo(), true));
		exit;
	}
	$lastscanarr = $dbdata->fetchAll();
	$lastscan = $lastscanarr[0]['timestamp'];
	if($lastscan < ($nowtime - 1800)) {
		enter_logfile($logpath,$timezone,4,"Much time gone since last scan.. reset time difference to zero.");
		$lastscan = $nowtime;
	} elseif($lastscan > $nowtime) {
		enter_logfile($logpath,$timezone,4,"Negative time between now and last scan (Error in your server time!).. reset time difference to zero.");
		$lastscan = $nowtime;
	}
	$uidarr = array();
	if ($dbdata->rowCount() != NULL) {
		if($mysqlcon->exec("UPDATE $dbname.job_check SET timestamp='$nowtime' WHERE job_name='calc_user_lastscan'") === false) {
			enter_logfile($logpath,$timezone,2,"calc_user 5:".print_r($mysqlcon->errorInfo(), true));
		}
		if(($dbuserdata = $mysqlcon->query("SELECT uuid,cldbid,count,grpid,nextup,idle,boosttime,grpsince,except FROM $dbname.user")) === false) {
			enter_logfile($logpath,$timezone,2,"calc_user 6:".print_r($mysqlcon->errorInfo(), true));
		}
		$uuids = $dbuserdata->fetchAll(PDO::FETCH_ASSOC);
		foreach($uuids as $uuid) {
			$sqlhis[$uuid['uuid']] = array(
				"uuid" => $uuid['uuid'],
				"cldbid" => $uuid['cldbid'],
				"count" => $uuid['count'],
				"grpid" => $uuid['grpid'],
				"nextup" => $uuid['nextup'],
				"idle" => $uuid['idle'],
				"boosttime" => $uuid['boosttime'],
				"grpsince" => $uuid['grpsince'],
				"except" => $uuid['except']
			);
			$uidarr[] = $uuid['uuid'];
		}
	}
	unset($uuids);

	check_shutdown($timezone,$logpath); usleep($slowmode);
	$yetonline = array();
	$insertdata  = array();
	$updatedata  = array();
	if(empty($grouptime)) {
		enter_logfile($logpath,$timezone,2,"calc_user 7:".$lang['wiconferr']);
		exit;
	}
	krsort($grouptime);
	$sumentries = 0;
	$nextupforinsert = key($grouptime) - 1;
	
	if(!isset($dbgroups)) {
		if(($dbgroups = $mysqlcon->query("SELECT sgid,sgidname FROM $dbname.groups")) === false) {
			enter_logfile($logpath,$timezone,2,"calc_user 8.1:".print_r($mysqlcon->errorInfo(), true));
		} else {
			$dbgroups = $dbgroups->fetchAll(PDO::FETCH_ASSOC);
		}
	}
	
	print_r($dbgroups);

	foreach ($allclients as $client) {
		$sumentries++;
		$cldbid = $client['client_database_id'];
		$name = $mysqlcon->quote($client['client_nickname'], ENT_QUOTES);
		$uid = htmlspecialchars($client['client_unique_identifier'], ENT_QUOTES);
		$cldgroup = $client['client_servergroups'];
		$sgroups = explode(",", $cldgroup);
		$platform = $client['client_platform'];
		$nation = $client['client_country'];
		$version = $client['client_version'];
		$firstconnect = $client['client_created'];
		$channel = $client['cid'];
		if (!in_array($uid, $yetonline) && $client['client_version'] != "ServerQuery") {
			if(strstr($client['connection_client_ip'], '[')) {
				$ip = $mysqlcon->quote(inet_pton(str_replace(array('[',']'),'',$client['connection_client_ip'])), ENT_QUOTES);
			} else {
				$ip = $mysqlcon->quote(inet_pton($client['connection_client_ip']), ENT_QUOTES);
			}
			$clientidle  = floor($client['client_idle_time'] / 1000);
			if(isset($ignoreidle) && $clientidle < $ignoreidle) {
				$clientidle = 0;
			}
			$yetonline[] = $uid;
			if(in_array($uid, $exceptuuid)) {
				$except = 3;
			} elseif(array_intersect($sgroups, $exceptgroup)) {
				$except = 2;
			} else {
				if(isset($sqlhis[$uid]['except']) && ($sqlhis[$uid]['except'] == 3 || $sqlhis[$uid]['except'] == 2) && $resetexcept == 2) { 
				 	$sqlhis[$uid]['count'] = 0;
				 	$sqlhis[$uid]['idle'] = 0;
					enter_logfile($logpath,$timezone,5,sprintf($lang['resettime'], $name, $uid, $cldbid));
					if($mysqlcon->exec("DELETE FROM $dbname.user_snapshot WHERE uuid='$uid'") === false) {
						enter_logfile($logpath,$timezone,2,"calc_user 5:".print_r($mysqlcon->errorInfo(), true));
					}
				}
				$except = 0;
			}
			if (in_array($uid, $uidarr)) {
				$idle   = $sqlhis[$uid]['idle'] + $clientidle;
				$grpid  = $sqlhis[$uid]['grpid'];
				$nextup = $sqlhis[$uid]['nextup'];
				$grpsince = $sqlhis[$uid]['grpsince'];
				if ($sqlhis[$uid]['cldbid'] != $cldbid && $resetbydbchange == 1) {
					enter_logfile($logpath,$timezone,5,sprintf($lang['changedbid'], $name, $uid, $cldbid, $sqlhis[$uid]['cldbid']));
						$count = 1;
						$idle  = 0;
				} else {
					$hitboost = 0;
					$boosttime = $sqlhis[$uid]['boosttime'];
					if($boostarr!=0) {
						foreach($boostarr as $boost) {
							if(in_array($boost['group'], $sgroups)) {
								$hitboost = 1;
								if($sqlhis[$uid]['boosttime']==0) {
									$boosttime = $nowtime;
								} else {
									if ($nowtime > $sqlhis[$uid]['boosttime'] + $boost['time']) {
										check_shutdown($timezone,$logpath); usleep($slowmode);
										try {
											$ts3->serverGroupClientDel($boost['group'], $cldbid);
											$boosttime = 0;
											enter_logfile($logpath,$timezone,5,sprintf($lang['sgrprm'], $dbgroups[$sqlhis[$uid]['grpid']]['sgidname'], $sqlhis[$uid]['grpid'], $name, $uid, $cldbid));
										}
										catch (Exception $e) {
											enter_logfile($logpath,$timezone,2,"calc_user 8:".sprintf($lang['sgrprerr'], $name, $uid, $cldbid));
										}
									}
								}
								$count = ($nowtime - $lastscan) * $boost['factor'] + $sqlhis[$uid]['count'];
								if ($clientidle > ($nowtime - $lastscan)) {
									$idle = ($nowtime - $lastscan) * $boost['factor'] + $sqlhis[$uid]['idle'];
								}
							}
						}
					}
					if($boostarr == 0 or $hitboost == 0) {
						$count = $nowtime - $lastscan + $sqlhis[$uid]['count'];
						if ($clientidle > ($nowtime - $lastscan)) {
							$idle = $nowtime - $lastscan + $sqlhis[$uid]['idle'];
						}
					}
				}
				$dtF = new DateTime("@0");
				if ($substridle == 1) {
					$activetime = $count - $idle;
				} else {
					$activetime = $count;
				}
				$dtT = new DateTime("@$activetime");
				foreach ($grouptime as $time => $groupid) {
					if (in_array($groupid, $sgroups)) {
						$grpid = $groupid;
						break;
					}
				}
				$grpcount=0;
				foreach ($grouptime as $time => $groupid) {
					$grpcount++;
					if(in_array($channel, $exceptcid) || (($sqlhis[$uid]['except'] == 3 || $sqlhis[$uid]['except'] == 2) && $resetexcept == 1)) {
						$count = $sqlhis[$uid]['count'];
						$idle = $sqlhis[$uid]['idle'];
						if($except != 2 && $except != 3) {
							$except = 1;
						}
					} elseif ($activetime > $time && !in_array($uid, $exceptuuid) && !array_intersect($sgroups, $exceptgroup)) {
						if ($sqlhis[$uid]['grpid'] != $groupid) {
							if ($sqlhis[$uid]['grpid'] != NULL && in_array($sqlhis[$uid]['grpid'], $sgroups)) {
								check_shutdown($timezone,$logpath); usleep($slowmode);
								try {
									$ts3->serverGroupClientDel($sqlhis[$uid]['grpid'], $cldbid);
									enter_logfile($logpath,$timezone,5,sprintf($lang['sgrprm'], $dbgroups[$sqlhis[$uid]['grpid']]['sgidname'], $sqlhis[$uid]['grpid'], $name, $uid, $cldbid));
								}
								catch (Exception $e) {
									enter_logfile($logpath,$timezone,2,"calc_user 9:".sprintf($lang['sgrprerr'], $name, $uid, $cldbid));
								}
							}
							if (!in_array($groupid, $sgroups)) {
								check_shutdown($timezone,$logpath); usleep($slowmode);
								try {
									$ts3->serverGroupClientAdd($groupid, $cldbid);
									$grpsince = $nowtime;
									enter_logfile($logpath,$timezone,5,sprintf($lang['sgrpadd'], $dbgroups[$groupid]['sgidname'], $groupid, $name, $uid, $cldbid));
								}
								catch (Exception $e) {
									enter_logfile($logpath,$timezone,2,"calc_user 10:".sprintf($lang['sgrprerr'], $name, $uid, $cldbid));
								}
							}
							$grpid = $groupid;
							if ($msgtouser == 1) {
								check_shutdown($timezone,$logpath); usleep($slowmode);
								$days  = $dtF->diff($dtT)->format('%a');
								$hours = $dtF->diff($dtT)->format('%h');
								$mins  = $dtF->diff($dtT)->format('%i');
								$secs  = $dtF->diff($dtT)->format('%s');
								try {
									$ts3->clientGetByUid($uid)->message(sprintf($rankupmsg, $days, $hours, $mins, $secs, $dbgroups[$groupid]['sgidname'], $client['client_nickname']));
								} catch (Exception $e) {
									enter_logfile($logpath,$timezone,2,"calc_user 12:".sprintf($lang['sgrprerr'], $name, $uid, $cldbid));
								}
							}
						}
						if($grpcount == 1) {
							$nextup = 0;
						}
						break;
					} else {
						$nextup = $time - $activetime;
					}
				}
				$updatedata[] = array(
					"uuid" => $mysqlcon->quote($client['client_unique_identifier'], ENT_QUOTES),
					"cldbid" => $cldbid,
					"count" => $count,
					"ip" => $ip,
					"name" => $name,
					"lastseen" => $nowtime,
					"grpid" => $grpid,
					"nextup" => $nextup,
					"idle" => $idle,
					"cldgroup" => $cldgroup,
					"boosttime" => $boosttime,
					"platform" => $platform,
					"nation" => $nation,
					"version" => $version,
					"except" => $except,
					"grpsince" => $grpsince
				);
			} else {
				$grpid = '0';
				foreach ($grouptime as $time => $groupid) {
					if (in_array($groupid, $sgroups)) {
						$grpid = $groupid;
						break;
					}
				}
				$insertdata[] = array(
					"uuid" => $mysqlcon->quote($client['client_unique_identifier'], ENT_QUOTES),
					"cldbid" => $cldbid,
					"ip" => $ip,
					"name" => $name,
					"lastseen" => $nowtime,
					"grpid" => $grpid,
					"nextup" => $nextupforinsert,
					"cldgroup" => $cldgroup,
					"platform" => $platform,
					"nation" => $nation,
					"version" => $version,
					"firstcon" => $firstconnect,
					"except" => $except
				);
				$uidarr[] = $uid;
				enter_logfile($logpath,$timezone,5,sprintf($lang['adduser'], $name, $uid, $cldbid));
			}
		}
	}

	if($mysqlcon->exec("UPDATE $dbname.user SET online='0'") === false) {
		enter_logfile($logpath,$timezone,2,"calc_user 13:".print_r($mysqlcon->errorInfo(), true));
	}

	if ($insertdata != NULL) {
		$allinsertdata = '';
		foreach ($insertdata as $insertarr) {
			$allinsertdata = $allinsertdata . "(" . $insertarr['uuid'] . ", '" . $insertarr['cldbid'] . "', '1', " . $insertarr['ip'] . ", " . $insertarr['name'] . ", '" . $insertarr['lastseen'] . "', '" . $insertarr['grpid'] . "', '" . $insertarr['nextup'] . "', '" . $insertarr['cldgroup'] . "', '" . $insertarr['platform'] . "', '" . $insertarr['nation'] . "', '" . $insertarr['version'] . "', '" . $insertarr['firstcon'] . "', '" . $insertarr['except'] . "','1'),";
		}
		$allinsertdata = substr($allinsertdata, 0, -1);
		if ($allinsertdata != NULL) {
			if($mysqlcon->exec("INSERT INTO $dbname.user (uuid, cldbid, count, ip, name, lastseen, grpid, nextup, cldgroup, platform, nation, version, firstcon, except, online) VALUES $allinsertdata") === false) {
				enter_logfile($logpath,$timezone,2,"calc_user 14:".print_r($mysqlcon->errorInfo(), true));
			}
		}
	}

	unset($insertdata);
	unset($allinsertdata);
	if ($updatedata != NULL) {
		$allupdateuuid	 = '';
		$allupdatecldbid   = '';
		$allupdatecount	= '';
		$allupdateip	   = '';
		$allupdatename	 = '';
		$allupdatelastseen = '';
		$allupdategrpid	= '';
		$allupdatenextup   = '';
		$allupdateidle	 = '';
		$allupdatecldgroup = '';
		$allupdateboosttime = '';
		$allupdateplatform = '';
		$allupdatenation = '';
		$allupdateversion = '';
		$allupdateexcept = '';
		$allupdategrpsince = '';
		foreach ($updatedata as $updatearr) {
			$allupdateuuid	 = $allupdateuuid . $updatearr['uuid'] . ",";
			$allupdatecldbid   = $allupdatecldbid . "WHEN " . $updatearr['uuid'] . " THEN '" . $updatearr['cldbid'] . "' ";
			$allupdatecount	= $allupdatecount . "WHEN " . $updatearr['uuid'] . " THEN '" . $updatearr['count'] . "' ";
			$allupdateip	   = $allupdateip . "WHEN " . $updatearr['uuid'] . " THEN " . $updatearr['ip'] . " ";
			$allupdatename	 = $allupdatename . "WHEN " . $updatearr['uuid'] . " THEN " . $updatearr['name'] . " ";
			$allupdatelastseen = $allupdatelastseen . "WHEN " . $updatearr['uuid'] . " THEN '" . $updatearr['lastseen'] . "' ";
			$allupdategrpid	= $allupdategrpid . "WHEN " . $updatearr['uuid'] . " THEN '" . $updatearr['grpid'] . "' ";
			$allupdatenextup   = $allupdatenextup . "WHEN " . $updatearr['uuid'] . " THEN '" . $updatearr['nextup'] . "' ";
			$allupdateidle	 = $allupdateidle . "WHEN " . $updatearr['uuid'] . " THEN '" . $updatearr['idle'] . "' ";
			$allupdatecldgroup = $allupdatecldgroup . "WHEN " . $updatearr['uuid'] . " THEN '" . $updatearr['cldgroup'] . "' ";
			$allupdateboosttime = $allupdateboosttime . "WHEN " . $updatearr['uuid'] . " THEN '" . $updatearr['boosttime'] . "' ";
			$allupdateplatform = $allupdateplatform . "WHEN " . $updatearr['uuid'] . " THEN '" . $updatearr['platform'] . "' ";
			$allupdatenation = $allupdatenation . "WHEN " . $updatearr['uuid'] . " THEN '" . $updatearr['nation'] . "' ";
			$allupdateversion = $allupdateversion . "WHEN " . $updatearr['uuid'] . " THEN '" . $updatearr['version'] . "' ";
			$allupdateexcept = $allupdateexcept . "WHEN " . $updatearr['uuid'] . " THEN '" . $updatearr['except'] . "' ";
			$allupdategrpsince = $allupdategrpsince . "WHEN " . $updatearr['uuid'] . " THEN '" . $updatearr['grpsince'] . "' ";
		}
		$allupdateuuid = substr($allupdateuuid, 0, -1);
		if($mysqlcon->exec("UPDATE $dbname.user set cldbid = CASE uuid $allupdatecldbid END, count = CASE uuid $allupdatecount END, ip = CASE uuid $allupdateip END, name = CASE uuid $allupdatename END, lastseen = CASE uuid $allupdatelastseen END, grpid = CASE uuid $allupdategrpid END, nextup = CASE uuid $allupdatenextup END, idle = CASE uuid $allupdateidle END, cldgroup = CASE uuid $allupdatecldgroup END, boosttime = CASE uuid $allupdateboosttime END, platform = CASE uuid $allupdateplatform END, nation = CASE uuid $allupdatenation END, version = CASE uuid $allupdateversion END, except = CASE uuid $allupdateexcept END, grpsince = CASE uuid $allupdategrpsince END, online = 1 WHERE uuid IN ($allupdateuuid)") === false) {
			enter_logfile($logpath,$timezone,2,"calc_user 15:".print_r($mysqlcon->errorInfo(), true));
		}
	}
}
?>