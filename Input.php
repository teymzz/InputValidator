<?php

/**
 * This class is a simply used for validating the following lists.
 * strings, bool, integer, mail, phone, number, ranges, text 
 * 
 * For proper validation of mail and phone you may need a more complex solution
 *
 * @author by Akinola Saheed <github@teymzz.de>
 *
 */

class Input{

  /**
   * value supplied
   *
   * @var [sring | array]
   */
  private $value;

  /**
   * type of validation 
   *
   * @var array
   */
  private $default = [
    'type'   => null,
    'range'  => null,
    'length' => null,
  ];

  private const types = [
    'string','text',
    'integer','number',
    'mail','phone',
    'pregmatch','bool'
  ];  

  private $type           = null;
  private $range          = null;
  private $length         = null;         
  private $allow_range    = false;
  private $allow_length   = false;
  private $pregmatch      = false;
  
  private $auto_response  = true;
  private $error_exists   = false;

  private $issue;
  private $message;

  /**
   * sets a value to be validated along with some custom settings
   *
   * @param string $value
   * @param array $config 
   *                [
   *                 'type'  => @property const types,
   *                 'range' => [value_a, value_b],
   *                 'length' =>[min, max],
   *                 'pregmatch' => 'pattern'
   *                ]
   * @param boolean $check_space
   * @return void
   */
  public function set($value,array $config,$check_space=false){

    //check for existing errors first
    if($this->error_exists) return $this->setIssue();


    //check if no value was supplied
    if(trim($value) == null) return $this->response("no value set"); 

    //check if type was set
    if(!$this->set_type($config['type'])) return $this->response("no type set");

    $this->value = $value;
    
    //check for length if supplied
    if(array_key_exists('length', $config)){

      $length = $this->length = $config['length'];

      if(!$this->validate_length($length)){
        $this->error_exists = true;
        $this->issue   = "length";
        return false;
      }

    } elseif ($this->allow_length) {
      
      if(!$this->validate_length($this->default['length'])){
        $this->error_exists = true;
        $this->issue   = "length";
        return false;
      }

    }

    //set preg_match
    if(array_key_exists('pregmatch', $config)){

      $this->pregmatch = $config['pregmatch'];

      if(!$this->matched($value)) return false;

    } 

    //set space checking
    $check_space = ($check_space === 'no-space')? true : false;

    if($this->findSpace($value,$check_space)) return false;

    //check for range if supplied
    if(array_key_exists('range', $config)){
      
      $range =  $config['range'];
      $this->range = $range;

      $allow_range = ($range == true)? true : false;

    } else {

      $this->range = null;

      $allow_range = ($this->default['range'] != null)? true : false;
         
    }

    if($allow_range == true){

      if($this->validate_range($value)){
        if($this->auto_response){
          return $this->validate();
        }
      }else{
        $this->error_exists = true;
        $this->issue   = "range";
        return false;
      }

    }else{
      if($this->auto_response == true){
        return $this->validate();
      } 
    }

  }

  /**
   * configures the class to return values for each data validation
   *
   * @param boolean $value
   * @return void
   */
  public function auto_response($value = true){
    $this->auto_response = is_bool($value)? $value : false;
  }

  /**
   * sets the minimum and maximum length of characters allowed
   *
   * @param [integer | array] $length
   * @return void
   */
  public function default_length($length){
    $this->allow_length = true;
    $this->default['length'] = $length;
  }

  /**
   * sets the default type
   *
   * @param string $default_type ['number' | 'string' ...]
   * @return void
   */
  public function default_type($default_type){    
    $this->default['type'] = $default_type;
  }
  
  /**
   * sets the default range
   *
   * @param [array | integer] $default_range
   * @return void
   */
  public function default_range($default_range){
    $this->allow_range   = true;
    $this->default['range'] = $default_range;
  }

  private function set_type($type=null){

    $types = self::types;
    $type  = ($type == null)? strtolower($this->default['type']) : strtolower($type);

    if(in_array($type,$types)){

      $this->type  =  $type;
      return true;

    }else{

      return false;

    }

  }

  private function findSpace($value,$check = false){
    if($check){
      if(strpos($value," ")) {
        $this->response("value does not allow space",false);
        $this->issue = 'space';
        return true;
      }
    }
    return false;
  }

  private function validate_string(){

    $value = $this->value;

    if (is_string($value)) {

      return $value;

    } else {

      return $this->response('value is not a valid string');

    }

  }

  private function validate_text(){

    $value = $this->value;

    if(!preg_match('/[^a-zA-Z]/', $value)){
      return $value;
    }

    return $this->response('value is not a valid text'); 

  }  

  private function validate_integer(){
      
    $value = $this->value;
        
    if($value != "0"){
        $nvalue = $value + 0;

        if(is_int($nvalue)&&($nvalue!=0)){
          if(strlen($nvalue)<10){
            if($this->allow_range == true){
              $ranges = $this->range;
              if(!is_array($ranges)) return false;
              if(in_array($nvalue,$ranges)){
                $this->response("value in range",true);
                return $value;
              }else{
                return $this->response("value not in range");
              }
            }else{
              $this->response("value is valid");
              return $nvalue;
            }
          }else{
              $this->reponse("value is too large");
              return false;
          }
        }else{
          if(strlen($value)>10){
            $mess = 'This value is too large';
          }else{
            $mess = 'This value is invalid';
          }
            return $this->response($mess);
        }

    }

  }

  private function validate_length($length){
    
    $value = $this->value;
    if($length != null){

      if(!is_array($length)){
        $len = is_numeric($length)? $length + 0 : $length;
      }else{        

        if(count($length) == 1){ $length[1] = $length[0]; }

        $count = count($length);

        if($count == 2){
          $len1 = $length[0];
          $len2 = $length[1];
        }
      }
     
    }

    if(isset($len)){

      if(!is_numeric($len)){
        return $this->response("string length is invalid");
      }

      if( (strlen($value) > 0) && (strlen($value) <= $length) && (!is_empty($length)) ){
        return true;
      }else{
        return $this->response("string maximum length ($length chars) exceeded !");
      }

    }elseif (isset($len1)) {

      if(!is_numeric($len1) || !is_numeric($len2)){
        return $this->response("length range must be numeric");
      }

      if($len1 > $len2){
        return $this->response("range of lengths misplaced");
      }

      if($len1 == $len2){
        if(strlen($value) != $len1){ return $this->response("value must be $len1 chars in length"); }
        return true;
      }
      
      $range = range($len1,$len2);

      if(in_array(strlen($value), $range)){
        return true;
      }else{
        if(is_array($length)){ $length = $length[0].' - '.$length[1]; }
        return $this->response("value not in range of $length chars!");     
      }

    }
    
    return $this->response("characters length range of undetermined!");

  }

  private function validate_range($value){

    $range = ($this->range == null)? $this->default['range'] : $this->range;

    if(in_array($value,$range)){
      return true;
    }else{
      return false;
    }

  }
  
  private function validate_number(){
    $value = $this->value;

    if(is_numeric($value)){
      return $value;
    }
    return $this->response('value supplied is not a valid number');
  }

  private function validate_phone(){
    $value = $this->value;

    if(is_numeric($value) && strlen($value) < 18){
      return $value;
    }
    return $this->response('value supplied is not a valid phone');
  }

  private function validate_mail(){
      
    $value = trim($this->value);

    if($this->findSpace($value,true)) return false;
    
    $pattern = "@\b(\b[a-zA-Z0-9.+-_]+\@[a-zA-Z0-9.+-]+[\.]([a-zA-Z]){2,63}\S\b)\b@";
    if(preg_match($pattern,$value)){

      return $value;

    } 
     return $this->response("$value is not a valid email")
;  }

  private function matched(){
    $value = $this->value;
    $pattern = '@'.$this->pregmatch.'@';

    return (preg_match($pattern, $value))? $value : false;
  }

  private function validate_bool(){
   
    $value = $this->value;

    return (is_bool($value))? true : false; /* bool value will always return true for either true or false while other values returns false*/
  }

  /**
   * calls the validation function
   *
   * @return bool
   */
  public function validate(){

    if(empty($this->type)) return false ;
    
    $types = self::types;      
    $type  = strtolower($this->type);

    if(in_array($type,$types)){
      switch($type){
        case "string": 
          return $this->validate_string();
          break;     
        case "text":
          return $this->validate_text();
          break;  
        case "integer":
          return $this->validate_integer();
          break;
        case "number":
          return $this->validate_number();
          break;
        case "phone":
          return $this->validate_phone();
          break;
        case "mail":
          return $this->validate_mail();
          break;                   
        case "pregmatch":
          return $this->validate_pregmatch();
          break;
        case "bool":
          return $this->validate_bool();
          break;
        default: return false;
      }
    }else{
      return $this->response("validation cannot be found!");
    }  
  }

  public function setIssue(){
    
    if($this->error_exists){

        $issue = $this->issue;

        switch($issue){
          case "space":
            $message = "no space allowed";
            break;
          case "empty":
            $message = "field is empty";
            break;
          case "range":
            $message = "value set not within range";
            break;                                     
          case "length":
            $length  = ($this->length == null)? $this->default['length']: $this->length;
            $length  = (is_array($length))? $length[0]." - ".$length[1] : $length;
            $message = ('value set exceed a length of '.$length.' chars');
          break;
          default: $message = $this->message; //'please input a valid value';
       }

       $this->response($message);

      } else {

        $this->response("",true);

      }

      return false;
  }

  /**
   * set and return message
   *
   * @param [type] $message
   * @param boolean $return
   * @return bool | 'string'
   */
  public function response($message=null,$return = false){

    if(func_num_args() == 0) return $this->message;

    if($message != null){

      $this->message = $message; 

      $this->error_exists = ($return == false)? true : false;

      return $return;
    }


  }

}


/**
 * DOCUMENTATION OF INPUT CLASS
 * 
 * //initialize class
 * $input = new Input;
 * 
 * Example 1:  ............................................
 *  $name = 'grego';
 *  $name = $input->set($name,['type'=>'string',length=>10,range=>['felix','tina','grego']]);
 *    
 *  Explanation 1: The above will:
 *    1: check if $name is a string
 *    2: check if the length is not more than 4
 *    3: check if $name can be found in array ['felix','tina','grego'] 
 *      4: if all validation is successful, the value is returned, else, a null value is returned;
 * 
 * 
 * Example 2:  ............................................
 *  $name = 'russel'; $number = '10';
 * 
 *  $name = $input->set($name,['type'=>'string',length=>[4, 10],range=>['felix','tina','grego']]);
 *  $number = $input->set($number,['type'=>'number',length=>2]); 
 *  
 *    
 *  Explanation 2: In the above,
 * 
 *   - The first $input->set() will:
 *      1: check if $name is a string
 *      2: check if the length is not less than 4 and not more than 10
 *    3: check if $name can be found in array ['felix','tina','grego'] 
 *      4: if all validation is successful, the value is returned, else, a null value is returned;
 * 
 * 
 * Example 3: to prevent the use of spaces .................
 *  $name = 'russel more';
 * 
 *  $name = $input->set($name,['type'=>'string'],'no-space'); 
 * 
 *  In the above, $input->set() will:
 *      1: check if $name is a string
 *      2: check if there is no space in $name
 *      3: if all validation is successful, the value is returned, else, a null value is returned;
 *      4: Since $name contains space, a null value is returned
 * 
 * 
 * SETTING DEFAULTS
 * 
 * You can set defaults configuration by using
 *  $input->default_type()
 *  $input->default_range()
 *  $input->default_length();
 * 
 *  Note: 
 *    1: These methods should be declared before using $input->set() method;
 *    2: Any configuration set within $input->set() will overwrite the default ONLY for the current value as it will never change the default configuration
 * 
 */