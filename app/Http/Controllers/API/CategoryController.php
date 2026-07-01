<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Repository\CategoryRepository;

class CategoryController extends BaseController
{

    public function __construct(protected  CategoryRepository $categoryRepository)
    {
    }

    public function index()
    {
        $data = $this->categoryRepository->getAll();
        return $this->sendResponse($data, trans('app.done'));
    }
}
