<?php namespace CodeIgniter\Security;

use CodeIgniter\HTTP\RequestInterface;

class MockSecurity extends Security
{
    public function CSRFSetCookie(RequestInterface $request)
    {
        $_COOKIE['csrf_cookie_name'] = $this->CSRFHash;

        return $this;
    }

    //--------------------------------------------------------------------

}
