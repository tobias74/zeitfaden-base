<?php

class AbstractUserSession
{
	protected $loggedInUserId = false;

  public function setLoggedInUserId($val)
  {
    $this->loggedInUserId = $val;
  }
  
  public function getLoggedInUserId()
  {
    return $this->loggedInUserId;
  }

  public function setFacebookUserId($val)
  {
    $this->facebookUserId = $val;
  }


	protected function isUserLoggedIn()
	{
		if ($this->getLoggedInUserId() !== false)
		{
			return true;
		}
		else
		{
			return false;
		}
	}


  public function setLoginPerformedByShard($val)
  {
    $this->loginPerformedByShard = $val;
  }


  public function hasAdminRole()
  {
    return false;
  }

  public function isOAuthSession()
  {
    return false;
  }


}





