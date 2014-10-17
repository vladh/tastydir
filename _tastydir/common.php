<?php

if(!defined('TASTYDIR_VERSION')){
	echo 'No direct access allowed.';
	die();
}

// CONFIGURABLE OPTIONS

// characters that are disallowed in filenames
$fname_blacklist=array('\\','/',':','*','?','"','<','>','|','%','&',chr(255));

// files with these extensions cannot be uploaded
$fext_blacklist=array('');

// if you want the home directory to be something other than the actual directory tastydir is installed in, change this. FULL PATH PLEASE! default is blank.
$homedir='';

// END OF CONFIGURABLE OPTIONS

$t_fn='http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
$t_dn='http://'.unslash($_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/');
$win=strtolower(substr(PHP_OS,0,3)) == "win";

if(empty($homedir)){
	$path=dirname($_SERVER['SCRIPT_FILENAME']);
}else{
	if($win){
		$path=str_replace('\\','/',$homedir);
	}else{
		$path=$homedir;
	}
}

// globally invisible files
// tastydir's files are added to this to prevent security problems and generally creating a mess
$inv=array(
dirname($path).'/index.php',
dirname($path).'/_tastydir'
);

if(!function_exists('posix_getpwuid')){
	function posix_getpwuid($id){
		return array(
			'name'=>getenv('USERNAME'),
			'passwd'=>'',
			'uid'=>0,
			'gid'=>0,
			'geocs'=>'',
			'dir'=>'',
			'shell'=>''
		);
	}
}

if(!function_exists('posix_getgrgid')){
	function posix_getgrgid($id){
		return array(
			'name'=>'N/A',
			'passwd'=>'',
			'members'=>array(),
			'gid'=>0
		);
	}
}

// json_encode for PHP 4 and early PHP 5 - thanks Steve!
// http://au.php.net/manual/en/function.json-encode.php#82904
if (!function_exists('json_encode'))
{
  function json_encode($a=false)
  {
    if (is_null($a)) return 'null';
    if ($a === false) return 'false';
    if ($a === true) return 'true';
    if (is_scalar($a))
    {
      if (is_float($a))
      {
        // Always use "." for floats.
        return floatval(str_replace(",", ".", strval($a)));
      }

      if (is_string($a))
      {
        static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
        return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
      }
      else
        return $a;
    }
    $isList = true;
    for ($i = 0, reset($a); $i < count($a); $i++, next($a))
    {
      if (key($a) !== $i)
      {
        $isList = false;
        break;
      }
    }
    $result = array();
    if ($isList)
    {
      foreach ($a as $v) $result[] = json_encode($v);
      return '[' . join(',', $result) . ']';
    }
    else
    {
      foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
      return '{' . join(',', $result) . '}';
    }
  }
}

if($win){
	$t_curruser=getenv('USERNAME');
}else{
	if(function_exists('posix_geteuid')){
		$uid=posix_geteuid();
	}else if(function_exists('posix_getuid')){
		$uid=posix_getuid();
	}else{
		$uid=99;
	}
	$t_pcurruser=posix_getpwuid($uid);
	$t_curruser=$t_pcurruser['name'];
}

$ext=array(
	'php'=>array('php','phtml','php3','php4','php5','phps'),
	'c'=>array('c','h'),
	'cpp'=>array('cpp','hpp'),
	'cs'=>array('cs'),
	'css'=>array('css'),
	'html'=>array('htm','html','shtml','xhtml'),
	'xml'=>array('xml'),
	'js'=>array('js'),
	'sql'=>array('sql'),
	'psd'=>array('psd'),
	'ai'=>array('ai','eps'),
	'pdf'=>array('pdf'),
	'rb'=>array('rb'),
	'excel'=>array('xls','xlsx'),
	'ppt'=>array('ppt','pptx'),
	'word'=>array('doc','docx'),
	'flash'=>array('flv','swf','fla','as'),
	'svg'=>array('svg'),
	'img'=>array('jpg','jpeg','png','gif','bmp','apng','tif','tiff'),
	'img2'=>array('cdr','raw','tga'),
	'code'=>array('pl','py','ini','cfg','sh','asp','aspx','asm'),
	'text'=>array('txt','rtf'),
	'video'=>array('avi','mov','mp4','wmv','mkv','vob','mpg'),
	'audio'=>array('mp3','flac','aac','m4a','m3u','pls','mpga','ogg'),
	'arc'=>array('zip','rar','7z','tar','gz','bz2','tgz')
);

function size_readable($size, $max = null, $system = 'si', $retstring = '%01.2f %s')
{
    $systems['si']['prefix'] = array('B', 'K', 'MB', 'GB', 'TB', 'PB');
    $systems['si']['size']   = 1000;
    $systems['bi']['prefix'] = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
    $systems['bi']['size']   = 1024;
    $sys = isset($systems[$system]) ? $systems[$system] : $systems['si'];
    $depth = count($sys['prefix']) - 1;
    if ($max && false !== $d = array_search($max, $sys['prefix'])) {
        $depth = $d;
    }
    $i = 0;
    while ($size >= $sys['size'] && $i < $depth) {
        $size /= $sys['size'];
        $i++;
    }
    return sprintf($retstring, $size, $sys['prefix'][$i]);
}

function view_perms($mode){
	$owner["read"] = ($mode & 00400)?"r":"-";
	$owner["write"] = ($mode & 00200)?"w":"-";
	$owner["execute"] = ($mode & 00100)?"x":"-";
	$group["read"] = ($mode & 00040)?"r":"-";
	$group["write"] = ($mode & 00020)?"w":"-";
	$group["execute"] = ($mode & 00010)?"x":"-";
	$world["read"] = ($mode & 00004)?"r":"-";
	$world["write"] = ($mode & 00002)? "w":"-";
	$world["execute"] = ($mode & 00001)?"x":"-";
	
	if (($mode & 0xC000) === 0xC000) {$type = "s";}
	elseif (($mode & 0x4000) === 0x4000) {$type = "d";}
	elseif (($mode & 0xA000) === 0xA000) {$type = "l";}
	elseif (($mode & 0x8000) === 0x8000) {$type = "-";}
	elseif (($mode & 0x6000) === 0x6000) {$type = "b";}
	elseif (($mode & 0x2000) === 0x2000) {$type = "c";}
	elseif (($mode & 0x1000) === 0x1000) {$type = "p";}
	else {$type = "?";}

	if ($mode & 0x800) {$owner["execute"] = ($owner["execute"] == "x")?"s":"S";}
	if ($mode & 0x400) {$group["execute"] = ($group["execute"] == "x")?"s":"S";}
	if ($mode & 0x200) {$world["execute"] = ($world["execute"] == "x")?"t":"T";}

	$ret = $type.join("",$owner).join("",$group).join("",$world);
	return $ret;
}

function view_perms_color($o){
	if (!is_readable($o)){
		$ret='<span class="no">'.view_perms(@fileperms($o)).'</span>';
	}elseif(!is_writable($o)){
		$ret='<span>'.view_perms(@fileperms($o)).'</span>';
	}else{
		$ret='<span class="ok">'.view_perms(@fileperms($o)).'</span>';
	}
	return $ret;
}


function is_binary($file) { 
	if (@file_exists($file)) { 
		if (!@is_file($file)) return 0; 
		
		$fh  = @fopen($file, "r"); 
		$blk = @fread($fh, 512); 
		@fclose($fh); 
		clearstatcache();
		
		return ( 
			0 or @substr_count($blk, "^ -~", "^\r\n")/512 > 0.3 
			or @substr_count($blk, "\x00") > 0 
		); 
	} 
	return 0; 
}  

function deleteAll($directory, $empty = false) {
	if(substr($directory,-1) == "/") {
		$directory = substr($directory,0,-1);
	}

	if(!file_exists($directory) || !is_dir($directory)) {
		return false;
	} elseif(!is_readable($directory)) {
		return false;
	} else {
		$directoryHandle = opendir($directory);
		
		while ($contents = readdir($directoryHandle)) {
			if($contents != '.' && $contents != '..') {
				$path = $directory . "/" . $contents;
               
				if(is_dir($path)) {
					deleteAll($path);
				} else {
					unlink($path);
				}
			}
		}
       
		closedir($directoryHandle);

		if($empty == false) {
			if(!rmdir($directory)) {
				return false;
			}
		}
       
		return true;
	}
}

function unslash($string){
	while(strpos($string,'//')!==false){
		$string=str_replace('//','/',$string);
	}
	return $string;
}
	
	
	
