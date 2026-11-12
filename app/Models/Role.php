<?php

declare(strict_types=1);

namespace App\Models;

use RuntimeException;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected string $guard_name = 'api';

    public function delete(): ?bool
    {
        if ($this->users()->exists()) {
            throw new RuntimeException('This Role still has associated Users.');
        }

        return parent::delete();
    }
}
