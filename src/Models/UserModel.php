<?php
namespace Models;

use Core\Model;
use Core\Security;
use Events\UserRegisteredEvent;
use Core\Container;

class UserModel extends Model
{
    /**
     * Nom de la table en base de données
     * @var string
     */
    protected $table = 'users';
    
    /**
     * Clé primaire
     * @var string
     */
    protected $primaryKey = 'id';
    
    /**
     * Colonnes modifiables
     * @var array
     */
    protected $fillable = ['name', 'email', 'password', 'roles', 'permissions', 'remember_token'];
    
    /**
     * Trouver un utilisateur par son email
     *
     * @param string $email
     * @return object|null
     */
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        return $this->db->query($sql, ['email' => $email], true);
    }
    
    /**
     * Créer un nouvel utilisateur
     *
     * @param array $data Données de l'utilisateur
     * @return int ID de l'utilisateur créé
     */
    public function create(array $data)
    {
        // Si un mot de passe est fourni, le hacher
        if (isset($data['password'])) {
            $security = new Security();
            $data['password'] = $security->hashPassword($data['password']);
        }
        
        // Si des rôles sont fournis sous forme de tableau, les convertir en chaîne
        if (isset($data['roles']) && is_array($data['roles'])) {
            $data['roles'] = implode(',', $data['roles']);
        }
        
        // Si des permissions sont fournies sous forme de tableau, les convertir en chaîne
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $data['permissions'] = implode(',', $data['permissions']);
        }
        
        // Insérer l'utilisateur en base de données
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        $this->db->execute($sql, $data);
        
        $userId = $this->db->lastInsertId();
        
        // Récupérer l'utilisateur nouvellement créé
        $user = $this->findById($userId);
        
        // Émettre un événement d'inscription utilisateur
        if ($user) {
            $container = Container::getInstance();
            
            if ($container->has('events')) {
                $events = $container->make('events');
                $events->dispatch(new UserRegisteredEvent($user));
            }
        }
        
        return $userId;
    }
    
    /**
     * Mettre à jour un utilisateur
     *
     * @param int $id ID de l'utilisateur
     * @param array $data Données à mettre à jour
     * @return bool
     */
    public function update($id, array $data)
    {
        // Si un mot de passe est fourni, le hacher
        if (isset($data['password']) && !empty($data['password'])) {
            $security = new Security();
            $data['password'] = $security->hashPassword($data['password']);
        } else {
            // Ne pas mettre à jour le mot de passe s'il est vide
            unset($data['password']);
        }
        
        // Si des rôles sont fournis sous forme de tableau, les convertir en chaîne
        if (isset($data['roles']) && is_array($data['roles'])) {
            $data['roles'] = implode(',', $data['roles']);
        }
        
        // Si des permissions sont fournies sous forme de tableau, les convertir en chaîne
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $data['permissions'] = implode(',', $data['permissions']);
        }
        
        // Construire la requête de mise à jour
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = "{$column} = :{$column}";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE {$this->primaryKey} = :id";
        
        $data['id'] = $id;
        
        return $this->db->execute($sql, $data);
    }
    
    /**
     * Stocker un token "Se souvenir de moi"
     *
     * @param int $userId ID de l'utilisateur
     * @param string $token Token haché
     * @return bool
     */
    public function storeRememberToken($userId, $token)
    {
        $sql = "UPDATE {$this->table} SET remember_token = :token WHERE id = :id";
        return $this->db->execute($sql, ['token' => $token, 'id' => $userId]);
    }
    
    /**
     * Vérifier un token "Se souvenir de moi"
     *
     * @param int $userId ID de l'utilisateur
     * @param string $token Token à vérifier
     * @return bool
     */
    public function validateRememberToken($userId, $token)
    {
        $user = $this->findById($userId);
        
        if (!$user || empty($user->remember_token)) {
            return false;
        }
        
        $security = new Security();
        return $security->verifyPassword($token, $user->remember_token);
    }
    
    /**
     * Attribuer un rôle à un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param string $role Rôle à attribuer
     * @return bool
     */
    public function assignRole($userId, $role)
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            return false;
        }
        
        $roles = $user->roles ? explode(',', $user->roles) : [];
        
        if (!in_array($role, $roles)) {
            $roles[] = $role;
            
            return $this->update($userId, [
                'roles' => implode(',', $roles)
            ]);
        }
        
        return true;
    }
    
    /**
     * Retirer un rôle à un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param string $role Rôle à retirer
     * @return bool
     */
    public function removeRole($userId, $role)
    {
        $user = $this->findById($userId);
        
        if (!$user || empty($user->roles)) {
            return false;
        }
        
        $roles = explode(',', $user->roles);
        
        if (($key = array_search($role, $roles)) !== false) {
            unset($roles[$key]);
            
            return $this->update($userId, [
                'roles' => implode(',', $roles)
            ]);
        }
        
        return true;
    }
    
    /**
     * Attribuer une permission à un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param string $permission Permission à attribuer
     * @return bool
     */
    public function grantPermission($userId, $permission)
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            return false;
        }
        
        $permissions = $user->permissions ? explode(',', $user->permissions) : [];
        
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            
            return $this->update($userId, [
                'permissions' => implode(',', $permissions)
            ]);
        }
        
        return true;
    }
    
    /**
     * Retirer une permission à un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param string $permission Permission à retirer
     * @return bool
     */
    public function revokePermission($userId, $permission)
    {
        $user = $this->findById($userId);
        
        if (!$user || empty($user->permissions)) {
            return false;
        }
        
        $permissions = explode(',', $user->permissions);
        
        if (($key = array_search($permission, $permissions)) !== false) {
            unset($permissions[$key]);
            
            return $this->update($userId, [
                'permissions' => implode(',', $permissions)
            ]);
        }
        
        return true;
    }
    
    /**
     * Obtenir le nom d'utilisateur
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->name ?? '';
    }
    
    /**
     * Obtenir l'email de l'utilisateur
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email ?? '';
    }
}