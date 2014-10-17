<?php

// Tastydir is copyright Vlad Harbuz (vladh.net)

define('TASTYDIR_VERSION','1.2');
define('TASTYDIR_SUBVERSION','1252');

error_reporting(0);

require_once 'common.php';
if(!file_exists('settings.php')){
	echo 'settings.php doesn\'t exist! Please run the install script.';
	die();
}else{
	require_once 'settings.php';
}

if(empty($homedir)){
	$path=dirname(dirname($_SERVER['SCRIPT_FILENAME']));
}else{
	$path=$homedir;
}

// check if logged in
$auth['loggedin']=false;
if($auth['use']){
	if(!empty($_COOKIE['tastydir_auth']) && $_COOKIE['tastydir_auth']==sha1($auth['pass'])){
		$auth['loggedin']=true;
	}
}else{
	$auth['loggedin']=true;
}

if(		(
			(	!empty($_GET['getf'])							)||
			(	!empty($_GET['editf']) && isset($_POST['fdata']))||
			(	!empty($_GET['delf'])							)||
			(	!empty($_GET['delfld'])							)||
			(	!empty($_GET['chmod'])							)||
			(	isset($_FILES['file']) && isset($_POST['dir'])	)||
			(	!empty($_GET['copy'])							)||
			(	!empty($_GET['mkdir'])							)
		) && !(
			$auth['loggedin']
		)
){
	echo 'Sorry, but you can\'t modify files if you\'re not logged in!';
	die();
}

$reqs=@array($_GET['editf'],$_GET['delf'],$_GET['delfld'],$_GET['to'],$_POST['dir'].$_FILES['file']['name'],$_GET['download']);
foreach($reqs as $req){
	if( !empty($req) ){
		$reqq=str_replace('\\','/',realpath($req));
		foreach($inv as $in){
			if(substr($reqq,0,strlen($in)) == $in){
				$ret=array('status'=>100);
				echo $_GET['cb']."(".json_encode($ret).");";
				die();
			}
		}
	}
}

//							 _       _          __ _ _
//			 _   _ _ __   __| | __ _| |_ ___   / _(_) | ___  ___
//			| | | | '_ \ / _` |/ _` | __/ _ \ | |_| | |/ _ \/ __|
//			| |_| | |_) | (_| | (_| | ||  __/ |  _| | |  __/\__ \
//			 \__,_| .__/ \__,_|\__,_|\__\___| |_| |_|_|\___||___/
//				  |_|
//

if(isset($_GET['d'])){
	$files=array();
	$dirs=array();
	$dirch=true;
	if(!empty($_GET['d'])){
		$cdir=stripslashes(rawurldecode($_GET['d']));
		//$cdir=unslash(str_replace('\\','/',$cdir));
		while( ($cdir[strlen($cdir) -1 ]) == '/' ){
			$cdir=substr($cdir,0,-1);
		}
		$dirch=@chdir($cdir);
	}else{
		$dirch=@chdir('../');
		$cdir=str_replace('\\','/',getcwd());
	}
	$cdirs=explode('/',$cdir);
	if($cdirs[0]==''){
		$cdirs[0]='/';
	}
	if( $dirch && ($auth['loggedin'] || substr(unslash($cdir),0,strlen($path))==$path) && (realpath($cdir)!=dirname($_SERVER['SCRIPT_FILENAME'])) ){ 
		if(empty($cdirs[count($cdirs)-1])) array_pop($cdirs);
		$tfiles=glob('*');
		if(is_array($tfiles) && count($tfiles) > 0){
			foreach($tfiles as $tfile){
				if(is_dir($tfile)){
					if(!in_array($cdir.'/'.$tfile,$inv)){
						$stat=@stat($tfile);
						$ui=posix_getpwuid($stat['uid']);
						$gi=posix_getgrgid($stat['gid']);
						$dirs[]=array('n'=>stripslashes($tfile), 's'=>'', 'fp'=>view_perms_color($tfile), 'p'=>view_perms(@fileperms($tfile)));
					}
				}else{
					if(!in_array($cdir.'/'.$tfile,$inv)){
						$fext=substr($tfile,strrpos($tfile,'.')+1);
						$type='page';
						foreach($ext as $name=>$exts){
							if(in_array(strtolower($fext),$exts)) $type=$name;
						}
						$stat=@stat($tfile);
						$ui=posix_getpwuid($stat['uid']);
						$gi=posix_getgrgid($stat['gid']);
						$bin=is_binary($tfile) ? 'true' : 'false';
						$files[]=array('n'=>stripslashes($tfile), 's'=>size_readable(@filesize($tfile)), 'fp'=>view_perms_color($tfile), 'p'=>view_perms(@fileperms($tfile)), 't'=>$type, 'b'=>$bin);
					}
				}
			}
		}
	}
	$arr=array('jsonlock'=>$_GET['lock'],'cdir'=>$cdir,'cdirs'=>$cdirs,'dirs'=>$dirs,'files'=>$files);
	echo $_GET['cb']."(".json_encode($arr).");";
	die();
}

//							_              _          __  __
//			 _ __ ___   ___| |_ __ _   ___| |_ _   _ / _|/ _|
//			| '_ ` _ \ / _ \ __/ _` | / __| __| | | | |_| |_
//			| | | | | |  __/ || (_| | \__ \ |_| |_| |  _|  _|
//			|_| |_| |_|\___|\__\__,_| |___/\__|\__,_|_| |_|
//

if(!empty($_GET['getinfo'])){
	// status codes:
	// 0 	ok
	// 1	file doesn't exist
	$f=stripslashes(rawurldecode($_GET['getinfo']));
	$ret=array('status'=>0);
	if(!file_exists($f)){
		$ret['status']=1;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	$stat=@stat($f);
	$ui=posix_getpwuid($stat['uid']);
	$gi=posix_getgrgid($stat['gid']);
	$ret['f']=$stat;
	$ret['u']=$ui;
	$ret['g']=$gi;
	echo $_GET['cb']."(".json_encode($ret).");";
	die();
}

if(isset($_GET['lt'])){
	if($win){
		$lt=array();
		foreach(range('c','z') as $ltr){
			if(is_dir($ltr.':/')) $lt[]=strtoupper($ltr);
		}
		echo $_GET['cb']."(".json_encode($lt).");";
	}
	die();
}

//					  _ _ _      __ _ _
//			  ___  __| (_) |_   / _(_) | ___
//			 / _ \/ _` | | __| | |_| | |/ _ \
//			|  __/ (_| | | |_  |  _| | |  __/
//			 \___|\__,_|_|\__| |_| |_|_|\___|
//	

if(!empty($_GET['getf'])){
	// status codes:
	// 0 	ok
	// 1	file doesn't exist
	// 2	file is binary
	$f=stripslashes(rawurldecode($_GET['getf']));
	$ret=array('status'=>0,'data'=>'');
	if(!file_exists($f)){
		$ret['status']=1;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	if(is_binary($f)){
		$ret['status']=2;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	if(filesize($f) > 0){
		$fh = fopen($f, "rb");
		$file = fread($fh,filesize($f));
		fclose($fh);
		clearstatcache();
		$ret['data']=$file;
	}
	echo $_GET['cb']."(".json_encode($ret).");";
	die();
}

if(!empty($_GET['editf']) && isset($_POST['fdata']) ){
	// status codes:
	// 0 	ok
	// 1	file isn't writable
	// 2	invalid filename
	// 3	couldn't write to file
	$f=stripslashes(rawurldecode($_GET['editf']));
	if(get_magic_quotes_gpc()){
		$data=stripslashes($_POST['fdata']);
	}else{
		$data=$_POST['fdata'];
	}
	$ret=array('status'=>0);
	if(file_exists($f)){
		if(!is_writable($f)){
			$ret['status']=1;
			echo $_GET['cb']."(".json_encode($ret).");";
			die();
		}
	}else{
		if(!is_writable(dirname($f))){
			$ret['status']=1;
			echo $_GET['cb']."(".json_encode($ret).");";
			die();
		}
	}
	$invalid=false;
	foreach(str_split(basename($f)) as $ff){
		if(in_array($ff,$fname_blacklist)){
			$invalid=true;
		}
	}
	if($invalid){
		$ret['status']=2;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	$fh = @fopen($f, "wb");
	if(!$fh){
		$ret['status']=3;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	fwrite($fh,$data);
	fclose($fh);
	clearstatcache();
	echo $_GET['cb']."(".json_encode($ret).");";
	die();
}

//				 _      _      _          __ _ _
//			  __| | ___| | ___| |_ ___   / _(_) | ___
//			 / _` |/ _ \ |/ _ \ __/ _ \ | |_| | |/ _ \
//			| (_| |  __/ |  __/ ||  __/ |  _| | |  __/
//			 \__,_|\___|_|\___|\__\___| |_| |_|_|\___|
//

if(!empty($_GET['delf'])){
	// status codes:
	// 0 	ok
	// 1	file doesn't exist
	// 2	file isn't writable
	// 3	couldn't delete
	$f=stripslashes(rawurldecode($_GET['delf']));
	$ret=array('status'=>0);
	if(!file_exists($f)){
		$ret['status']=1;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	if(!is_writable($f)){
		$ret['status']=2;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	if(!@unlink($f)){
		$ret['status']=3;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	echo $_GET['cb']."(".json_encode($ret).");";
	die();
}

//					  _ _ _      __       _     _
//			  ___  __| (_) |_   / _| ___ | | __| | ___ _ __
//			 / _ \/ _` | | __| | |_ / _ \| |/ _` |/ _ \ '__|
//			|  __/ (_| | | |_  |  _| (_) | | (_| |  __/ |
//			 \___|\__,_|_|\__| |_|  \___/|_|\__,_|\___|_|
//

if(!empty($_GET['mkdir'])){
	// status codes:
	// 0 	ok
	// 1	parent isn't writable
	// 2	invalid filename
	// 3	couldn't write to file
	$f=stripslashes(rawurldecode($_GET['mkdir']));
	$ret=array('status'=>0);
	if(!is_writable(dirname($f))){
		$ret['status']=1;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	$invalid=false;
	foreach(str_split(basename($f)) as $ff){
		if(in_array($ff,$fname_blacklist)){
			$invalid=true;
		}
	}
	if($invalid){
		$ret['status']=2;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	if(!@mkdir($f)){
		$ret['status']=3;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	echo $_GET['cb']."(".json_encode($ret).");";
	die();
}

//				 _      _      _          __       _     _
//			  __| | ___| | ___| |_ ___   / _| ___ | | __| | ___ _ __
//			 / _` |/ _ \ |/ _ \ __/ _ \ | |_ / _ \| |/ _` |/ _ \ '__|
//			| (_| |  __/ |  __/ ||  __/ |  _| (_) | | (_| |  __/ |
//			 \__,_|\___|_|\___|\__\___| |_|  \___/|_|\__,_|\___|_|
//

if(!empty($_GET['delfld'])){
	// status codes:
	// 0 	ok
	// 1	file doesn't exist
	// 2	file isn't writable
	// 3	couldn't delete
	$f=stripslashes(rawurldecode($_GET['delfld']));
	$ret=array('status'=>0);
	if(!file_exists($f)){
		$ret['status']=1;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	if(!is_writable($f)){
		$ret['status']=2;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	if(!@deleteAll($f)){
		$ret['status']=3;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	echo $_GET['cb']."(".json_encode($ret).");";
	die();
}

//						 _                 _    __ _ _
//			 _   _ _ __ | | ___   __ _  __| |  / _(_) | ___
//			| | | | '_ \| |/ _ \ / _` |/ _` | | |_| | |/ _ \
//			| |_| | |_) | | (_) | (_| | (_| | |  _| | |  __/
//			 \__,_| .__/|_|\___/ \__,_|\__,_| |_| |_|_|\___|
//				  |_|
//

if( isset($_FILES['file']) && isset($_POST['dir']) ){
	// status codes:
	// 0 	ok
	// 1	file already exists
	// 2	couldn't upload
	
	$dest=$_POST['dir'].'/';
	$where=$dest.basename($_FILES['file']['name']);
  $fext=strtolower(substr($where,strrpos($where,'.')+1));
  echo $fext;
	
	$ret=array('status'=>0);
	if( file_exists($where) && !isset($_POST['force']) ){
		$ret['status']=1;
		$ret['type']=is_dir($where) ? 'dir' : 'file';
	}elseif(in_array($fext, $fext_blacklist) ||
          !@move_uploaded_file($_FILES['file']['tmp_name'], $where)){
		$ret['status']=2;
	}
	
	?>
	<script>
		window.top.window.stopUpload(<?php echo json_encode($ret); ?>);
	</script>
	<?php
	die();
}

//			  __ _ _             _               _
//			 / _(_) | ___    ___| |__   ___  ___| | __
//			| |_| | |/ _ \  / __| '_ \ / _ \/ __| |/ /
//			|  _| | |  __/ | (__| | | |  __/ (__|   <
//			|_| |_|_|\___|  \___|_| |_|\___|\___|_|\_\
//

if(!empty($_GET['fcheck'])){
	$ret=array();
	$f=stripslashes(rawurldecode($_GET['fcheck']));
	$ret['exists']=@file_exists($f);
	$ret['type']=@is_dir($f) ? 'dir' : 'file';
	echo $_GET['cb']."(".json_encode($ret).");";
	die();
}

//									  __ _ _
//			  ___ ___  _ __  _   _   / _(_) | ___
//			 / __/ _ \| '_ \| | | | | |_| | |/ _ \
//			| (_| (_) | |_) | |_| | |  _| | |  __/
//			 \___\___/| .__/ \__, | |_| |_|_|\___|
//					  |_|    |___/
//

if(!empty($_GET['copy']) && $_GET['copy']=='copy'){
	// status codes:
	// 0 	ok
	// 1	no file
	// 2	dest not writable
	// 3	invalid fname
	// 4	couldn't
	$f=stripslashes(rawurldecode($_GET['from']));
	$to=stripslashes(rawurldecode($_GET['to']));
	$ret=array('status'=>0);
	if(!file_exists($f)){
		$ret['status']=1;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	if(!is_writable(dirname($to))){
		$ret['status']=2;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	$invalid=false;
	foreach(str_split(basename($to)) as $ff){
		if(in_array($ff,$fname_blacklist)){
			$invalid=true;
		}
	}
	if($invalid){
		$ret['status']=3;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	if(!@copy($f,$to)){
		$ret['status']=4;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	echo $_GET['cb']."(".json_encode($ret).");";
	die();
}

if(!empty($_GET['copy']) && $_GET['copy']=='move'){
	// status codes:
	// 0 	ok
	// 1	no file
	// 2	origin not writable
	// 3	dest not writable
	// 4	invalid filename
	// 5	couldn't
	$f=stripslashes(rawurldecode($_GET['from']));
	$to=stripslashes(rawurldecode($_GET['to']));
	$ret=array('status'=>0);
	if(!file_exists($f)){
		$ret['status']=1;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	if(!is_writable($f)){
		$ret['status']=2;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	if(!is_writable(dirname($to))){
		$ret['status']=3;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	$invalid=false;
	foreach(str_split(basename($to)) as $ff){
		if(in_array($ff,$fname_blacklist)){
			$invalid=true;
		}
	}
	if($invalid){
		$ret['status']=4;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	if(!@rename($f,$to)){
		$ret['status']=5;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	echo $_GET['cb']."(".json_encode($ret).");";
	die();
}

//				 _                     _                 _    __ _ _
//			  __| | _____      ___ __ | | ___   __ _  __| |  / _(_) | ___
//			 / _` |/ _ \ \ /\ / / '_ \| |/ _ \ / _` |/ _` | | |_| | |/ _ \
//			| (_| | (_) \ V  V /| | | | | (_) | (_| | (_| | |  _| | |  __/
//			 \__,_|\___/ \_/\_/ |_| |_|_|\___/ \__,_|\__,_| |_| |_|_|\___|
//

if(!empty($_GET['download'])){
	$file=stripslashes(rawurldecode($_GET['download']));
	$name=basename($file);
	if( !$auth['loggedin'] && substr($file,0,strlen($path))!=$path ){ 
		echo 'Sorry, but you need to login to be able to download that file.';
		die();
	}
	if(!file_exists($file)){
		echo 'Sorry, the file you\'re trying to download doesn\'t exist.';
		die();
	}
	if(!is_readable($file)){
		echo 'Sorry, the file you\'re trying to download isn\'t readable.';
		die();
	}
	if(strpos($name,'.')!==FALSE){
		$fext=strtolower(substr($file,strrpos($file,'.')+1));
	}
	if(!$auth['loggedin'] && in_array($fext,$ext['php'])){
		echo 'Sorry, but you need to login to be able to download that file.';
		die();
	}
	$mime='application/octet-stream';
	if(!isset($_GET['forcedl'])){
		if(!empty($fext)){			
			if(		in_array($fext,$ext['php']) || in_array($fext,$ext['c']) || in_array($fext,$ext['cpp']) ||
					in_array($fext,$ext['cs']) || in_array($fext,$ext['css']) || in_array($fext,$ext['html']) ||
					in_array($fext,$ext['xml']) || in_array($fext,$ext['js']) || in_array($fext,$ext['sql']) ||
					in_array($fext,$ext['rb']) || in_array($fext,$ext['code']) || in_array($fext,$ext['text'])
			){
				$mime='text/plain';
			}else if(in_array($fext,$ext['img'])){
				$mime='image/'.str_replace('jpg','jpeg',str_replace('apng','png',$fext));
			}else if(in_array($fext,$ext['video'])){
				$mime='video/'.str_replace('mov','quicktime',str_replace('wmv','x-ms-wmv',str_replace('mpg','mpeg',$fext)));
			}else if(in_array($fext,$ext['psd'])){
				$mime="application/psd";
			}else if(in_array($fext,$ext['ai'])){
				$mime="application/illustrator";
			}else if(in_array($fext,$ext['pdf'])){
				$mime="application/pdf";
			}else if($fext=='xls'){
				$mime="application/vnd.ms-excel";
			}else if($fext=='xlsx'){
				$mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
			}else if($fext=='xls'){
				$mime="application/vnd.ms-powerpoint";
			}else if($fext=='xlsx'){
				$mime="application/vnd.openxmlformats-officedocument.presentationml.presentation";
			}else if($fext=='xls'){
				$mime="application/vnd.ms-word";
			}else if($fext=='xlsx'){
				$mime="application/vnd.openxmlformats-officedocument.wordprocessingml.document";
			}else if($fext=='swf'){
				$mime="application/x-shockwave-flash";
			}else if(in_array($fext,$ext['audio'])){
				switch($fext){
					case 'mp3':
						$mime='audio/mpeg';
						break;
					case 'flac':
						$mime='audio/flac';
						break;
					case 'aac':
						$mime='audio/aac';
						break;
					case 'm4a':
						$mime='audio/mp4a-latm';
						break;
					case 'mpga':
						$mime='audio/mpeg';
						break;
					case 'ogg':
						$mime='audio/ogg';
						break;
				}
			}
		}else if(!is_binary($file)){
			$mime='text/plain';
		}
	}
	if($mime=='application/octet-stream'){
		header('Content-Disposition: attachment; filename="'.$name.'"');
	}else{
		header('Content-Disposition: inline; filename="'.$name.'"');
	}
	header('Content-Description: File Transfer');
	header('Content-Type: '.$mime);
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . filesize($file));
	ob_clean();
	flush();
	readfile($file);
	die();
}

//						_         _
//			  __ _  ___| |_   ___(_)_______
//			 / _` |/ _ \ __| / __| |_  / _ \
//			| (_| |  __/ |_  \__ \ |/ /  __/
//			 \__, |\___|\__| |___/_/___\___|
//			 |___/
//

if(!empty($_GET['getsize'])){
	// status codes:
	// 0 	ok
	// 1	no file
	// 2	couldn't get size, probably not an image
	$img=stripslashes(rawurldecode($_GET['getsize']));
	$ret=array('status'=>0);
	if(!file_exists($img)){
		$ret['status']=1;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	if(!list($width, $height, $i_type, $i_attr) = @getimagesize($img)){
		$ret['status']=2;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	$ret['width']=$width;
	$ret['height']=$height;
	// get next and previous imgs
	$imgs=array();
	$files=glob(dirname($img).'/*');
	foreach($files as $key=>$file){
		if($file==$img) break; 
	}
	$found=false;
	$ret['imgb']='';
	for($i=$key-1;$i>=0 && !$found;$i--){
		$fname=$files[$i];
		if(strpos($fname,'.')!==FALSE){
			$fext=strtolower(substr($fname,strrpos($fname,'.')+1));
			if(in_array($fext,$ext['img'])){
				$ret['imgb']=$fname;
				$found=true;
			}
		}
	}
	$found=false;
	$ret['imga']='';
	for($i=$key+1;$i<=count($files)-1 && !$found;$i++){
		$fname=$files[$i];
		if(strpos($fname,'.')!==FALSE){
			$fext=strtolower(substr($fname,strrpos($fname,'.')+1));		
			if(in_array($fext,$ext['img'])){
				$ret['imga']=$fname;
				$found=true;
			}
		}
	}
	echo $_GET['cb']."(".json_encode($ret).");";
	die();
}

//				  _                         _
//			  ___| |__  _ __ ___   ___   __| |
//			 / __| '_ \| '_ ` _ \ / _ \ / _` |
//			| (__| | | | | | | | | (_) | (_| |
//			 \___|_| |_|_| |_| |_|\___/ \__,_|
//

if(!empty($_GET['chmod']) && !empty($_GET['to'])){
	// status codes:
	// 0 	ok
	// 1	file doesn't exist
	// 2	couldn't chmod
	$f=stripslashes(rawurldecode($_GET['chmod']));
	$to=$_GET['to'];
	$ret=array('status'=>0);
	if(!file_exists($f)){
		$ret['status']=1;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	if(!@chmod($f,octdec('0'.$to))){
		$ret['status']=2;
		echo $_GET['cb']."(".json_encode($ret).");";
		die();
	}
	echo $_GET['cb']."(".json_encode($ret).");";
	die();
}

echo 'Unknown action.';





















