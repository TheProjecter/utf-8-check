<?php
class Utf8Check 
{
	protected static $assign = null;
	public static $dberror = false;
	
	public static function initDb() {
		if ($_REQUEST) {
			$connection=mysql_connect($_REQUEST["host"], $_REQUEST["user"], $_REQUEST["pwd"]) or die("DB connection failed.");
			//mysql_select_db($_REQUEST["db"], $connection) or die("DB table not found.");
			if ($_REQUEST["useUtf8"]) {
				mysql_query("SET NAMES 'utf8'");
				ini_set('default_charset', 'utf8');
			}
		} else {
			die("Please enter credentials for DB check");
		}
	}
	
	private static function checkUtf8() 
	{
		//self::initDb();
		
		$data = Array();
		define('ABSTRACT_OE','ö');
		
		/* Character Set */
		$query = "SHOW VARIABLES LIKE 'character_set_%'";
		$result = mysql_query($query) or die("DB Query failed: ".$query);
		self::assign('dberror',false);
		while($row=mysql_fetch_array($result)) {
			$data[$row[0]] = $row[1];
			$bool[$row[0]] = preg_match('/utf/',$row[1]);
			if ($row[0] == "character_set_filesystem" AND $row[1] == "binary") {
				$bool[$row[0]] = true;
			}
			if ($bool[$row[0]] == false) {
				self::assign('dberror',true);
			}
		}
		
		/* Collation */
		$query = "SHOW VARIABLES LIKE 'collation_%'";
		$result = mysql_query($query) or die("DB Query failed: ".$query);
		
		while($row=mysql_fetch_array($result)) {
			// add result string to $data array
			$data[$row[0]] = $row[1];
			
			// add boolean check to $bool array if string contains "utf"
			$bool[$row[0]] = preg_match('/utf/',$row[1]);
		}
		// info array -> get text from this
		self::assign('data',$data);
		
		// bool array -> get "assertation met" from here
		self::assign('bool',$bool);
		
		/* Testquery */
		#$query = "CREATE TABLE utf8_test (`utf_text` text, `utf_varchar` varchar(5)) DEFAULT CHARSET=utf8";
		#$result = Redspark_RsDatabase::abstract_query($query);
		
		$query = "SELECT '".ABSTRACT_OE."',CHAR(50102 USING utf8),CHAR(246 USING latin1),ORD('".ABSTRACT_OE."'),ASCII(CHAR(246 USING latin1))";
		$result = mysql_query($query) or die("Anfrage nicht erfolgreich");
		if (!$result) {
			echo ('Error in query '.$query);
			return;
		}
		$field = 'Test';//abstract_field_name($result,0);
		$row = mysql_fetch_array($result);
		self::assign('queryhl',$field);
		self::assign('queryoe1',$row[0]);
		self::assign('queryoe2',$row[1]);
		self::assign('queryoe3',$row[2]);
		self::assign('queryord',$row[3]);
		self::assign('queryascii',$row[4]);
		self::assign('strlen_queryhlstrlen',strlen($field));
		self::assign('strlen_queryhlstrlen_mb',mb_strlen($field));
		#ini_set('mbstring.internal_encoding','UTF-8');
		#ini_set('mbstring.func_overload',1);
		
		/* PHP */
		if (ini_get('default_charset')) {
			self::assign('default_charset',ini_get('default_charset'));
			if (preg_match('/utf/i',ini_get('default_charset'))) {
				self::assign('default_charset_ok',true);
			} else {
				self::assign('default_charset_ok',false);
			}			
		} else {
			self::assign('default_charset','unset');
			self::assign('default_charset_ok',false);
		}
		self::assign('mb_lang_ok',false);
		if (function_exists('mb_language')) {
			if (mb_language()) {
				self::assign('mb_lang',mb_language());
				if ((mb_language() == "uni") or (mb_language() == "neutral")) {
					self::assign('mb_lang_ok',true);				
				}
			} else {
				self::assign('mb_lang','unset');
			}
		} else {
			self::assign('mb_lang','no multibyte support');
		}
		self::assign('mb_language',ini_get('mbstring.language'));
		self::assign('mb_encoding',ini_get('mbstring.internal_encoding'));
		self::assign('mb_input',ini_get('mbstring.http_input'));
		self::assign('mb_ouput',ini_get('mbstring.http_output'));
		self::assign('mb_overload',ini_get('mbstring.func_overload'));
		
		/* Tests */
		self::assign('oe',ABSTRACT_OE);
		self::assign('oe_urldecode',urldecode('%C3%B6'));
		self::assign('oe_urldecode2',urldecode('%F6'));
		self::assign('oe_urlencode',urlencode(ABSTRACT_OE));
		self::assign('oe_htmlentity',htmlentities(ABSTRACT_OE,ENT_NOQUOTES,'UTF-8'));
		self::assign('oe_htmlentity_print',preg_replace("/(&#)+/","&amp;#",self::get('oe_htmlentity')));  
		self::assign('oe_htmlentitydecode',html_entity_decode('&ouml;',ENT_NOQUOTES,'UTF-8'));
		
		self::assign('strlen',strlen(ABSTRACT_OE));
		self::assign('strlen_mb',mb_strlen(ABSTRACT_OE));
		self::assign('strlen_k',mb_strlen(ABSTRACT_OE));
		self::assign('strlen2',strlen(urldecode('%F6')));
		self::assign('strlen_mb2',mb_strlen(urldecode('%F6')));
		#var_dump(self::_controller->getAssigns());
		#die();
	}
	
	
	function assign($name, $val)
	{
		self::$assign[$name] = $val;
		//echo $name." = ".$val."<br>";
	}
	
	
	public static function get($name,$from_group='') 
	{
		$x = 1;
		if (self::$assign === null) {
			self::checkUtf8();
		}
		if (empty($from_group)) {
			if (isset(self::$assign[$name])) {
				return self::$assign[$name];
			}
			// fallback to data
			if (isset(self::$assign['data'][$name])) {
				return self::$assign['data'][$name];
			}
		} else {
			if (isset(self::$assign[$from_group][$name])) {
				return self::$assign[$from_group][$name];
			}
		}
		return '';
	}
	
	public static function info($name)
	{
		switch ($name) {
			case "character_set_server": 
				$info = "The server's default character set. Set the following in my.cnf<br> [mysql]<br> default-character-set=utf8<br><br>(defaults to latin1)";
				break;
			case "character_set_database": 
				$info = "The character set used for returning query results such as result sets or error messages to the client. ";
				break;
			case "character_set_connection": 
				$info = "The character set used for literals that do not have a character set introducer and for number-to-string conversion.";
				break;
			case "character_set_client": 
				$info = "The character set for statements that arrive from the client. The session value of this variable is set using the character set requested by the client when the client connects to the server. (Many clients support a --default-character-set option to enable this character set to be specified explicitly.";
				$info .= "The global value of the variable is used to set the session value in cases when the client-requested value is unknown or not available, or the server is configured to ignore client requests: ";
				$info .= "<ul><li>The client is from a version of MySQL older than 4.1 and does not request character set</li><li>The client requests a character set not known to the server.</li><li>mysqld was started with the --skip-character-set-client-handshake option, which causes it to ignore client character set configuration.</li></ul>";
				break;
			case "character_set_results": 
				$info = "The character set used for returning query results such as result sets or error messages to the client. ";
				break;
			case "character_set_system": 
				$info = "The character set used by the server for storing identifiers. The value is always utf8.";
				break;
			case "character_set_filesystem": 
				$info = "The file system character set. This variable is used to interpret string literals that refer to file names, such as in the LOAD DATA INFILE and SELECT ... INTO OUTFILE statements and the LOAD_FILE() function. Such file names are converted from character_set_client to character_set_filesystem before the file opening attempt occurs. The default value is binary, which means that no conversion occurs. For systems on which multi-byte file names are permitted, a different value may be more appropriate. For example, if the system represents file names using UTF-8, set character_set_filesystem to 'utf8'.";
				break;
			case "collation_connection": 
				$info = "The collation of the connection character set.";
				break;
			case "collation_database": 
				$info = "The collation used by the default database. The server sets this variable whenever the default database changes. If there is no default database, the variable has the same value as collation_server";
				break;
			case "collation_server": 
				$info = "The server's default collation.";
				break;
			case "use_utf8": 
				$info = "Will try to enable UTF8 via script:<br><ul><li>MySQL Query: SET NAMES 'utf8'</li><li>PHP ini_set('default_charset', 'utf8')</li></ul>";
				break;
			case "default_charset":
				$info = "Add the following variable to php.ini:<br>default_charset = utf8";
				break;
			default: $info = "";
		}
		return $info;
	}
	
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<script type="text/javascript">
<!--
function info(id){el=document.getElementById(id);var display=el.style.display?'':'none';el.style.display=display;}
//-->
</script>

<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<style>
	body {
		background: #EBEBEB;
		text-align: center;
		
	}
	
	.rahmen {
		margin: 100px auto;
		width: 760px;
		text-align: left;
	}
	
	.bild {
		float:left;
		width: 100px;
		padding: 10px;
	}
	.tabelle {
		padding: 10px;
		border: 0;
		cellspacing:10px;
	}
	.tabelle td {
		padding-left: 20px;
	}
	
	.infotext {
		font-size: 12px;
		margin-left: 50px;
		background-color: white;
		padding:10px;
	}
	
</style>

</head>
<body>
<div class="rahmen">
	<div class="bild"><a href="http://www.redsparkframework.de" target="_top">
		<img src="http://www.redsparkframework.de/templates/redsparkframework/img/layout/redsparklogo.png" border="0" width="96" height="96" align="left" alt="Powerded by Redspark Framework"></a>
	</div>

<br></br><h1>UTF8 Compatibility Check</h1>
<h3> provided by RedSpark Framework</h3>
<div style="clear: both"></div>
<table class="tabelle">
<form name="dbhost" method="post" action="">
<tr><td width="50%">Hostname:</td><td width="50%"><input type="text" size="10" name="host" value="<?php echo $_REQUEST["host"];?>"/></td></tr>
<!--<tr><td>Database:</td><td><input type="text" size="10" name="db" value="<?php echo $_REQUEST["db"];?>"/></td></tr>-->
<tr><td>Username:</td><td><input type="text" size="10" name="user" value="<?php echo $_REQUEST["user"];?>"/></td></tr>
<tr><td>Password:</td><td><input type="password" size="10" name="pwd" value="<?php echo $_REQUEST["pwd"];?>"/></td></tr>
<tr><td><img src="http://www.redsparkframework.de/apidoc/media/images/Index.png" onclick="info('use_utf8')"> Set UTF-8 within script</td><td><input type="checkbox" name="useUtf8" <?php if ($_REQUEST["useUtf8"]) { echo "checked"; } ?>/></td></tr>
<tr id="use_utf8" style="display:none"><td colspan="3" class="infotext"><?php echo Utf8Check::info('use_utf8');?></td></tr>
<tr><td></td><td colspan="2"><br></br><input type="submit"/></td></tr>
</form>
<tr><td colspan="3" style="color:red"><br></br>
<?php Utf8Check::initDb(); ?>
</td></tr>
<tr><th>DB Server Character Set</th><th> (utf8)</th></tr>
<tr><td><img src="http://www.redsparkframework.de/apidoc/media/images/Index.png" onclick="info('character_set_server')"> character_set_server</td>	<td><span style="color:<?php Utf8Check::get('character_set_server','bool') ? $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('character_set_server','data') ?></span></td></tr>
<tr id="character_set_server" style="display:none"><td colspan="3" class="infotext"><?php echo Utf8Check::info('character_set_server');?></td></tr>
<tr><td><img src="http://www.redsparkframework.de/apidoc/media/images/Index.png" onclick="info('character_set_database')"> character_set_database</td>	<td><span style="color:<?php Utf8Check::get('character_set_database','bool') ? $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('character_set_database','data') ?></span></td></tr>
<tr id="character_set_database" style="display:none"><td colspan="3" class="infotext"><?php echo Utf8Check::info('character_set_database');?></td></tr>
<tr><td><img src="http://www.redsparkframework.de/apidoc/media/images/Index.png" onclick="info('character_set_connection')"> character_set_connection</td>	<td><span style="color:<?php Utf8Check::get('character_set_connection','bool') ? $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('character_set_connection','data') ?></span></td></tr>
<tr id="character_set_connection" style="display:none"><td colspan="3" class="infotext"><?php echo Utf8Check::info('character_set_connection');?></td></tr>
<tr><td><img src="http://www.redsparkframework.de/apidoc/media/images/Index.png" onclick="info('character_set_client')"> character_set_client</td>	<td><span style="color:<?php Utf8Check::get('character_set_client','bool') ? $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('character_set_client','data') ?></span></td></tr>
<tr id="character_set_client" style="display:none"><td colspan="3" class="infotext"><?php echo Utf8Check::info('character_set_client');?></td></tr>
<tr><td><img src="http://www.redsparkframework.de/apidoc/media/images/Index.png" onclick="info('character_set_results')"> character_set_results</td>	<td><span style="color:<?php Utf8Check::get('character_set_results','bool') ? $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('character_set_results','data') ?></span></td></tr>
<tr id="character_set_results" style="display:none"><td colspan="3" class="infotext"><?php echo Utf8Check::info('character_set_results');?></td></tr>
<tr><td><img src="http://www.redsparkframework.de/apidoc/media/images/Index.png" onclick="info('character_set_system')"> character_set_system</td>	<td><span style="color:<?php Utf8Check::get('character_set_system','bool') ? $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('character_set_system','data') ?></span></td></tr>
<tr id="character_set_system" style="display:none"><td colspan="3" class="infotext"><?php echo Utf8Check::info('character_set_system');?></td></tr>
<tr><td><img src="http://www.redsparkframework.de/apidoc/media/images/Index.png" onclick="info('character_set_filesystem')"> character_set_filesystem</td><td><span style="color:<?php Utf8Check::get('character_set_filesystem','bool') ? $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('character_set_filesystem','data') ?></span></td></tr>
<tr id="character_set_filesystem" style="display:none"><td colspan="3" class="infotext"><?php echo Utf8Check::info('character_set_filesystem');?></td></tr>

<tr><th>DB Server Collation</th><th> (utf8_general_ci)</th></tr>

<tr><td><img src="http://www.redsparkframework.de/apidoc/media/images/Index.png" onclick="info('collation_server')"> collation_server</td>		<td><span style="color:<?php Utf8Check::get('collation_server','bool') ? $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('collation_server','data') ?></span></td><td></td></tr>
<tr id="collation_server" style="display:none"><td colspan="3" class="infotext"><?php echo Utf8Check::info('collation_server');?></td></tr>

<tr><td><img src="http://www.redsparkframework.de/apidoc/media/images/Index.png" onclick="info('collation_database')"> collation_database</td>		<td><span style="color:<?php Utf8Check::get('collation_database','bool') ? $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('collation_database','data') ?></span></td><td></td></tr>
<tr id="collation_database" style="display:none"><td colspan="3" class="infotext"><?php echo Utf8Check::info('collation_database');?></td></tr>

<tr><td><img src="http://www.redsparkframework.de/apidoc/media/images/Index.png" onclick="info('collation_connection')"> collation_connection</td>	<td><span style="color:<?php Utf8Check::get('collation_connection','bool') ? $tc="green" : $tc="red"; echo $tc; ?>"><?php echo Utf8Check::get('collation_connection','data') ?></span></td><td></td></tr>
<tr id="collation_connection" style="display:none"><td colspan="3" class="infotext"><?php echo Utf8Check::info('collation_connection');?></td></tr>

<tr><th>DB Tests</th></tr>
<tr><td>Query 'ö' = CHAR(ORD)</td>	<td colspan="2"><span style="color:<?php Utf8Check::get('queryoe1') == Utf8Check::get('queryoe2') ?  $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('queryoe1')." = ".Utf8Check::get('queryoe2') ?></span></td></tr>
<tr><td>Query 'ö' = CHAR(ASCII)</td><td colspan="2"><span style="color:<?php Utf8Check::get('queryoe1') == Utf8Check::get('queryoe3') ?  $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('queryoe1')." = ".Utf8Check::get('queryoe3') ?></span></td></tr>
<tr><td>Query ORD('ö') = 50102</td>			<td><span style="color:<?php Utf8Check::get('queryord') == 50102 ?  $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('queryord') ?></span></td></tr>
<tr><td>Query ASCII('ö') = 246</td>		<td><span style="color:<?php Utf8Check::get('queryascii') == 246 ?  $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('queryascii') ?></span></td></tr>


<tr><th><br><br>PHP</th></tr>
<tr><td><img src="http://www.redsparkframework.de/apidoc/media/images/Index.png" onclick="info('default_charset')"> default_charset</td>		<td><span style="color:<?php Utf8Check::get('default_charset_ok') ? $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('default_charset'); ?></span></td><td>(UTF8)</td></tr>
<tr id="default_charset" style="display:none"><td colspan="3" class="infotext"><?php echo Utf8Check::info('default_charset');?></td></tr>
<tr><td>mb_language</td>			<td><span style="color:<?php Utf8Check::get('mb_lang_ok') ? $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('mb_lang'); ?></span></td><td>(uni)</td></tr>
<!--
<tr><td>mbstring.language</td>		<td colspan="2"><?php echo Utf8Check::get('mb_language'); ?></td></tr>
<tr><td>mbstring.internal_encoding</td><td><span style="color:<?php Utf8Check::get('mb_encoding') == "UTF-8" ? $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('mb_encoding'); ?></span></td><td>(UTF-8)</td></tr>
<tr><td>mbstring.http_input</td>	<td colspan="2"><?php echo Utf8Check::get('mb_input'); ?></td></tr>
<tr><td>mbstring.http_output</td>	<td colspan="2"><?php echo Utf8Check::get('mb_ouput'); ?></td></tr>
-->

<tr><th>PHP Tests</th></tr>
<tr><td>URLDecode (%C3%B6 -&gt; <?php echo Utf8Check::get('oe'); ?>)</td>	<td colspan="2"><span style="color:<?php Utf8Check::get('oe_urldecode') == Utf8Check::get('oe') ? $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('oe_urldecode'); ?></span></td></tr>
<tr><td>URLEncode (<?php echo Utf8Check::get('oe'); ?> -&gt; %C3%B6)</td>	<td colspan="2"><span style="color:<?php Utf8Check::get('oe_urlencode') == "%C3%B6" ? $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('oe_urlencode'); ?></span></td></tr>
<tr><td>HTML Entity Encode (<?php echo Utf8Check::get('oe'); ?> -&gt; &amp;ouml;)</td>	<td colspan="2"><span style="color:<?php Utf8Check::get('oe_htmlentity') == "&ouml;" ? $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('oe_htmlentity'); ?></span></td></tr>
<tr><td>HTML Entity Decode (&amp;ouml; -&gt; <?php echo Utf8Check::get('oe'); ?>)</td>	<td colspan="2"><span style="color:<?php Utf8Check::get('oe_htmlentitydecode') == Utf8Check::get('oe') ? $tc="green" : $tc="red"; echo $tc ?>"><?php echo Utf8Check::get('oe_htmlentitydecode'); ?></td></tr>



</table>


</div>
</body>
</html>