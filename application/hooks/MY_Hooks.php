<?php

class MY_Hooks
{
    function __construct()
    {
    }

    function checkPermission()
    {
        $CI =& get_instance();

        if (
            isset($CI->noLoginAllow)
            && (is_array($CI->noLoginAllow) === false
            || in_array($CI->router->method, $CI->noLoginAllow) === false)
        ) {
            // 로그인을 했는지 판단을 하는 로직을 넣으면 되겠죠.
            if (1) {
                // redirect url도 알아서...
            }
        }
    }
}