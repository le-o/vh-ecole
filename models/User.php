<?php

namespace app\models;

class User extends \yii\base\Object implements \yii\web\IdentityInterface
{
    public $id;
    public $username;
    public $password;
    public $authKey;
    public $accessToken;

    private static $users = [
        '100' => [
            'id' => '100',
            'username' => 'admin',
            'password' => 'c0rnal1nVH',
            'authKey' => 'test100key',
            'accessToken' => '100-token',
        ],
        '201' => [
            'id' => '201',
            'username' => 'benoit',
            'password' => 'm0t2Pa$$e',
            'authKey' => 'test201key',
            'accessToken' => '201-token',
        ],
        '413' => [
            'id' => '413',
            'username' => 'michael',
            'password' => 'indiaPal3Ale!',
            'authKey' => 'test413key',
            'accessToken' => '413-token',
        ],
        '600' => [
            'id' => '600',
            'username' => 'gestion',
            'password' => 'wh1ter@bbIt',
            'authKey' => 'test600key',
            'accessToken' => '600-token',
        ],
        '1001' => [
            'id' => '1001',
            'username' => 'accueil',
            'password' => 'm0t2Pa$$e',
            'authKey' => 'test1100key',
            'accessToken' => '1100-token',
        ],
        '1101' => [
            'id' => '1101',
            'username' => 'moniteurs',
            'password' => 'm0nit3urs',
            'authKey' => 'test1001key',
            'accessToken' => '1001-token',
        ],
    ];

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return isset(self::$users[$id]) ? new static(self::$users[$id]) : null;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        foreach (self::$users as $user) {
            if ($user['accessToken'] === $token) {
                return new static($user);
            }
        }

        return null;
    }

    /**
     * Finds user by username
     *
     * @param  string      $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        foreach (self::$users as $user) {
            if (strcasecmp($user['username'], $username) === 0) {
                return new static($user);
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
     * Validates password
     *
     * @param  string  $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return $this->password === $password;
    }
}
