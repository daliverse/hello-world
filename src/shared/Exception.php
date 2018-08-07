<?php

namespace Uie;


return 'Not in use at the moment, still being conceptualized';


/************************************************************************
 *
 *	See:  https://codeinphp.github.io/post/throwing-your-own-library-exceptions-in-php/
 *
 ************************************************************************/

class Exception extends \Exception {

  public function __construct( $message , $event = null, $previous = null, $code = null) {
    parent::__construct($message, $code, $previous);
  }

  private $Application;

}

?>
