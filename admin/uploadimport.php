<?php
require ('admin_header.php');
umask(0002);
if (!isset($_FILES['uploaded'])) {
	header("Location: uploaditems.php");
	break;
}

// Öffne Datenbank, mache ordentlichen Header, binde Stylesheets, Scripts ein
require ('includes/header.php');
// Endet mit </html>
require ('includes/design.php');
// macht das ganze Klickibunti, endet mit <div id="main"

echo "<table width=\"" . SRVYTBLWIDTH . "\">";
echo "<tr class=\"adminmessage\"><td>";

$target = "upload/";
$target = $target . basename( $_FILES['uploaded']['name']) ;

if (file_exists($target)) {
  rename($target,$target . "-overwritten-" . date('Y-m-d-H:m'));
  echo "Eine Datei mit gleichem Namen existierte schon und wurde unter " . $target . "-overwritten-" . date('Y-m-d-H:m') . " gesichert.<br />";
}
$ok=1;

$file_type=substr($_FILES['uploaded']['name'],strlen($_FILES['uploaded']['name'])-3,3);
/*
FIX: Maximale Größe und richtigen Dateityp kontrollieren!

$_FILES['userfile']['name']
// Der ursprüngliche Dateiname auf der Client Maschine. 

$_FILES['userfile']['type']
// Der Mime-Type der Datei, falls der Browser diese Information zur Verfügung gestellt hat. Ein Beispiel wäre "image/gif". 

$_FILES['userfile']['size']
// Die Größe der hochgeladenen Datei in Bytes. 

$_FILES['userfile']['tmp_name']
// Der temporäre Dateiname, unter dem die hochgeladene Datei auf dem Server gespeichert wurde. 

if ($uploaded_size > FILEUPLOADMAXSIZE)
{
echo "Die Datei ist zu groß. Bitte kontrollieren und gegebenenfalls Einstellungen höher setzen.<br>";
$ok=0;
}

if (!($uploaded_type=="text/csv")) {
echo "Datei ist keine csv. Es werden nur CSV-Dateien akzeptiert.<br>";
$ok=0;
}
*/

if(!move_uploaded_file($_FILES['uploaded']['tmp_name'], $target)) {
  echo "Sorry, es gab ein Problem bei dem Upload.<br />";
} else {
  echo "Datei $target wurde hochgeladen<br />";
}


// Leere / erstelle items
if (!table_exists(ITEMSTABLE, $database) && $ok!=0) {
// FIX Hier limitieren wir auf 14 MC-Alternativen! Anpassung ist notwendig, wenn mehr oder weniger gegeben werden.
		$query = "CREATE TABLE IF NOT EXISTS `".ITEMSTABLE."` (
  `id` int(11) NOT NULL,
  `variablenname` varchar(100) NOT NULL,
  `wortlaut` text NOT NULL,
  `altwortlautbasedon` varchar(150) NOT NULL,
  `altwortlaut` text NOT NULL,
  `typ` varchar(100) NOT NULL,
  `antwortformatanzahl` int(100) NOT NULL,
  `ratinguntererpol` text NOT NULL,
  `ratingobererpol` text NOT NULL,
  `MCalt1` text NOT NULL,
  `MCalt2` text NOT NULL,
  `MCalt3` text NOT NULL,
  `MCalt4` text NOT NULL,
  `MCalt5` text NOT NULL,
  `MCalt6` text NOT NULL,
  `MCalt7` text NOT NULL,
  `MCalt8` text NOT NULL,
  `MCalt9` text NOT NULL,
  `MCalt10` text NOT NULL,
  `MCalt11` text NOT NULL,
  `MCalt12` text NOT NULL,
  `MCalt13` text NOT NULL,
  `MCalt14` text NOT NULL,
  `Teil` varchar(255) NOT NULL,
  `relevant` char(1) NOT NULL,
  `skipif` text NOT NULL,
  `special` varchar(100) NOT NULL,
  `rand` varchar(10) NOT NULL,
  `study` varchar(100) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
	mysql_query($query);
	if(DEBUG) {
		echo $query;	
		echo mysql_error();
	}
} elseif ($ok!=0) {
	$query = "truncate ".ITEMSTABLE.";";
	mysql_query($query);
	echo "Existierende Itemtabelle wurde geleert.<br />";
	if(DEBUG) {
		echo $query;	
		echo mysql_error();
	}
} elseif ($ok==0) {
	echo "Es wurden keine Änderungen an der Datenbank vorgenommen.";
	// $ok muss 0 gewesen sein
}

// Du hast nun entweder ein $ok =1 oder 0 und auf jeden Fall eine existierende, leere Itemtabelle

if($file_type=="csv") {

$import = realpath($target);
$file = fopen($import,"r");
fgetcsv($file,0,";");
echo "<br/>";
while( !feof($file) ) {
  // get a line
  $a = fgetcsv($file, 0, ";");
  // get our field
  $skipif = $a[25];
  // is it empty?
  if( $skipif != "" ) {
    // is it valid?
    $val = json_decode($skipif, true);
    if( is_null($val) ) {
      echo $a[0]." - ".$a[1]." cannot be decoded: check the skipif!<br/>";
    }
  }
}
fclose($file);
require("includes/csvreader.php");
if ($ok==1) {
  $reader = new CSVReader();
  $data = $reader->parse_file($import);
	
  $sql = array(); 
  foreach($data as $row) {
    /*  id
        variablenname
        wortlaut
        altwortlautbasedon
        altwortlaut
        typ
        antwortformatanzahl
        ratinguntererpol
        ratingobererpol
        MCalt1
        MCalt2
        MCalt3
        MCalt4
        MCalt5
        MCalt6
        MCalt7
        MCalt8
        MCalt9
        MCalt10
        MCalt11
        MCalt12
        MCalt13
        MCalt14
        Teil
        relevant
        skipif
        special
        rand
        study
    */
    $sql[] = "(\"". implode("\",\"",array(mysql_real_escape_string($row['id']), mysql_real_escape_string($row['variablenname']), mysql_real_escape_string($row['wortlaut']), mysql_real_escape_string($row['altwortlautbasedon']), mysql_real_escape_string($row['altwortlaut']), mysql_real_escape_string($row['typ']), mysql_real_escape_string($row['antwortformatanzahl']), mysql_real_escape_string($row['ratinguntererpol']), mysql_real_escape_string($row['ratingobererpol']), mysql_real_escape_string($row['MCalt1']), mysql_real_escape_string($row['MCalt2']), mysql_real_escape_string($row['MCalt3']), mysql_real_escape_string($row['MCalt4']), mysql_real_escape_string($row['MCalt5']), mysql_real_escape_string($row['MCalt6']), mysql_real_escape_string($row['MCalt7']), mysql_real_escape_string($row['MCalt8']), mysql_real_escape_string($row['MCalt9']), mysql_real_escape_string($row['MCalt10']), mysql_real_escape_string($row['MCalt11']), mysql_real_escape_string($row['MCalt12']), mysql_real_escape_string($row['MCalt13']), mysql_real_escape_string($row['MCalt14']), mysql_real_escape_string($row['Teil']), mysql_real_escape_string($row['relevant']), mysql_real_escape_string($row['skipif']), mysql_real_escape_string($row['special']), mysql_real_escape_string($row['rand']), mysql_real_escape_string($row['study']) )) ."\")";
  }
  $query = 'INSERT INTO `'.ITEMSTABLE.'` (id,
	variablenname,
	wortlaut,
	altwortlautbasedon,
	altwortlaut,
	typ,
	antwortformatanzahl,
	ratinguntererpol,
	ratingobererpol,
	MCalt1, MCalt2,	MCalt3,	MCalt4,	MCalt5,	MCalt6,	MCalt7,	MCalt8,	MCalt9,	MCalt10, MCalt11,	MCalt12,	MCalt13,	MCalt14,
	Teil,
	relevant,
	skipif,
	special,
	rand,
	study) VALUES '.implode(',', $sql);

  // load data local infile needs privileges we don't have on www2	
  //	$query = "LOAD DATA LOCAL INFILE '$import' REPLACE INTO TABLE `".ITEMSTABLE."` CHARACTER SET utf8 FIELDS TERMINATED BY ';' OPTIONALLY ENCLOSED BY '\"' IGNORE 1 LINES;";
  //	mysql_query($query);
  if(DEBUG) {
    echo $query;	
    echo mysql_error();
  }
  if (mysql_query($query)) {
    echo "Datei wurde erfolgreich importiert.";
  }
  echo mysql_error();

}
} else if($file_type=="ods") {
  
  require_once "SpreadsheetReaderFactory.php";
  $spreadsheetsFilePath=$target; 
  $reader=SpreadsheetReaderFactory::reader($spreadsheetsFilePath);
  $sheets=$reader->read($spreadsheetsFilePath);

  foreach($sheets as $sheet) {
    foreach($sheet as $sh) {
      $skipif=$sh[25];
      if( $skipif != "" ) {
        // is it valid?
        $val = json_decode($skipif, true);
        if( is_null($val) ) {
          echo $sh[0]." - ".$sh[1]." cannot be decoded: check the skipif!<br/>";
        }
      }
    } 
  }

  if($ok==1) {
    $sql=array();
    $cnt=0;
    foreach($sheets as $sheet) {
      foreach($sheet as $row) {
        if($cnt!=0) 
          $sql[] = "(\"". implode("\",\"",array(mysql_real_escape_string($row[0]), mysql_real_escape_string($row[1]), mysql_real_escape_string($row[2]), mysql_real_escape_string($row[3]), mysql_real_escape_string($row[4]), mysql_real_escape_string($row[5]), mysql_real_escape_string($row[6]), mysql_real_escape_string($row[7]), mysql_real_escape_string($row[8]), mysql_real_escape_string($row[9]), mysql_real_escape_string($row[10]), mysql_real_escape_string($row[11]), mysql_real_escape_string($row[12]), mysql_real_escape_string($row[13]), mysql_real_escape_string($row[14]), mysql_real_escape_string($row[15]), mysql_real_escape_string($row[16]), mysql_real_escape_string($row[17]), mysql_real_escape_string($row[18]), mysql_real_escape_string($row[19]), mysql_real_escape_string($row[20]), mysql_real_escape_string($row[21]), mysql_real_escape_string($row[22]), mysql_real_escape_string($row[23]), mysql_real_escape_string($row[24]), mysql_real_escape_string($row[25]), mysql_real_escape_string($row[26]), mysql_real_escape_string($row[27]), mysql_real_escape_string($row[28]) )) ."\")";
        else 
          $cnt=1;
      }    
    }
    $query = 'INSERT INTO `'.ITEMSTABLE.'` (id,
        variablenname,
        wortlaut,
        altwortlautbasedon,
        altwortlaut,
        typ,
        antwortformatanzahl,
        ratinguntererpol,
        ratingobererpol,
        MCalt1, MCalt2,	MCalt3,	MCalt4,	MCalt5,	MCalt6,	MCalt7,	MCalt8,	MCalt9,	MCalt10, MCalt11,	MCalt12,	MCalt13,	MCalt14,
        Teil,
        relevant,
        skipif,
        special,
        rand,
        study) VALUES '.implode(',', $sql);

    // load data local infile needs privileges we don't have on www2
    //	$query = "LOAD DATA LOCAL INFILE '$import' REPLACE INTO TABLE `".ITEMSTABLE."` CHARACTER SET utf8 FIELDS TERMINATED BY ';' OPTIONALLY ENCLOSED BY '\"' IGNORE 1 LINES;";
    //	mysql_query($query);
    if(DEBUG) {
      echo $query;
      echo mysql_error();
    }
    if (mysql_query($query)) {
      echo "Datei wurde erfolgreich importiert.";
    }
    echo mysql_error();
  }
  
}

echo "</td></tr><tr class=\"odd\"><td><form action=\"index.php\"><input type=\"submit\" value=\"Weiter\"></form></td></tr></table>";

// schließe main-div
echo "</div>\n";
// binde Navigation ein
require ('includes/navigation.php');
// schließe Datenbank-Verbindung, füge bei Bedarf Analytics ein
require('includes/footer.php');

?>