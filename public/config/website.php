<?php

function adNameExists($name,$website_id) {
  global $dbhost,$dbname,$dbuser,$dbpass,$lang;
  $conn=mysql_connect($dbhost,$dbuser,$dbpass);
  if(!$conn)
    return $lang['CONNECT_ERROR'];
  if(!mysql_select_db($dbname,$conn)) {
    mysql_close();
    return $lang['DBSELECT_ERROR'];
  }
  $query="SELECT * FROM ads WHERE name='".mysql_real_escape_string($name)."' AND website_id='".mysql_real_escape_string($website_id)."'";
  $res=mysql_query($query);
  if($res===false)
    return $lang['QUERY_ERROR'];
  if(mysql_num_rows($res))
    return true;
  return false;
}

function adNameValid($name,$website_id) {
  global $lang;
  if($name==='')
    return $lang['AD_NAME_EMPTY'];
  if(!isInRange($name,2,50))
    return $lang['AD_NAME_RANGE'];
  $tmp=adNameExists($name,$website_id);
  if($tmp===true)
    return $lang['AD_NAME_EXISTS'];
  return true;
}

class Website {
  public $status=false;
  private $errors=array();
  public $id;
  public $user_id;
  public $url;
  public $associate_tag;
  public $access_key;
  public $private_key;

  /* function __construct($url=NULL,$user_id=NULL) { */
  /*   if($url!=NULL or $user_id!=NULL) { */
  /*     $url=trim($url); */
  /*     $tmp=urlValid($url,$user_id); */
  /*     if($tmp!==true) { */
  /*       $this->status=false; */
  /*       $this->errors[]=$tmp; */
  /*     } */
  /*     $this->url=$url; */
  /*     $this->user_id=$user_id; */
  /*     if(count($this->errors)==0)     */
  /*       $this->status=true; */
  /*   } */
  /* } */

  function Constructor($url,$user_id) {
    $url=trim($url);
    $tmp=urlValid($url,$user_id);
    if($tmp!==true) {
      $this->status=false;
      $this->errors[]=$tmp;
      return false;
    }
    $this->url=$url;
    $this->user_id=$user_id;
    if(count($this->errors)==0)    
      $this->status=true;
    return true;
  }

  function fillIn($id) {
    global $dbhost,$dbname,$dbuser,$dbpass,$lang;
    $conn=mysql_connect($dbhost,$dbuser,$dbpass);
    if(!$conn) {
      $this->status=false;
      $this->errors[]="Could not connect do database";
      return false;
    }
    if(!mysql_select_db($dbname,$conn)) {
      $this->status=false;
      $this->errors[]="Could not connect do database";
      mysql_close();
      return false;
    }
    $id=mysql_real_escape_string($id);
    $query="SELECT * FROM websites WHERE id='".$id."'";
    $result=mysql_query($query);
    if(!$result or mysql_num_rows($result)==false) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return false;
    }
    $row=mysql_fetch_array($result);
    $user_id=isset($row['user_id']) ? $row['user_id'] : '';
    $url=isset($row['url']) ? $row['url'] : '';
    $associate_tag=isset($row['associate_tag']) ? $row['associate_tag'] : '0';
    $access_key=isset($row['access_key']) ? $row['access_key'] : '0';
    $private_key=isset($row['private_key']) ? $row['private_key'] : '0';
    $this->id=$id;
    $this->user_id=$user_id;
    $this->url=$url;
    $this->associate_tag=$associate_tag;
    $this->access_key=$access_key;
    $this->private_key=$private_key;
    $this->status=true;
  }

  function GetAds() {
    $ads=array();
    global $dbhost,$dbname,$dbuser,$dbpass,$lang;
    $conn=mysql_connect($dbhost,$dbuser,$dbpass);
    if(!$conn) {
      $this->status=false;
      $this->errors[]="Could not connect do database";
      return false;
    }
    if(!mysql_select_db($dbname,$conn)) {
      $this->status=false;
      $this->errors[]="Could not connect do database";
      mysql_close();
      return false;
    }
    $id=(isset($this->id))?mysql_real_escape_string($this->id):'';
    $query="SELECT * FROM ads WHERE website_id='".$id."'";
    $result=mysql_query($query);
    if(!$result or mysql_num_rows($result)==false) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return false;
    }
    if(mysql_num_rows($result)!=false) {
      while($row=mysql_fetch_array($result)) {
        $a=new Ad;
        $a->fillIn($row['id']);
        if($a->status)
          $ads[]=$a;
      }
    }
    return $ads;
  }

  function Register() { 
    global $dbhost,$dbname,$dbuser,$dbpass,$lang;
    $conn=mysql_connect($dbhost,$dbuser,$dbpass);
    if(!$conn) {
      $this->status=false;
      $this->errors[]="Could not connect do database";
      return false;
    }
    if(!mysql_select_db($dbname,$conn)) {
      $this->status=false;
      $this->errors[]="Could not connect do database";
      mysql_close();
      return false;
    }
    $url=mysql_real_escape_string($this->url);
    $user_id=mysql_real_escape_string($this->user_id);
    $query="SELECT associate_tag, access_key, private_key FROM users WHERE id='".$user_id."'";
    $result=mysql_query($query);
    if(!$result or mysql_num_rows($result)==false) {
      $this->status=false;
      $this->errors[]="Could not execute query1";
      mysql_close();
      return false;
    }
    $row=mysql_fetch_array($result);
    $associate_tag=isset($row['associate_tag']) ? $row['associate_tag'] : '0';
    $access_key=isset($row['access_key']) ? $row['access_key'] : '0';
    $private_key=isset($row['private_key']) ? $row['private_key'] : '0';
    $id=uniqid();
    $query="INSERT INTO websites (id,user_id,url,associate_tag,access_key,private_key) VALUES ('$id','$user_id','$url','$associate_tag','$access_key','$private_key');";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query2";
      mysql_close();
      return false;
    }
    /* $query="SELECT id FROM websites WHERE user_id='".$user_id."' AND url='".$url."'"; */
    /* $result=mysql_query($query); */
    /* if(!$result or mysql_num_rows($result)==false) { */
    /*   $this->status=false; */
    /*   $this->errors[]="Could not execute query3"; */
    /*   mysql_close(); */
    /*   return false; */
    /* } */
    /* $row=mysql_fetch_array($result); */
    /* $id=isset($row['id']) ? $row['id'] : ' '; */
    $this->associate_tag=$associate_tag;
    $this->access_key=$access_key;
    $this->private_key=$private_key;
    $this->id=$id;
    return true;
  }

  function changeUrl($url) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $url=trim($url);
    $tmp=urlValid($url,$this->id);
    if($tmp!==true) {
      $this->status=false;
      $this->errors[]=$tmp;
      return;
    }
    $conn=mysql_connect($dbhost,$dbuser,$dbpass);
    if(!$conn) {
      $this->status=false;
      $this->errors[]="Could not connect do database";
      return;
    }
    if(!mysql_select_db($dbname,$conn)) {
      $this->status=false;
      $this->errors[]="Could not connect do database";
      mysql_close();
      return;
    }
    $query="UPDATE websites SET url = '$url' WHERE id = '$this->id'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->url=$url;
    $this->status=true;
  }

  function changeAssociateTag($associate_tag,$rec) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $associate_tag=trim($associate_tag);
    $tmp=associateTagValid($associate_tag);
    if($tmp!==true) {
      $this->status=false;
      $this->errors[]=$tmp;
      return;
    }
    $conn=mysql_connect($dbhost,$dbuser,$dbpass);
    if(!$conn) {
      $this->status=false;
      $this->errors[]="Could not connect do database";
      return;
    }
    if(!mysql_select_db($dbname,$conn)) {
      $this->status=false;
      $this->errors[]="Could not connect do database";
      mysql_close();
      return;
    }
    $query="UPDATE websites SET associate_tag = '$associate_tag' WHERE id = '$this->id'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    if(isset($rec) and $rec==true) {
      $query="UPDATE ads SET associate_tag = '$associate_tag' WHERE website_id = '$this->id'";
      $result=mysql_query($query);
      if(!$result) {
        $this->status=false;
        $this->errors[]="Could not execute query";
        mysql_close();
        return;
      }
    }
    $this->associate_tag=$associate_tag;
    $this->status=true;
  }

  function changeAccessKey($access_key,$rec) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $access_key=trim($access_key);
    $tmp=accessKeyValid($access_key);
    if($tmp!==true) {
      $this->status=false;
      $this->errors[]=$tmp;
      return;
    }
    $conn=mysql_connect($dbhost,$dbuser,$dbpass);
    if(!$conn) {
      $this->status=false;
      $this->errors[]="Could not connect do database";
      return;
    }
    if(!mysql_select_db($dbname,$conn)) {
      $this->status=false;
      $this->errors[]="Could not connect do database";
      mysql_close();
      return;
    }
    $query="UPDATE websites SET access_key = '$access_key' WHERE id = '$this->id'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    if(isset($rec) and $rec==true) {
      $query="UPDATE ads SET access_key = '$access_key' WHERE website_id = '$this->id'";
      $result=mysql_query($query);
      if(!$result) {
        $this->status=false;
        $this->errors[]="Could not execute query";
        mysql_close();
        return;
      }
    }
    $this->access_key=$access_key;
    $this->status=true;
  }

  function changePrivateKey($private_key,$rec) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $private_key=trim($private_key);
    $tmp=privateKeyValid($private_key);
    if($tmp!==true) {
      $this->status=false;
      $this->errors[]=$tmp;
      return;
    }
    $conn=mysql_connect($dbhost,$dbuser,$dbpass);
    if(!$conn) {
      $this->status=false;
      $this->errors[]="Could not connect do database";
      return;
    }
    if(!mysql_select_db($dbname,$conn)) {
      $this->status=false;
      $this->errors[]="Could not connect do database";
      mysql_close();
      return;
    }
    $query="UPDATE websites SET private_key = '$private_key' WHERE id = '$this->id'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    if(isset($rec) and $rec==true) {
      $query="UPDATE ads SET private_key = '$private_key' WHERE website_id = '$this->id'";
      $result=mysql_query($query);
      if(!$result) {
        $this->status=false;
        $this->errors[]="Could not execute query";
        mysql_close();
        return;
      }
    }
    $this->private_key=$private_key;
    $this->status=true;
  }

   function GetErrors() {
    $tmp=$this->errors;
    $this->errors=array();
    return $tmp;
  }

 

}

?>