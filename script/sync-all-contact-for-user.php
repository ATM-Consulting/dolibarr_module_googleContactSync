<?php
	set_time_limit(0);
	require '../config.php';


	$fk_user = GETPOST('fk_user');

	if (!empty($fk_user))
	{
		$u=new User($db);
		$u->fetch($fk_user);
		if($u->id <=0 ) exit('fk_user : fetch fail');
		echo $u->getNomUrl(1);
	}
	else
	{
		echo "Pas de fk_user, c'est donc pour tous les users qui on un token<br />";
	}
	

	dol_include_once('/googlecontactsync/class/gcs.class.php');
				  	
	$PDOdb=new TPDOdb;
		
	$Tab = $PDOdb->ExecuteAsArray("
		SELECT rowid, 'contact' as type_object FROM ".MAIN_DB_PREFIX."socpeople WHERE 1 
		UNION
		SELECT rowid, 'societe' as type_object FROM ".MAIN_DB_PREFIX."societe WHERE 1 
		UNION
		SELECT rowid, 'user_object' as type_object FROM ".MAIN_DB_PREFIX."user WHERE 1 
	");
	
	$count = count($Tab);
	echo $count."<br />";
//	var_dump($Tab);

	$i=0;
	foreach($Tab as &$row) {
		$i++;
		if ($i % 100 == 0) {
			echo "iteration ".$i." / ".$count."<br />";
			flush();
		}
		
		$fk_object = $row->rowid;
		$type_object = $row->type_object;
		
		if ($type_object == 'societe' && empty($conf->global->GCS_GOOGLE_SYNC_THIRDPARTY)) continue;
		if ($type_object == 'contact' && (empty($conf->global->GCS_GOOGLE_SYNC_CONTACT) || !empty($conf->global->GCS_GOOGLE_SYNC_ALL_CONTACT_FROM_SOCIETE))) continue;
		if ($type_object == 'user_object' && empty($conf->global->GCS_GOOGLE_SYNC_USER)) continue;
		
		// S'occupe de passer l'attribut "to_sync" à 1 => puis la tâche cron fera la synchro
		if (!empty($fk_user)) TGCSToken::setSync($PDOdb, $fk_object, $type_object, $u->id);
		else TGCSToken::setSyncAll($PDOdb, $fk_object, $type_object);
	}

