<?php

namespace App\Presenters\Users;

use App\Transformers\Users\UserTransformer;
use Prettus\Repository\Presenter\FractalPresenter;

class UserPresenter extends FractalPresenter
{
    public function getTransformer(): UserTransformer
    {
        return new UserTransformer;
    }
}
