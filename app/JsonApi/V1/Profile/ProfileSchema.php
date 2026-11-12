<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Profile;

use App\JsonApi\V1\Users\UserSchema;

class ProfileSchema extends UserSchema
{
    protected string $resourceType = 'users';
}
