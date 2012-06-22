<?php


class User {

  public $status=false;
  private $errors=array();
  public $id;
  public $fname;
  public $lname;
  public $email;
  public $password;
  public $default_language;
  public $street;
  public $address2;
  public $city;
  public $state;
  public $postal;
  public $country;
  public $uid;
  public $active;
  public $email_verified;
  public $usage_model;
  public $associate_tag;
  public $access_key;
  public $private_key;
  public $bank_name;
  public $blz;
  public $kontonummer;
  public $anonymous=false;
  public $vpncode;
  public $bday;
  private $completed_studies=array();
  private $started_studies=array();


  function EligibleForStudyRun($study,$run) { 
    if(!is_object($study) or !is_object($run))
      return false;
    if(!$this->EligibleForRun($run))
      return false;
    if($study->id==$run->getFirstStudyId())
      return true;
    $prev_studies=$run->getPrevStudies($study);
    if(!$prev_studies)
      return false;
    foreach($prev_studies as $ps) {
      if(!$this->userHasCompletedStudy($ps))
        return false;
    }
    return true;
  }

  function userHasCompletedStudy($study) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    if(!isset($study))
      return false;
    if(!$this->anonymous) {   
      $conn=mysql_connect($dbhost,$dbuser,$dbpass);
      if(!$conn)
        return false;
      if(!mysql_select_db($dbname,$conn)) {
        mysql_close();
        return false;
      }
      $query="SELECT * FROM users_studies where user_id='".mysql_real_escape_string($this->id)."' AND study_id='".mysql_real_escape_string($study->id)."' AND completed = 1";
      $result=mysql_query($query);
      if(!$result or !mysql_num_rows($result) or mysql_num_rows($result)!=1) {
        mysql_close();
        return false;
      }
      mysql_close();
    }
    foreach($this->completed_studies as $cs) {
      if($cs==$study->id)
        return true;
    }
    return false;
  }

  function userCompletedStudy($study) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    if(!isset($study))
      return false;
    $sid=$study->id;
    foreach($this->completed_studies as $cs) {
      if($cs==$sid)
        return true;
    }
    foreach($this->started_studies as $ss) {
      if($ss==$sid) {     
        if(!$this->anonymous) {
          $conn=mysql_connect($dbhost,$dbuser,$dbpass);
          if(!$conn)
            return false;
          if(!mysql_select_db($dbname,$conn)) {
            mysql_close();
            return false;
          }
          $query="UPDATE users_studies SET completed = 1 WHERE study_id = '$sid' AND user_id = '$this->id'";
          $result=mysql_query($query);
          if(!$result) {
            mysql_close();
            return false;
          }
        }
        $this->completed_studies[]=$sid;
        return true;
      }
    }
    return false;
  }

  function userStartedStudy($study) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    if(!isset($study))
      return false;
    $sid=$study->id;
    foreach($this->completed_studies as $cs) {
      if($cs==$sid)
        return false;
    }
    foreach($this->started_studies as $ss) {
      if($ss==$sid)
        return true;
    }
    if(!$this->anonymous) {
      $conn=mysql_connect($dbhost,$dbuser,$dbpass);
      if(!$conn)
        return false;
      if(!mysql_select_db($dbname,$conn)) {
        mysql_close();
        return false;
      }
      $id=uniqid();
      $query="INSERT INTO users_studies (id,user_id,study_id) VALUES ('$id','$this->id','$sid');";
      $result=mysql_query($query);
      if(!$result) {
        mysql_close();
        return false;
      }
    }
    $this->started_studies[]=$sid;
    return true;
  }

  function getUsersStudies() {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $conn=mysql_connect($dbhost,$dbuser,$dbpass);
    if(!$conn)
      return false;
    if(!mysql_select_db($dbname,$conn)) {
      mysql_close();
      return false;
    }
    $query="SELECT * FROM users_studies where user_id='".mysql_real_escape_string($this->id)."' AND completed = 1";
    $result=mysql_query($query);
    if(!$result or mysql_num_rows($result)===false) {
      mysql_close();
      return false;
    }
    while($row=mysql_fetch_array($result)) {
      $this->completed_studies[]=$row['study_id'];
    }
    $query="SELECT * FROM users_studies where user_id='".mysql_real_escape_string($this->id)."' AND completed = 0";
    $result=mysql_query($query);
    if(!$result or mysql_num_rows($result)===false) {
      mysql_close();
      return false;
    }
    while($row=mysql_fetch_array($result)) {
      $this->started_studies[]=$row['study_id'];
    }
    return true;    
  }

  function EligibleForStudy($study) {
    $this->status=true;
    if(!isset($study) or !is_object($study)) {
      $this->errors[]="study is no object. something is wrong";
      $this->status=false;
      return false;
    }
    if(!$study->public) {
      $this->errors[]="Diese Studie wurde noch nicht ver&ouml;ffentlicht";
      $this->status=false;
    }
    if($study->registered_req and $this->anonymous==true) {
      $this->errors[]="Sie m&uuml;ssen registriert sein um an dieser Studie teilzunehmen.";
      $this->status=false;
    }
    return $this->status;
  }

  function EligibleForRun($run) {
    $this->status=true;
    if(!isset($run) or !is_object($run)) {
      $this->errors[]="run is no object. something is wrong";
      $this->status=false;
      return false;
    }
    if(!$run->public) {
      $this->errors[]="Diese Studie wurde noch nicht ver&ouml;ffentlicht";
      $this->status=false;
    }
    if($run->registered_req and $this->anonymous==true) {
      $this->errors[]="Sie m&uuml;ssen registriert sein um an dieser Studie teilzunehmen.";
      $this->status=false;
    }
    return $this->status;
  }

  function anonymous() {
    $this->email="anonymous@anonymous.anonymous";
    $this->id="000";
    $this->password="anonymous";
    $this->vpncode=generate_vpncode();
    $this->anonymous=true;
    $this->status=true;    
  }

  function login($email,$password) {
    global $available_languages;
    global $dbhost,$dbname,$dbuser,$dbpass;
    $conn=mysql_connect($dbhost,$dbuser,$dbpass);
    if(!$conn) {
      $this->errors[]="Could not connect do database";
      return;
    }
    if(!mysql_select_db($dbname,$conn)) {
      $this->errors[]="Could not connect do database";
      mysql_close();
      return;
    }
    $query="SELECT * FROM users ";
    $query.="WHERE email='$email'";
    $result=mysql_query($query);
    if($result===false) {
      $this->errors[]="Query Error";
      mysql_close();
      return;
    }
    if(mysql_num_rows($result)===false) {
      $this->errors[]="Email/Password combiantion is not corect1";
      mysql_close();
      return;
    } 
    $row=mysql_fetch_array($result);
    if(!isset($row['password'])) {
      $this->errors[]="Email/Password combiantion is not corect0";
      mysql_close();
      return;
    }
    $user_pwd=$row['password'];
    $secure_pwd=generateHash($password,$user_pwd);
    if($secure_pwd!==$user_pwd) {
      $this->errors[]="Email/Password combiantion is not corect2";
      mysql_close();
      return;
    }

    /* if(!isset($row['email_verified']) or $row['email_verified']==false) { */
    /*   $this->errors[]="This Accounts email address has not been verified."; */
    /*   mysql_close(); */
    /*   return; */
    /* } */
    /* if(!isset($row['active']) or $row['active']==false) { */
    /*   $this->errors[]="This account has not been activated."; */
    /*   mysql_close(); */
    /*   return; */
    /* } */

    /* $this->errors[]=$this->getUsersStudies(); */
    /* return; */

    if(!$this->getUsersStudies()) {
      $this->errors[]="Could not get users studies";
      mysql_close();
      return;
    }
    $id=isset($row['id']) ? $row['id'] : '';
    $vpncode=isset($row['vpncode']) ? $row['vpncode'] : '';
    $this->email=$email;
    $this->id=$id;
    $this->vpncode=$vpncode;
    $this->password=$user_pwd;
    $this->anonymous=false;
    $this->status=true;
  }

  function fillIn($id) {
    global $available_languages;
    global $dbhost,$dbname,$dbuser,$dbpass;
    $conn=mysql_connect($dbhost,$dbuser,$dbpass);
    if(!$conn) {
      $this->errors[]="Could not connect do database";
      return;
    }
    if(!mysql_select_db($dbname,$conn)) {
      $this->errors[]="Could not connect do database";
      mysql_close();
      return;
    }
    $query="SELECT * FROM users ";
    $query.="WHERE id='$id'";
    $result=mysql_query($query);
    if($result===false) {
      $this->errors[]="Query Error";
      mysql_close();
      return;
    }
    if(mysql_num_rows($result)===false) {
      $this->errors[]="Invalid ID";
      mysql_close();
      return;
    }
    $row=mysql_fetch_array($result);
    $fname=isset($row['fname']) ? $row['fname'] : '';
    $lname=isset($row['lname']) ? $row['lname'] : '';
    $street=isset($row['street']) ? $row['street'] : '';
    $address2=isset($row['address2']) ? $row['address2'] : '';
    $city=isset($row['city']) ? $row['city'] : '';
    $state=isset($row['state']) ? $row['state'] : '';
    $postal=isset($row['postal']) ? $row['postal'] : '';
    $country=isset($row['country']) ? $row['country'] : '';
    $uid=isset($row['uid']) ? $row['uid'] : '';    
    $id=isset($row['id']) ? $row['id'] : '';    
    $vpncode=isset($row['vpncode']) ? $row['vpncode'] : '';    
    $associate_tag=isset($row['associate_tag']) ? $row['associate_tag'] : '0';
    $usage_model=isset($row['usage_model']) ? $row['usage_model'] : '0';
    $access_key=isset($row['access_key']) ? $row['access_key'] : '0';
    $private_key=isset($row['private_key']) ? $row['private_key'] : '0';
    $bank_name=isset($row['bank_name']) ? $row['bank_name'] : '';
    $blz=isset($row['blz']) ? $row['blz'] : '';
    $kontonummer=isset($row['kontonummer']) ? $row['kontonummer'] : '';
    $this->email=$row['email'];
    $this->fname=$fname;
    $this->lname=$lname;
    $this->password=$row['password'];;
    $this->street=$street;
    $this->address2=$address2;
    $this->city=$city;
    $this->state=$state;
    $this->postal=$postal;
    $this->country=$country;
    $this->uid=$uid;
    $this->id=$id;
    $this->vpncode=$vpncode;
    $this->default_language=$row['default_language'];;
    /* if($row['active']==true) */
    /*   $this->active=true; */
    /* else */
    $this->active=$row['active'];
    $this->email_verified=$row['email_verified'];
    $this->associate_tag=$associate_tag;
    $this->usage_model=$usage_model;
    $this->access_key=$access_key;
    $this->private_key=$private_key;
    $this->bank_name=$bank_name;
    $this->blz=$blz;
    $this->kontonummer=$kontonummer;
    $this->status=true;
  }
  
  /* function addWebsite($url) { */
  /*   global $dbhost,$dbname,$dbuser,$dbpass; */
  /*   $url=trim($url); */
  /*   $tmp=urlValid($url,$this->id); */
  /*   if($tmp!==true) { */
  /*     $this->status=false; */
  /*     $this->errors[]=$tmp; */
  /*     return -1; */
  /*   } */
  /*   $conn=mysql_connect($dbhost,$dbuser,$dbpass); */
  /*   if(!$conn) { */
  /*     $this->status=false; */
  /*     $this->errors[]="Could not connect do database"; */
  /*     return -1; */
  /*   } */
  /*   if(!mysql_select_db($dbname,$conn)) { */
  /*     $this->status=false; */
  /*     $this->errors[]="Could not connect do database"; */
  /*     mysql_close(); */
  /*     return -1; */
  /*   } */
  /*   $url=mysql_real_escape_string($url); */
  /*   $query="INSERT INTO websites (user_id,url,associate_tag,access_key,private_key) VALUES ('$this->id','$url','$this->associate_tag','$this->access_key','$this->private_key');"; */
  /*   $result=mysql_query($query); */
  /*   if(!$result) { */
  /*     $this->status=false; */
  /*     $this->errors[]="Could not execute query"; */
  /*     mysql_close(); */
  /*     return -1; */
  /*   } */
    
  /*   $this->status=true; */
  /*   return $id; */
  /* } */

  function changeUsername($username) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $username=strtolower(trim($username));
    $tmp=userValid($username);
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
    $query="UPDATE users SET username = '$username' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->username=$username;
    $this->status=true;
  }

  function changeLname($lname) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $lname=trim($lname);
    $tmp=lnameValid($lname);
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
    $query="UPDATE users SET lname = '$lname' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->lname=$lname;
    $this->status=true;
  }

  function changeFname($fname) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $fname=trim($fname);
    $tmp=fnameValid($fname);
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
    $query="UPDATE users SET fname = '$fname' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->fname=$fname;
    $this->status=true;
  }

  function changePassword($password,$password_new,$password_newr) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $password=trim($password);
    $password_new=trim($password_new);
    $password_newr=trim($password_newr);
    $tmp=passwordValid($password_new,$password_newr);
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
    $query="SELECT password FROM users ";
    $query.="WHERE id='$this->id'";
    $result=mysql_query($query);
    if(mysql_num_rows($result)===false) {
      $this->errors[]="Could not execurte query";
      mysql_close();
      return;
    }
    $row=mysql_fetch_array($result);
    if(!isset($row['password'])) {
      $this->errors[]="Could not change password";
      mysql_close();
      return;
    }
    $user_pwd=$row['password'];
    $secure_pwd=generateHash($password,$user_pwd);
    if($secure_pwd!==$user_pwd) {
      $this->errors[]="Wrong password";
      mysql_close();
      return;
    }
    $secure_pwd_new=generateHash($password_new);
    $query="UPDATE users SET password = '$secure_pwd_new' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->password=$secure_pwd_new;
    $this->status=true;
  }

  function changeEmail($email) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $email=strtolower(trim($email));
    $tmp=emailValid($email);
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
    $token=generateActivationToken();
    $query="UPDATE users SET email = '$email', email_verified = 0, email_token='$token' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->email=$email;
    if(!sendActivationMail($email,$token)) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->email_verified=0;
    $this->status=true;
  }

  function changeStreet($street) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $street=trim($street);
    $tmp=streetValid($street);
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
    $query="UPDATE users SET street = '$street' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->street=$street;
    $this->status=true;
  }

  function changeAddress2($address2) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $address2=trim($address2);
    $tmp=address2Valid($address2);
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
    $query="UPDATE users SET address2 = '$address2' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->address2=$address2;
    $this->status=true;
  }

  function changeCity($city) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $city=trim($city);
    $tmp=cityValid($city);
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
    $query="UPDATE users SET city = '$city' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->city=$city;
    $this->status=true;
  }

  function changeState($state) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $state=trim($state);
    $tmp=stateValid($state);
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
    $query="UPDATE users SET state = '$state' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->state=$state;
    $this->status=true;
  }

  function changePostal($postal) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $postal=trim($postal);
    $tmp=postalValid($postal);
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
    $query="UPDATE users SET postal = '$postal' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->postal=$postal;
    $this->status=true;
  }

  function changeCountry($country) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $country=trim($country);
    $tmp=countryValid($country);
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
    $query="UPDATE users SET country = '$country' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->country=$country;
    $this->status=true;
  }

  function changeUid($uid) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $uid=trim($uid);
    $tmp=uidValid($uid);
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
    $query="UPDATE users SET uid = '$uid' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->uid=$uid;
    $this->status=true;
  }

  function changeBankName($bank_name) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $bank_name=trim($bank_name);
    $tmp=bankNameValid($bank_name);
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
    $query="UPDATE users SET bank_name = '$bank_name' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->bank_name=$bank_name;
    $this->status=true;
  }

  function changeBlz($blz) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $blz=trim($blz);
    $tmp=blzValid($blz);
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
    $query="UPDATE users SET blz = '$blz' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->blz=$blz;
    $this->status=true;
  }

  function changeKontonummer($kontonummer) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $kontonummer=trim($kontonummer);
    $tmp=kontoNummerValid($kontonummer);
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
    $query="UPDATE users SET kontonummer = '$kontonummer' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query..";
      mysql_close();
      return;
    }
    $this->kontonummer=$kontonummer;
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
    $query="UPDATE users SET associate_tag = '$associate_tag' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    if(isset($rec) and $rec==true) {
      $query="UPDATE websites SET associate_tag = '$associate_tag' WHERE user_id = '$this->id'";
      $result=mysql_query($query);
      if(!$result) {
        $this->status=false;
        $this->errors[]="Could not execute query";
        mysql_close();
        return;
      }
      $query="UPDATE ads SET associate_tag = '$associate_tag' WHERE user_id = '$this->id'";
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
    $query="UPDATE users SET access_key = '$access_key' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    if(isset($rec) and $rec==true) {
      $query="UPDATE websites SET access_key = '$access_key' WHERE user_id = '$this->id'";
      $result=mysql_query($query);
      if(!$result) {
        $this->status=false;
        $this->errors[]="Could not execute query";
        mysql_close();
        return;
      }
      $query="UPDATE ads SET access_key = '$access_key' WHERE user_id = '$this->id'";
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
    $query="UPDATE users SET private_key = '$private_key' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    if(isset($rec) and $rec==true) {
      $query="UPDATE websites SET private_key = '$private_key' WHERE user_id = '$this->id'";
      $result=mysql_query($query);
      if(!$result) {
        $this->status=false;
        $this->errors[]="Could not execute query";
        mysql_close();
        return;
      }
      $query="UPDATE ads SET private_key = '$private_key' WHERE user_id = '$this->id'";
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

  function changeActive($active) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $conn=mysql_connect($dbhost,$dbuser,$dbpass);
    $v='0';
    if(isset($active) and $active)
      $v='1';
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
    $query="UPDATE users SET active = '$v' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->active=$v;
    $this->status=true;
  }

  function changeEmailVerified($email_verified) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $conn=mysql_connect($dbhost,$dbuser,$dbpass);
    $v='0';
    if(isset($email_verified) and $email_verified)
      $v='1';
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
    $query="UPDATE users SET email_verified = '$v' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->email_verified=$v;
    $this->status=true;
  }

  function changeDefaultLanguage($lang) {
    global $available_languages;
    global $dbhost,$dbname,$dbuser,$dbpass;
    $lang=strtolower(trim($lang));
    $tmp=false;
    foreach($available_languages as $l) {
      if($lang===$l) {
        $tmp=true;
        break;
      }
    }
    if($tmp!==true) {
      $this->status=false;
      $this->errors[]="Selected language is not valid";
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
    $query="UPDATE users SET default_language = '$lang' WHERE email = '$this->email'";
    $result=mysql_query($query);
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return;
    }
    $this->default_language=$lang;
    $this->status=true;
  }

  function GetErrors() {
    $tmp=$this->errors;
    $this->errors=array();
    return $tmp;
    /* return $this->errors; */
  }

  function logout() {
    if(isset($_SESSION['userObj'])) {
      $_SESSION['userObj']=NULL;
      unset($_SESSION['userObj']);
    }
  }

  function GetStudies() {
    $studies=array();
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
    $query="SELECT * FROM studies WHERE user_id='".$id."'";
    $result=mysql_query($query);
    if(mysql_num_rows($result)==false) {
      $this->status=false;
      mysql_close();
      return false;
    }
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return false;
    }
    if(mysql_num_rows($result)!=false) {
      while($row=mysql_fetch_array($result)) {
        $s=new Study;
        $s->fillIn($row['id']);
        if($s->status)
          $studies[]=$s;
      }
    }
    return $studies;
  }
  function GetRuns() {
    $runs=array();
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
    $query="SELECT * FROM runs WHERE user_id='".$id."'";
    $result=mysql_query($query);
    if(mysql_num_rows($result)==false) {
      $this->status=false;
      mysql_close();
      return false;
    }
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return false;
    }
    if(mysql_num_rows($result)!=false) {
      while($row=mysql_fetch_array($result)) {
        $r=new Run;
        $r->fillIn($row['id']);
        if($r->status)
          $runs[]=$r;
      }
    }
    return $runs;
  }

  function ownsStudy($id) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $conn=mysql_connect($dbhost,$dbuser,$dbpass);
    if(!$conn) {
      return false;
    }
    if(!mysql_select_db($dbname,$conn)) {
      mysql_close();
      return false;
    }
    $query="SELECT * FROM studies ";
    $query.="WHERE id='$id'";
    $result=mysql_query($query);
    if($result===false) {
      return false;
    }
    if(mysql_num_rows($result)===false) {
      mysql_close();
      return false;
    } 
    $row=mysql_fetch_array($result);
    if($row==false) {
      mysql_close();
      return false;
    }
    if(isset($row['user_id']) and $row['user_id']==$this->id)
      return true;
    return false;
  }

  function ownsRun($id) {
    global $dbhost,$dbname,$dbuser,$dbpass;
    $conn=mysql_connect($dbhost,$dbuser,$dbpass);
    if(!$conn) {
      return false;
    }
    if(!mysql_select_db($dbname,$conn)) {
      mysql_close();
      return false;
    }
    $query="SELECT * FROM runs ";
    $query.="WHERE id='$id'";
    $result=mysql_query($query);
    if($result===false) {
      return false;
    }
    if(mysql_num_rows($result)===false) {
      mysql_close();
      return false;
    } 
    $row=mysql_fetch_array($result);
    if($row==false) {
      mysql_close();
      return false;
    }
    if(isset($row['user_id']) and $row['user_id']==$this->id)
      return true;
    return false;
  }
  
  function GetAvailableStudies() {
    $studies=array();
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
    $query="SELECT * FROM studies WHERE public='1' ";
    $result=mysql_query($query);
    if(mysql_num_rows($result)==false) {
      $this->status=false;
      mysql_close();
      return false;
    }
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return false;
    }
    if(mysql_num_rows($result)!=false) {
      while($row=mysql_fetch_array($result)) {
        $s=new Study;
        $s->fillIn($row['id']);
        if($s->status)
          $studies[]=$s;
      }
    }
    return $studies;
  }

  function GetAvailableRuns() {
    $runs=array();
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
    $query="SELECT * FROM runs WHERE public=1";
    $result=mysql_query($query);
    if(mysql_num_rows($result)===false) {
      $this->status=false;
      mysql_close();
      return false;
    }
    if(!$result) {
      $this->status=false;
      $this->errors[]="Could not execute query";
      mysql_close();
      return false;
    }
    if(mysql_num_rows($result)!=false) {
      while($row=mysql_fetch_array($result)) {
        $r=new Run;
        $r->fillIn($row['id']);
        if($r->status)
          $runs[]=$r;
      }
    }
    return $runs;
  }

}


?>