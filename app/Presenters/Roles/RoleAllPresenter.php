<?php

namespace App\Presenters\Roles;

use App\Transformers\Roles\RoleAllTransformer;
use Prettus\Repository\Presenter\FractalPresenter;

class RoleAllPresenter extends FractalPresenter
{
    public function getTransformer(): RoleAllTransformer
    {
        return new RoleAllTransformer;
    }
}
