<?php
namespace App\DAO;

use App\Models\UserModel;
use App\Services\PaginationService;

final class UserDAO extends Connection
{
    public function __construct()
    {
        parent::__construct();
    }
    public function getAllUsers(array $filters): array
    {
        $result = [];

        try {

            $procedurePaginationLine = PaginationService::getPaginationLine($filters["currentPage"], $filters["itemsPerPage"]);

            $likeClousure = $filters["name"];

            $sql = "SELECT 
                    u.*,
                    CASE 
	                    WHEN r.name = 'customer' THEN 'cliente'
	                    WHEN r.name = 'seller' THEN 'vendedor'
	                    ELSE 'admin'
                    END AS role,
                    r.category AS roleCategory
                    FROM users u
                    INNER JOIN user_roles ur
                    ON u.id = ur.user_id
                    INNER JOIN roles r
                    ON ur.role_id = r.id
                    WHERE u.name LIKE '%$likeClousure%'
                    ORDER BY u.id
                    $procedurePaginationLine";

            $statement = $this->pdo->prepare($sql);

            if (!$statement->execute()) {
                throw new \Exception("Não foi possível buscar os usuários no momento. Por favor, tente mais tarde.");
            }
            $users = $statement->fetchAll(\PDO::FETCH_ASSOC);

            $sqlCount = "SELECT 
                    COUNT(*) AS total
                    FROM users u
                    INNER JOIN user_roles ur
                    ON u.id = ur.user_id
                    INNER JOIN roles r
                    ON ur.role_id = r.id
                    WHERE u.name LIKE '%$likeClousure%'
                  ";

            $statement = $this->pdo->prepare($sqlCount);

            $statement->execute();

            $result["totalItems"] = (int) $statement->fetch(\PDO::FETCH_COLUMN);

            $result["data"] = $users;

            return $result;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getUserByEmail(string $email): ?UserModel
    {
        $sql = "SELECT * FROM users WHERE email = :email";

        $statement = $this->pdo->prepare($sql);

        $statement->bindParam(":email", $email, \PDO::PARAM_STR);

        if (!$statement->execute()) {
            return null;
        }

        $users = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (count($users) !== 0) {
            $user = new UserModel();

            $user->setId($users[0]["id"])
                ->setName($users[0]["name"])
                ->setEmail($users[0]["email"])
                ->setPassword($users[0]["password"]);
            return $user;
        }
        return null;

    }

    public function getUserById(int $userId): array
    {
        $result = [];

        try {
            $sql = "SELECT 
                    u.id, 
                    u.name, 
                    u.email, 
                    COALESCE(u.address, '') AS address, 
                    COALESCE(u.state_id, 0) AS stateId, 
                    COALESCE(u.city_id, 0) AS cityId, 
                    COALESCE(u.house_number, '') AS houseNumber, 
                    COALESCE(u.complement, '') AS complement, 
                    COALESCE(u.zip_code, '') AS zipCode,
                    CASE 
	                    WHEN r.name = 'customer' THEN 'cliente'
	                    WHEN r.name = 'seller' THEN 'vendedor'
	                    ELSE 'admin'
                    END AS role,
                    r.category AS roleCategory
                    FROM users u
                    INNER JOIN user_roles ur
                    ON u.id = ur.user_id
                    INNER JOIN roles r
                    ON ur.role_id = r.id
                    WHERE u.id = :userId
                    ";

            $statement = $this->pdo->prepare($sql);

            $statement->bindParam(":userId", $userId, \PDO::PARAM_INT);

            if (!$statement->execute()) {
                throw new \Exception("Não foi possível consultar o usuário no momento. Por favor, tente mais tarde.");
            }

            $user = $statement->fetch(\PDO::FETCH_ASSOC);


            $result = $user;
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function insertUser(UserModel $user): array
    {
        $result = [];
        try {
            $this->pdo->beginTransaction();

            $sqlValidateEmail = "SELECT * FROM users WHERE email = :email";
            $statement = $this->pdo->prepare($sqlValidateEmail);

            $statement->bindValue(":email", $user->getEmail(), \PDO::PARAM_STR);
            if (!$statement->execute()) {
                throw new \Exception("Não foi possível validar o e-mail no momento. Por favor, tente mais tarde.");
            }
            $userRegistered = $statement->fetchAll(\PDO::FETCH_ASSOC);

            if (count($userRegistered) !== 0) {
                throw new \InvalidArgumentException("O e-mail informado já está cadastrado no sistema.");
            }


            $sqlUserRegister = "INSERT INTO users (
                name, 
                email, 
                password, 
                created_at
                ) VALUES (
                    :name, 
                    :email, 
                    :password, 
                    CONVERT(datetime, :createdAt, 120)
                    )";
            $statement = $this->pdo->prepare($sqlUserRegister);

            $currentDateTime = (new \DateTime())->format("Y-m-d H:i:s");

            $statement->bindValue(":name", $user->getName(), \PDO::PARAM_STR);
            $statement->bindValue(":email", $user->getEmail(), \PDO::PARAM_STR);
            $statement->bindValue(":password", $user->getPassword(), \PDO::PARAM_STR);
            $statement->bindValue(":createdAt", $currentDateTime, \PDO::PARAM_STR);

            if (!$statement->execute()) {
                throw new \Exception("Não foi possível inserir o usuário no momento. Por favor, tente mais tarde.");
            }

            $userId = (int) $this->pdo->lastInsertId();

            $roleDAO = new RoleDAO();
            $roleCustomerId = $roleDAO->getRoleIdByCategory(1);

            $sqlSetUserAsCustomer = "INSERT INTO user_roles(
                user_id, 
                role_id
                ) VALUES (
                    :userId, 
                    :roleId
                    )";
            $statement = $this->pdo->prepare($sqlSetUserAsCustomer);

            $statement->bindParam(":userId", $userId, \PDO::PARAM_INT);
            $statement->bindParam(":roleId", $roleCustomerId, \PDO::PARAM_INT);

            if (!$statement->execute()) {
                throw new \Exception("Não foi possível inserir o usuário no momento. Por favor, tente mais tarde.");
            }

            $result["message"] = "Usuário inserido com sucesso.";

            $this->pdo->commit();
            return $result;

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateUser(UserModel $user): array
    {
        $result = [];

        try {
            $sql = "UPDATE users SET 
                        name = :name,
                        email = :email,
                        address = :address,
                        state_id = :stateId,
                        city_id = :cityId,
                        house_number = :houseNumber,
                        complement = :complement,
                        zip_code = :zipCode
                    WHERE id = :id";

            $statement = $this->pdo->prepare($sql);

            $statement->bindValue(":name", $user->getName(), \PDO::PARAM_STR);
            $statement->bindValue(":email", $user->getEmail(), \PDO::PARAM_STR);
            $statement->bindValue(":address", $user->getAddress(), \PDO::PARAM_STR);
            $statement->bindValue(":stateId", $user->getStateId(), \PDO::PARAM_INT);
            $statement->bindValue(":cityId", $user->getCityId(), \PDO::PARAM_INT);
            $statement->bindValue(":houseNumber", $user->getHouseNumber(), \PDO::PARAM_STR);
            $statement->bindValue(":complement", $user->getComplement(), \PDO::PARAM_STR);
            $statement->bindValue(":zipCode", $user->getZipCode(), \PDO::PARAM_STR);
            $statement->bindValue(":id", $user->getId(), \PDO::PARAM_INT);

            if (!$statement->execute()) {
                throw new \Exception("Não foi possível atualizar os dados do usuário no momento. Por favor, tente mais tarde.");
            }

            $result["message"] = "Usuário atualizados com sucesso.";

            return $result;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function updatePassword(UserModel $user): array
    {
        $result = [];

        try {
            $sql = "UPDATE users SET password = :password WHERE id = :id";

            $statement = $this->pdo->prepare($sql);

            $statement->bindValue(":password", $user->getPassword(), \PDO::PARAM_STR);
            $statement->bindValue(":id", $user->getId(), \PDO::PARAM_INT);

            if (!$statement->execute()) {
                throw new \Exception("Não foi possível atualizar a senha, tente novamente mais tarde.");
            }

            $result["message"] = "Senha atualizada com sucesso.";

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function updateUserRole(int $userId, int $category): array
    {
        $result = [];

        try {
            $sql = "UPDATE user_roles 
            SET role_id = 
            (
                SELECT id 
                FROM roles
                WHERE category = :category
            )
            WHERE user_id = :userId";

            $statement = $this->pdo->prepare($sql);

            $statement->bindParam(":userId", $userId, \PDO::PARAM_INT);
            $statement->bindParam(":category", $category, \PDO::PARAM_INT);

            if (!$statement->execute()) {
                throw new \Exception("Não foi possível atualizar o perfil do usuário no momento. Por favor, tente mais tarde.");
            }
            $result["message"] = "Perfil do usuário atualizado com sucesso.";
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function deleteUserById(int $id): array
    {
        $result = [];

        try {
            $sql = "DELETE FROM users WHERE id = :id";

            $statement = $this->pdo->prepare($sql);

            $statement->bindParam(":id", $id, \PDO::PARAM_INT);

            if (!$statement->execute()) {
                throw new \Exception("Não foi possível excluir o usuário no momento. Por favor, tente mais tarde.");
            }

            $result["message"] = "Usuário excluído com sucesso.";

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}