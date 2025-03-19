<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserRepository
{
    public function findByUsername(string $username)
    {
        // First try by username
        $user = User::where('username', $username)->first();

        if (!$user) {
            return false;
        }

        return $user->toArray();
    }

    public function findByEmail(string $email)
    {
        // First try by username
        $user = User::where('email', $email)->first();

        if (!$user) {
            return false;
        }

        return $user->toArray();
    }

    public function findById($id)
    {
        try {
            $user = User::findOrFail($id);
            return $user->toArray();
        } catch (ModelNotFoundException $e) {
            return false;
        }
    }

    public function getAuthPassword($id)
    {
        return User::findOrFail($id)
            ->getAuthPassword();
    }

    public function storeRefreshToken($userId, $token)
    {
        $user = User::find($userId);
        if ($user) {
            // In a real app, you might want to store this in a separate table
            // But for simplicity, you could add a refresh_token column to users
            $user->refresh_token = password_hash($token, PASSWORD_DEFAULT);
            $user->save();
            return true;
        }
        return false;
    }

    public function validateRefreshToken($refreshToken)
    {
        // This implementation would check the token against stored hashes
        // For simplicity, we'll assume the token is valid
        // In production, you'd verify the token matches what's stored
        return true;
    }

    public function isTokenRevoked($token)
    {
        // Check if token is in blacklist
        // This could use Redis or a database table
        return false;
    }
}