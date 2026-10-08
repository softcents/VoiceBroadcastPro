<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class OwnedByAuthUser implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! auth()->check() || auth()->user()->isAdmin()) {
            return;
        }

        if (auth()->user()->isSP()) {
            $builder->whereIn(
                $model->getTable().'.user_id',
                User::query()
                    ->select('id')
                    ->where('sp_id', auth()->id())
                    ->where('type', 'user')
            );

            return;
        }

        $builder->where($model->getTable().'.user_id', auth()->id());
    }
}
