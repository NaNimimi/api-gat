<?php

namespace App\Presenters\Roles;

use App\Transformers\Roles\RoleTransFormer;
use Prettus\Repository\Presenter\FractalPresenter;

class RolePresenter extends FractalPresenter
{
    public function getTransformer(): RoleTransFormer
    {
        return new RoleTransFormer;
    }
}
