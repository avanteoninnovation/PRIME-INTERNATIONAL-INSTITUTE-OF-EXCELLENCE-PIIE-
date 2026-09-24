<?php

namespace App\Support\Staff;

/**
 * Staff creation refused for a reason the administrator can fix (bad photo,
 * email taken, …). The message is user-facing and never contains sensitive data.
 */
class StaffProvisioningException extends \RuntimeException
{
}
