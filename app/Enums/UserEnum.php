<?php

namespace App\Enums;

class UserEnum extends BaseEnum
{
    public const VERIFY_MAIL_USER = 'http://localhost:3000/auth/verify-email/user?token=';
    public const FORGOT_PASSWORD_USER = 'http://localhost:3000/auth/forgot-password/user?token=';

}
