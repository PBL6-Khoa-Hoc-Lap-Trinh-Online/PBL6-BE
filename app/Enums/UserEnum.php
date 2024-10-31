<?php

namespace App\Enums;

class UserEnum extends BaseEnum
{
    public const VERIFY_MAIL_USER = 'http://localhost:3000/auth/verify-email/user?token=';
    public const FORGOT_PASSWORD_USER = 'http://localhost:3000/auth/forgot-password/user?token=';
    public const URL_CLIENT = 'http://localhost:3000';
    public const URL_SERVER = 'https://lucifernsz.com/PBL6_Pharmacity/BE/PBL6-BE/public/api';

}
