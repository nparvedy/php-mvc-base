<?php
namespace Models;

use Core\Model;

class UserModel extends Model
{
    protected $table = 'users';

    public function findByEmail($email)
    {
        $query = "SELECT * FROM {$this->table} WHERE email = :email";
        return $this->db->query($query, ['email' => $email], true);
    }

    public function authenticate($email, $password)
    {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        return password_verify($password, $user->password) ? $user : false;
    }

    public function registerUser($name, $email, $password)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->create($userData);
    }
}