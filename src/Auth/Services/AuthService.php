<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Auth\Exception\UserAlreadyExistsException;
use App\Auth\Dtos\Request\UserRegisterRequest;
use App\Auth\Dtos\Response\UserRegisterResponse;
use App\Auth\Repositories\AuthRepository;

class AuthService {
    private AuthRepository $repository;

    public function __construct(AuthRepository $repository) {
        $this->repository = $repository;
    }

    public function register(UserRegisterRequest $request): UserRegisterResponse {
        if ($this->repository->findByDni($request->dni) !== null) {
            throw new UserAlreadyExistsException("User with DNI already exists");
        }

        return null;
    }
}
