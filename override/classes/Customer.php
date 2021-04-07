<?php
/*
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * <DARK SIDE TEAM> wrote this file. As long as you retain this notice you
 * can do whatever you want with this stuff. If we meet some day, and you think
 * this stuff is worth it, you can buy me a beer in return Poul-Henning Kamp
 * ----------------------------------------------------------------------------
 */

class Customer extends CustomerCore
{
    public function customHook()
    {
        $isSuccess = $this->isLogged($withGuest = false);

        if ($isSuccess == true) {
            Hook::exec('actionCustomerLoginAfter', array('customer' => $this));
        }
    }
}
