<?php

namespace App\Presenters\Users;

use App\Transformers\Users\UsersAllTransformer;
use Prettus\Repository\Presenter\FractalPresenter;

class UsersAllPresenter extends FractalPresenter
{
    public function getTransformer(): UsersAllTransformer
    {
        return new UsersAllTransformer;
    }
}
